<?php

use App\Services\Billing\ReconciliationService;
use App\Services\Marketplace\MarketplaceListingService;
use Illuminate\Support\Facades\Schedule;

/*
| Console routes + scheduled tasks (Laravel 11). Referenced by bootstrap/app.php
| withRouting(commands: ...). The scheduler runs via a single system cron entry
| (* * * * * php artisan schedule:run) — see VPS_MIGRATION_CHECKLIST / Hostinger sheet.
*/

// Wave 4c (D-060): auto-expire published marketplace listings past their deadline.
// Daily; the public scope also filters expired lazily, so this is the authoritative sweep.
Schedule::call(function (MarketplaceListingService $service) {
    $service->expireOverdue();
})->dailyAt('00:10')->name('marketplace:expire-listings')->withoutOverlapping();

// Wave Billing (D-084): reconcile lapsed subscriptions (recovers missed webhooks). Idempotent;
// only EXPIRES stale subscriptions — never creates entitlements unsupported by state.
Schedule::call(function (ReconciliationService $service) {
    $service->expireLapsed();
})->hourly()->name('billing:reconcile')->withoutOverlapping();
