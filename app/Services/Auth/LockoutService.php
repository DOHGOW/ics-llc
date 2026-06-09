<?php

namespace App\Services\Auth;

use App\Events\Core\AccountLocked;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Account lockout (D-039 / E-CORE-005 / T-4.4).
 *
 * - Tracks failed login attempts per (email + IP) without revealing whether the
 *   account exists (no enumeration).
 * - Locks after 5 consecutive failures for 15 minutes.
 * - Dispatches AccountLocked on the threshold (alert + audit wired in Task 6).
 *
 * Backed by the cache store (file on shared hosting; redis on VPS — config-only).
 */
class LockoutService
{
    private int $maxAttempts = 5;

    private int $decaySeconds = 900; // 15 minutes

    private function key(string $email, string $ip): string
    {
        return 'login:'.sha1(Str::lower(trim($email)).'|'.$ip);
    }

    public function tooManyAttempts(string $email, string $ip): bool
    {
        return RateLimiter::tooManyAttempts($this->key($email, $ip), $this->maxAttempts);
    }

    public function availableIn(string $email, string $ip): int
    {
        return RateLimiter::availableIn($this->key($email, $ip));
    }

    public function recordFailure(string $email, string $ip, ?string $userAgent = null): void
    {
        $key = $this->key($email, $ip);
        RateLimiter::hit($key, $this->decaySeconds);

        if (RateLimiter::attempts($key) >= $this->maxAttempts) {
            event(new AccountLocked($email, $ip, RateLimiter::attempts($key), $userAgent));
        }
    }

    public function clear(string $email, string $ip): void
    {
        RateLimiter::clear($this->key($email, $ip));
    }
}
