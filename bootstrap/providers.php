<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\RateLimitServiceProvider;
use App\Providers\TenancyServiceProvider;

/*
| Service provider registration (Laravel 11). Consolidates the Sprint 1 providers:
|  - AppServiceProvider        (default container bindings)
|  - AuthServiceProvider       (Gate::before Super-Admin-only + policies, D-021/D-044)
|  - EventServiceProvider      (audit + security-alert subscribers, D-046/R-7)
|  - RateLimitServiceProvider  (named limiters, T-9.2)
*/

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    EventServiceProvider::class,
    RateLimitServiceProvider::class,
    TenancyServiceProvider::class, // TenantScope activation (D-076)
];
