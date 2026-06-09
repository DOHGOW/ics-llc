<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies security response headers from config/security.php (T-9.1 / D-039).
 * Config-driven (D-037); HSTS only over HTTPS. Does not affect content/markup, so
 * accessibility (D-028) is unaffected — CSP is tuned to permit first-party assets.
 *
 * Register globally in bootstrap/app.php (web + api).
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $cfg = (array) config('security.headers', []);

        // HSTS — only meaningful/served over HTTPS.
        $hsts = $cfg['hsts'] ?? [];
        if (! empty($hsts['enabled']) && $request->secure()) {
            $value = 'max-age='.(int) ($hsts['max_age'] ?? 31536000);
            if (! empty($hsts['include_subdomains'])) {
                $value .= '; includeSubDomains';
            }
            if (! empty($hsts['preload'])) {
                $value .= '; preload';
            }
            $response->headers->set('Strict-Transport-Security', $value);
        }

        if (! empty($cfg['csp'])) {
            $response->headers->set('Content-Security-Policy', $cfg['csp']);
        }

        $response->headers->set('X-Frame-Options', $cfg['frame_options'] ?? 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', $cfg['content_type_options'] ?? 'nosniff');
        $response->headers->set('Referrer-Policy', $cfg['referrer_policy'] ?? 'strict-origin-when-cross-origin');

        if (! empty($cfg['permissions_policy'])) {
            $response->headers->set('Permissions-Policy', $cfg['permissions_policy']);
        }

        foreach ((array) config('security.remove_headers', []) as $header) {
            $response->headers->remove($header);
        }

        return $response;
    }
}
