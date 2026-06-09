<?php

namespace App\Services\Auth;

use Illuminate\Validation\Rules\Password;

/**
 * Central password policy (D-039 / T-4.3).
 *
 * - Minimum 12 characters, mixed case, numbers, symbols.
 * - uncompromised(): HIBP k-anonymity breach check (Laravel built-in — no full
 *   hash or password ever leaves the server; only a 5-char SHA-1 prefix).
 *
 * Bcrypt cost is configured in config/hashing.php (cost 12). Password history
 * (no-reuse) is enforced in the reset/change flow (T-4.3) against prior hashes.
 */
class PasswordRules
{
    public static function default(): Password
    {
        return Password::min(12)
            ->mixedCase()
            ->numbers()
            ->symbols()
            ->uncompromised();
    }
}
