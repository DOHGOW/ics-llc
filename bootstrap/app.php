<?php

use App\Http\Middleware\RequireMfaForAdmins;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Auth\AuthenticationException;
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
        // NOTE: do NOT call config() here — this closure runs via afterResolving(HttpKernel)
        // BEFORE LoadConfiguration binds the `config` repository, so config() throws
        // BindingResolutionException ("Target class [config] does not exist") on real HTTP
        // requests (invisible to the test harness, which pre-bootstraps config). Use a literal;
        // config-driven proxy ranges (D-048) must be applied where config is available
        // (a provider boot() or a request-time TrustProxies middleware), not at build time.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // API-first (D-023): an unauthenticated request to an api/* route must return 401 JSON,
        // never redirect to a `login` route (none exists) — that redirect throws
        // RouteNotFoundException ("Route [login] not defined") = HTTP 500 for non-JSON clients.
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => $e->getMessage()], 401);
            }
        });
    })->create();
