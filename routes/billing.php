<?php

/*
|--------------------------------------------------------------------------
| Billing Routes  (Wave Billing — substrate for Membership)
|--------------------------------------------------------------------------
| Decisions: D-023, D-031, D-046, D-076, D-084, D-085, D-086.
|
| REGISTER in bootstrap/app.php withRouting(then: ...):
|     Route::middleware('api')->group(base_path('routes/billing.php'));
|
| Webhook is PUBLIC (gateway-called; security = signature, verified in the processor). Plans are
| public (tenant-scoped). Subscribe/cancel are authenticated. Overrides/refunds are HQ + HIGH-audited.
*/

use App\Http\Controllers\Billing\Admin\BillingAdminController;
use App\Http\Controllers\Billing\PlanController;
use App\Http\Controllers\Billing\SubscriptionController;
use App\Http\Controllers\Billing\WebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/billing')->group(function () {

    // Public: gateway webhook (signature-verified) + plan catalogue
    Route::post('webhooks/{gateway}', [WebhookController::class, 'handle']);
    Route::get('plans', [PlanController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('subscribe', [SubscriptionController::class, 'subscribe']);
        Route::get('my/subscriptions', [SubscriptionController::class, 'mine']);
        Route::post('subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel']);

        // HQ admin (HIGH-audited governance)
        Route::post('admin/subscriptions/{subscription}/override', [BillingAdminController::class, 'override']);
        Route::post('admin/subscriptions/{subscription}/reactivate', [BillingAdminController::class, 'reactivate']);
        Route::post('admin/subscriptions/{subscription}/cancel', [BillingAdminController::class, 'adminCancel']);
        Route::post('admin/payments/{payment}/refund', [BillingAdminController::class, 'refund']);
    });
});
