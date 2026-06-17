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

        // API-first (D-023): never redirect unauthenticated guests to a `login` route (none exists).
        // The Authenticate middleware eagerly evaluates route('login') for non-JSON requests, which
        // throws RouteNotFoundException = 500; returning null + shouldRenderJsonWhen (below) yields a
        // direct 401 JSON for api/* instead.
        $middleware->redirectGuestsTo(fn () => null);

        // Trusted proxies (T-9.4 / D-048). VERIFIED infrastructure: LiteSpeed sets REMOTE_ADDR to the
        // real client IP and HTTPS=on directly — no Cloudflare, no X-Forwarded-For/Proto. So trust NO
        // proxies: there is no legitimate forwarding layer, and trusting X-Forwarded-* would only let
        // clients spoof their IP (audit/rate-limit integrity, D-046) or scheme. If a real edge
        // (Cloudflare/LB) is introduced later, trust its SPECIFIC ranges here (not '*').
        // NOTE: do NOT call config() here — this closure runs via afterResolving(HttpKernel) BEFORE
        // LoadConfiguration binds `config`, which would throw BindingResolutionException on real HTTP.
        $middleware->trustProxies(at: []);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // API-first (D-023): force JSON exception rendering for api/* so an unauthenticated request
        // returns 401 JSON DIRECTLY — never a redirect to a `login` route (none exists), which would
        // throw RouteNotFoundException ("Route [login] not defined") = HTTP 500 for non-JSON clients.
        // Handler::unauthenticated() consults shouldRenderJsonWhen (verified in framework source).
        $exceptions->shouldRenderJsonWhen(
            fn ($request, Throwable $e) => $request->is('api/*') || $request->expectsJson()
        );
    })->create();
