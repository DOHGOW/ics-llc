<?php

/*
| Security configuration (Task 9 / D-039). Everything here is configuration-driven
| (requirement 4) so policy can be tuned per environment via .env with no code
| change (D-037).
|
| CSP note: the default policy is strict. Alpine.js's standard build evaluates
| expressions and needs either 'unsafe-eval' OR the @alpinejs/csp build. Keep the
| CSP strict and switch to the CSP build (preferred) — see SECURITY_MIDDLEWARE_REVIEW
| finding T9-1.
*/

return [

    'headers' => [

        'hsts' => [
            'enabled' => (bool) env('SECURITY_HSTS_ENABLED', true),
            'max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 31536000), // 1 year
            'include_subdomains' => (bool) env('SECURITY_HSTS_INCLUDE_SUBDOMAINS', true),
            'preload' => (bool) env('SECURITY_HSTS_PRELOAD', false),
        ],

        'csp' => env(
            'SECURITY_CSP',
            "default-src 'self'; ".
            "base-uri 'self'; ".
            "object-src 'none'; ".
            "frame-ancestors 'self'; ".
            "form-action 'self'; ".
            "img-src 'self' data:; ".
            "font-src 'self'; ".
            "style-src 'self'; ".
            "script-src 'self'; ".
            "connect-src 'self'"
        ),

        'frame_options' => env('SECURITY_FRAME_OPTIONS', 'SAMEORIGIN'),
        'content_type_options' => 'nosniff',
        'referrer_policy' => env('SECURITY_REFERRER_POLICY', 'strict-origin-when-cross-origin'),
        'permissions_policy' => env(
            'SECURITY_PERMISSIONS_POLICY',
            'geolocation=(), microphone=(), camera=(), payment=(), usb=()'
        ),
    ],

    // Response headers to strip (also set expose_php=Off in php.ini — spike S7).
    'remove_headers' => ['X-Powered-By', 'Server'],

    // Named rate limits (requests/minute) — applied via the throttle middleware.
    'rate_limits' => [
        'login' => (int) env('RL_LOGIN', 6),
        'password_reset' => (int) env('RL_PASSWORD_RESET', 6),
        'mfa' => (int) env('RL_MFA', 10),
        'public_forms' => (int) env('RL_PUBLIC_FORMS', 20),
        'api' => (int) env('RL_API', 120),
    ],

    // Trusted proxies for correct client IP behind Cloudflare/load balancers.
    // Register in bootstrap/app.php ->trustProxies(). '*' trusts all (use only
    // when the origin is reachable solely via the proxy).
    'trusted_proxies' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('TRUSTED_PROXIES', ''))
    ))),

];
