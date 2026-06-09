<?php

use Illuminate\Support\Str;

/*
| Cache configuration.
| F-2: the database cache store uses the renamed `sys_cache` table.
| Phase 1 default store is `file` (D-037); VPS uses `redis` (config-only).
*/

return [

    'default' => env('CACHE_STORE', 'file'),

    'stores' => [

        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'sys_cache',          // F-2 — renamed infra table
            'connection' => env('DB_CACHE_CONNECTION'),
            'lock_connection' => env('DB_CACHE_LOCK_CONNECTION'),
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            'lock_path' => storage_path('framework/cache/data'),
        ],

        'apc' => [
            'driver' => 'apc',
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'cache',
            'lock_connection' => 'default',
        ],

    ],

    'prefix' => env('CACHE_PREFIX', Str::slug(env('APP_NAME', 'ics'), '_').'_cache_'),

];
