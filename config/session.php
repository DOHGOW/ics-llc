<?php

use Illuminate\Support\Str;

/*
| Session configuration.
| F-2: when SESSION_DRIVER=database, the renamed `sys_sessions` table is used.
| Phase 1 default is `file` (M-1 — spares scarce DB connections, LIM-08).
| D-039: cookies are httpOnly + Secure + SameSite=Strict; lifetime 120 min.
*/

return [

    'driver' => env('SESSION_DRIVER', 'file'),
    'lifetime' => (int) env('SESSION_LIFETIME', 120),
    'expire_on_close' => false,
    'encrypt' => false,
    'files' => storage_path('framework/sessions'),

    'connection' => env('SESSION_CONNECTION'),
    'table' => 'sys_sessions',          // F-2 — renamed infra table

    'store' => env('SESSION_STORE'),
    'lottery' => [2, 100],

    'cookie' => env('SESSION_COOKIE', Str::slug(env('APP_NAME', 'ics'), '_').'_session'),
    'path' => '/',
    'domain' => env('SESSION_DOMAIN'),
    'secure' => env('SESSION_SECURE_COOKIE', true),    // D-039
    'http_only' => true,                                // D-039
    'same_site' => env('SESSION_SAME_SITE', 'strict'),  // D-039
    'partitioned' => false,

];
