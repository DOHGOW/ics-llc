<?php

use App\Http\Middleware\RequireMfaForAdmins;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

/*
| Application bootstrap (Laravel 11). Consolidates every Sprint 1 integration item:
|  - routes/auth.php loaded under the api group (Tasks 4–9 endpoints)
|  - SecurityHeaders applied globally (T-9.1)
|  - SetLocale on the web group (R-1 / Task 8)
|  - mfa.admin alias → RequireMfaForAdmins (Task 7)
|  - trusted proxies for Cloudflare (T-9.4)
| Providers are registered in bootstrap/providers.php.
*/

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')->group(base_path('routes/auth.php'));
            Route::middleware('api')->group(base_path('routes/cms.php')); // Wave 1c
            Route::middleware('api')->group(base_path('routes/crm.php')); // Wave 1d
            Route::middleware('api')->group(base_path('routes/portal.php')); // Wave 2
            Route::middleware('api')->group(base_path('routes/library.php')); // Wave 3
            Route::middleware('api')->group(base_path('routes/training.php')); // Wave 4a
            Route::middleware('api')->group(base_path('routes/community.php')); // Wave 4b
            Route::middleware('api')->group(base_path('routes/marketplace.php')); // Wave 4c
            Route::middleware('api')->group(base_path('routes/startup.php')); // Wave 5a
            Route::middleware('api')->group(base_path('routes/program.php')); // Wave 5b
            Route::middleware('api')->group(base_path('routes/tenant.php')); // Wave FT-1
            Route::middleware('api')->group(base_path('routes/billing.php')); // Wave Billing
            Route::middleware('api')->group(base_path('routes/membership.php')); // Wave Membership
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Global: security headers on every response (T-9.1 / D-039).
        $middleware->append(SecurityHeaders::class);

        // Web group: locale detection sets app locale + shares lang/dir (R-1).
        $middleware->web(append: [
            SetLocale::class,
        ]);

        // Route middleware aliases.
        $middleware->alias([
            'mfa.admin' => RequireMfaForAdmins::class,
        ]);

        // Correct client IP behind Cloudflare/LB (T-9.4). Replace '*' with explicit
        // Cloudflare ranges before production (D-048 / T9-3).
        $middleware->trustProxies(at: (array) config('security.trusted_proxies', []));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Default handling; API returns JSON problem responses.
    })->create();
