<?php

use Illuminate\Support\Facades\Route;

/*
| Web routes. The platform is API-first (D-023); Blade is used for the public site
| and authenticated portals in later sprints. The SetLocale middleware (web group)
| sets the locale for any Blade response. Health check is at /up (bootstrap).
*/

Route::get('/', fn () => response('ICS Enterprise Platform'));
