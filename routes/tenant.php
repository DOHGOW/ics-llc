<?php

/*
|--------------------------------------------------------------------------
| Tenant / Franchise Administration Routes  (Wave FT-1)
|--------------------------------------------------------------------------
| Decisions: D-023, D-046, D-076, D-079.
|
| REGISTER in bootstrap/app.php withRouting(then: ...):
|     Route::middleware('api')->group(base_path('routes/tenant.php'));
|
| HQ-only (Platform/Super Admin) tenant provisioning + lifecycle. All mutations are audited HIGH
| under TENANT_MANAGEMENT. core_tenants is not tenant-scoped (it IS the tenant).
*/

use App\Http\Controllers\Admin\TenantAdminController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/admin/tenants')->middleware(['auth:sanctum', 'mfa.admin'])->group(function () {
    Route::get('/', [TenantAdminController::class, 'index']);
    Route::post('/', [TenantAdminController::class, 'store']);
    Route::post('{tenant}/suspend', [TenantAdminController::class, 'suspend']);
    Route::post('{tenant}/activate', [TenantAdminController::class, 'activate']);
    Route::post('{tenant}/transfer-ownership', [TenantAdminController::class, 'transferOwnership']);
    Route::post('{tenant}/elevate-admin', [TenantAdminController::class, 'elevateAdmin']);
    Route::post('{tenant}/residency', [TenantAdminController::class, 'changeResidency']);
});
