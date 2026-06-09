<?php

namespace App\Http\Controllers\Auth;

use App\Events\Core\PasswordChanged;
use App\Http\Controllers\Controller;
use App\Services\Auth\PasswordRules;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

/**
 * Password recovery (D-041 / D-006 / T-4.3).
 *
 * Security: no account enumeration (uniform response on forgot); reset token is
 * hashed in password_reset_tokens and expires per config; new password passes
 * the full policy + HIBP (PasswordRules); all API tokens revoked on reset; reset
 * email is sent immediately via the failover mailer (AF-2).
 */
class PasswordResetController extends Controller
{
    public function forgot(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        // Fire the broker but ALWAYS return a generic response (no enumeration).
        Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => __('If an account matches that email, a reset link has been sent.'),
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRules::default()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) {
                // 'hashed' cast bcrypts the value on save.
                $user->forceFill(['password' => $password])->save();

                // Revoke all API tokens on credential change (E-CORE-004).
                $user->tokens()->delete();

                event(new PasswordReset($user));
                event(new PasswordChanged($user, request()->ip())); // → immutable audit
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => __('Your password has been reset.')])
            : response()->json(['message' => __('This reset link is invalid or has expired.')], 422);
    }
}
