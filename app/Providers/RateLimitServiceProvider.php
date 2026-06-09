<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * Named rate limiters (T-9.2 / D-039). Limits are config-driven (config/security.php)
 * so they are tunable per environment via .env (D-037). Apply with the throttle
 * middleware: throttle:login, throttle:password-reset, throttle:mfa,
 * throttle:public-forms, throttle:api.
 *
 * Register in bootstrap/providers.php.
 */
class RateLimitServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('login', fn (Request $r) => Limit::perMinute(
            (int) config('security.rate_limits.login', 6)
        )->by($this->loginKey($r)));

        RateLimiter::for('password-reset', fn (Request $r) => Limit::perMinute(
            (int) config('security.rate_limits.password_reset', 6)
        )->by($this->loginKey($r)));

        RateLimiter::for('mfa', fn (Request $r) => Limit::perMinute(
            (int) config('security.rate_limits.mfa', 10)
        )->by($this->userOrIp($r)));

        RateLimiter::for('public-forms', fn (Request $r) => Limit::perMinute(
            (int) config('security.rate_limits.public_forms', 20)
        )->by($r->ip()));

        RateLimiter::for('api', fn (Request $r) => Limit::perMinute(
            (int) config('security.rate_limits.api', 120)
        )->by($this->userOrIp($r)));
    }

    /** Keyed on email + IP so attacks against one account don't lock the whole IP out unfairly. */
    private function loginKey(Request $request): string
    {
        return sha1(strtolower((string) $request->input('email')).'|'.$request->ip());
    }

    private function userOrIp(Request $request): string
    {
        return optional($request->user())->id
            ? 'user:'.$request->user()->id
            : 'ip:'.$request->ip();
    }
}
