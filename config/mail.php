<?php

/*
| Mail configuration — AF-2 / D-043.
| Authentication-critical communications (password reset, MFA, account recovery)
| must use IMMEDIATE delivery with SMTP fallback, never the delayed queue.
|
| The default mailer is `failover`, which tries Brevo first and the fallback SMTP
| relay if Brevo is unavailable (D-039 SPOF-04). Auth notifications are NOT
| ShouldQueue, so they send synchronously through this failover transport.
*/

return [

    'default' => env('MAIL_MAILER', 'failover'),

    'mailers' => [

        // Primary transactional relay (Brevo).
        'brevo' => [
            'transport' => 'smtp',
            'host' => env('MAIL_HOST', 'smtp-relay.brevo.com'),
            'port' => (int) env('MAIL_PORT', 587),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
        ],

        // Secondary SMTP relay used when Brevo fails (AF-2 / SPOF-04).
        'backup' => [
            'transport' => 'smtp',
            'host' => env('MAIL_FALLBACK_HOST'),
            'port' => (int) env('MAIL_FALLBACK_PORT', 587),
            'encryption' => env('MAIL_FALLBACK_ENCRYPTION', 'tls'),
            'username' => env('MAIL_FALLBACK_USERNAME'),
            'password' => env('MAIL_FALLBACK_PASSWORD'),
            'timeout' => null,
        ],

        // Failover transport: immediate, with automatic SMTP fallback.
        'failover' => [
            'transport' => 'failover',
            'mailers' => ['brevo', 'backup'],
            'retry_after' => 60,
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

    ],

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'no-reply@ics.example'),
        'name' => env('MAIL_FROM_NAME', 'ICS Enterprise Platform'),
    ],

];
