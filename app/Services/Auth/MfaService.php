<?php

namespace App\Services\Auth;

use App\Models\Core\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * MFA / TOTP service (D-039 / T-4.5 / AF-1 / AF-3).
 *
 * - Secret is written to User::$mfa_secret, which is ENCRYPTED at rest by the
 *   model `encrypted` cast (AF-1/F-5) — this service never persists plaintext.
 * - Recovery codes are returned in plaintext ONCE at generation, then stored as
 *   bcrypt HASHES (AF-3/D-043). Verification is constant-work Hash::check; a code
 *   is removed on use (single-use).
 */
class MfaService
{
    public function __construct(private Google2FA $google2fa) {}

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    /**
     * Begin enrolment: store an (encrypted) secret and return the secret +
     * provisioning QR (SVG). The secret is displayed once for manual entry.
     */
    public function beginEnrolment(User $user): array
    {
        $secret = $this->generateSecret();

        $user->mfa_secret = $secret;   // encrypted by the model cast on save
        $user->save();

        $otpauthUrl = $this->google2fa->getQRCodeUrl(
            (string) config('app.name'),
            $user->email,
            $secret
        );

        return [
            'secret' => $secret,
            'qr_svg' => $this->qrSvg($otpauthUrl),
        ];
    }

    public function verify(User $user, string $code): bool
    {
        if (empty($user->mfa_secret)) {
            return false;
        }

        // $user->mfa_secret is transparently decrypted by the cast.
        return $this->google2fa->verifyKey($user->mfa_secret, $code, 1);
    }

    /**
     * Confirm enrolment with a valid code; enable MFA and issue recovery codes.
     * Returns the plaintext recovery codes ONCE, or null if the code is invalid.
     */
    public function confirmEnrolment(User $user, string $code): ?array
    {
        if (! $this->verify($user, $code)) {
            return null;
        }

        $plain = $this->generateRecoveryCodes($user);
        $user->mfa_enabled = true;
        $user->save();

        return $plain;
    }

    /**
     * Generate recovery codes. Plaintext is returned to the caller (shown once);
     * only bcrypt HASHES are persisted (AF-3).
     */
    public function generateRecoveryCodes(User $user, int $count = 8): array
    {
        $plain = [];
        $hashed = [];

        for ($i = 0; $i < $count; $i++) {
            $code = Str::upper(Str::random(10));
            $plain[] = $code;
            $hashed[] = Hash::make($code);
        }

        $user->mfa_recovery_codes = $hashed;

        return $plain;
    }

    /**
     * Verify and consume a single-use recovery code.
     */
    public function verifyRecoveryCode(User $user, string $code): bool
    {
        $codes = $user->mfa_recovery_codes ?? [];

        foreach ($codes as $index => $hash) {
            if (Hash::check($code, $hash)) {
                unset($codes[$index]);
                $user->mfa_recovery_codes = array_values($codes);
                $user->save();

                return true;
            }
        }

        return false;
    }

    public function disable(User $user): void
    {
        $user->forceFill([
            'mfa_enabled' => false,
            'mfa_secret' => null,
            'mfa_recovery_codes' => null,
        ])->save();
    }

    private function qrSvg(string $otpauthUrl): string
    {
        $renderer = new ImageRenderer(new RendererStyle(220), new SvgImageBackEnd);

        return (new Writer($renderer))->writeString($otpauthUrl);
    }
}
