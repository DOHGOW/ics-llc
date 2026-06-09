<?php

/*
|--------------------------------------------------------------------------
| Membership Routes  (Wave Membership — D-080..D-083 / D-087)
|--------------------------------------------------------------------------
| Membership is a CONSUMER of Billing. SUBSCRIBING/cancelling reuse the Billing endpoints
| (/api/v1/billing/*) — there is NO parallel payment path here. These routes expose the
| membership ENTITLEMENT projection (member self-service) + tenant-aware membership administration
| (plan management, manual entitlement, analytics). Plans catalogue is public (tenant-scoped).
|
| REGISTER in bootstrap/app.php withRouting(then: ...):
|     Route::middleware('api')->group(base_path('routes/membership.php'));
*/

use App\Http\Controllers\Membership\Admin\MembershipAdminController;
use App\Http\Controllers\Membership\MembershipController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/membership')->group(function () {

    // Public: membership plan catalogue (tenant-scoped).
    Route::get('plans', [MembershipController::class, 'plans']);

    Route::middleware('auth:sanctum')->group(function () {
        // Member self-service: live entitlement projection.
        Route::get('status', [MembershipController::class, 'status']);

        // Tenant-aware administration (HQ + Franchise Admin). Manual entitlement + policy = HIGH-audited.
        Route::post('admin/plans', [MembershipAdminController::class, 'storePlan']);
        Route::put('admin/plans/{plan}', [MembershipAdminController::class, 'updatePlan']);
        Route::post('admin/users/{user}/grant', [MembershipAdminController::class, 'grant']);
        Route::post('admin/subscriptions/{subscription}/revoke', [MembershipAdminController::class, 'revoke']);
        Route::get('admin/analytics', [MembershipAdminController::class, 'analytics']);
    });
});
