<?php

namespace App\Http\Controllers\Auth;

use App\Events\Core\UserLoggedIn;
use App\Events\Core\UserLoggedOut;
use App\Http\Controllers\Controller;
use App\Models\Core\User;
use App\Services\Auth\LockoutService;
use App\Services\Auth\MfaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Sanctum API authentication (D-021 / D-023 / T-4.2).
 *
 * Security: lockout (T-4.4), no account enumeration (uniform invalid-credentials
 * response), MFA challenge for enrolled users (T-4.5), token issuance with
 * expiry. Audit events (E-CORE-002) are wired in Task 6.
 */
class AuthController extends Controller
{
    public function __construct(
        private readonly LockoutService $lockout,
        private readonly MfaService $mfa,
    ) {}

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'totp' => ['nullable', 'string'],
        ]);

        $ip = (string) $request->ip();

        if ($this->lockout->tooManyAttempts($data['email'], $ip)) {
            return response()->json([
                'message' => __('Too many attempts. Please try again later.'),
                'retry_after' => $this->lockout->availableIn($data['email'], $ip),
            ], 429);
        }

        $user = User::where('email', $data['email'])->first();

        // Uniform failure path — no enumeration of existence vs wrong password.
        if (! $user || $user->status !== 'active' || ! Hash::check($data['password'], $user->password)) {
            $this->lockout->recordFailure($data['email'], $ip, $request->userAgent());

            return response()->json(['message' => __('Invalid credentials.')], 401);
        }

        // MFA challenge for enrolled users (TOTP or single-use recovery code).
        if ($user->mfa_enabled) {
            $code = $data['totp'] ?? null;

            $passed = $code
                && ($this->mfa->verify($user, $code) || $this->mfa->verifyRecoveryCode($user, $code));

            if (! $passed) {
                return response()->json([
                    'message' => __('A valid MFA code is required.'),
                    'mfa_required' => true,
                ], 401);
            }
        }

        $this->lockout->clear($data['email'], $ip);

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ])->save();

        $expiresAt = now()->addMinutes((int) config('sanctum.expiration')
            ?: (int) env('SANCTUM_TOKEN_EXPIRATION', 1440));

        $token = $user->createToken('api', ['*'], $expiresAt)->plainTextToken;

        event(new UserLoggedIn($user, $ip, $request->userAgent()));

        return response()->json([
            'token' => $token,
            'user' => $user->only(['id', 'name', 'email']),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();

        event(new UserLoggedOut($user, $request->ip()));

        return response()->json(['message' => __('Logged out.')]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => $user->only(['id', 'name', 'email', 'locale', 'mfa_enabled']),
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }
}
