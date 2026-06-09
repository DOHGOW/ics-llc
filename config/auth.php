<?php

use App\Models\Core\User;

/*
| Auth configuration. The Eloquent user provider points at the ICS core User
| model (App\Models\Core\User) on the core_users table (D-021). The password
| broker uses the password_reset_tokens table (D-041) with a 60-minute expiry
| and 60-second throttle.
*/

return [

    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        // Sanctum guards API requests via the 'sanctum' middleware; it falls
        // back to the 'web' guard provider for token resolution (D-021/D-023).
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => User::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,

];
