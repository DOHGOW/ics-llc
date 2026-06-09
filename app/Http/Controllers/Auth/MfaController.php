<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\MfaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * MFA / TOTP management (D-039 / T-4.5 / AF-1 / AF-3).
 *
 * Enrol returns a secret + QR (shown once). Confirm enables MFA and returns the
 * recovery codes ONCE (thereafter only hashes are stored). Disable requires a
 * valid current code AND password re-authentication.
 */
class MfaController extends Controller
{
    public function __construct(private readonly MfaService $mfa) {}

    public function enrol(Request $request): JsonResponse
    {
        $data = $this->mfa->beginEnrolment($request->user());

        return response()->json([
            'secret' => $data['secret'],
            'qr_svg' => $data['qr_svg'],
            'message' => __('Scan the QR code, then confirm with a generated code.'),
        ]);
    }

    public function confirm(Request $request): JsonResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        $recoveryCodes = $this->mfa->confirmEnrolment($request->user(), $request->string('code'));

        if ($recoveryCodes === null) {
            return response()->json(['message' => __('Invalid verification code.')], 422);
        }

        return response()->json([
            'message' => __('Multi-factor authentication is now enabled.'),
            'recovery_codes' => $recoveryCodes, // shown once — store securely
        ]);
    }

    public function disable(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! Hash::check($request->string('password'), $user->password)
            || ! $this->mfa->verify($user, $request->string('code'))) {
            return response()->json(['message' => __('Verification failed.')], 422);
        }

        $this->mfa->disable($user);

        return response()->json(['message' => __('Multi-factor authentication disabled.')]);
    }
}
