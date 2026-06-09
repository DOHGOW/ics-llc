<?php

/*
|--------------------------------------------------------------------------
| Authentication Routes  (Sprint 1 · Task 4)
|--------------------------------------------------------------------------
| Decisions: D-021, D-023, D-039, D-041, D-006.
|
| REGISTER in bootstrap/app.php withRouting(then: function () {
|     Route::middleware('api')->group(base_path('routes/auth.php'));
| }); — the framework skeleton wiring (one line). See README.
|
| Throttle:6,1 = coarse per-IP rate limit on unauthenticated endpoints (T-9.2);
| the LockoutService provides the credential-specific lockout (T-4.4).
*/

use App\Http\Controllers\Admin\RoleManagementController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\MfaController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\Profile\DataPrivacyController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/auth')->group(function () {

    // Public (named rate limiters — T-9.2).
    Route::post('register', [RegistrationController::class, 'register'])->middleware('throttle:public-forms'); // R-5
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('forgot-password', [PasswordResetController::class, 'forgot'])->middleware('throttle:password-reset');
    Route::post('reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:password-reset');

    // Authenticated endpoints.
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);

        Route::post('mfa/enrol', [MfaController::class, 'enrol'])->middleware('throttle:mfa');
        Route::post('mfa/confirm', [MfaController::class, 'confirm'])->middleware('throttle:mfa');
        Route::post('mfa/disable', [MfaController::class, 'disable'])->middleware('throttle:mfa');
    });
});

Route::prefix('api/v1/profile')->middleware('auth:sanctum')->group(function () {
    Route::get('data-export', [DataPrivacyController::class, 'export']);   // E-CORE-009
    Route::post('delete', [DataPrivacyController::class, 'destroy']);      // E-CORE-010
});

// ── Admin: User Management + Role Management (Task 7) ──────────────────────
// auth:sanctum + admin MFA enforced (RequireMfaForAdmins alias, registered in
// bootstrap/app.php). UserPolicy/RoleAssignmentService enforce all invariants.
Route::prefix('api/v1/admin')->middleware(['auth:sanctum', 'mfa.admin'])->group(function () {

    Route::get('users', [UserManagementController::class, 'index']);
    Route::post('users', [UserManagementController::class, 'store']);
    Route::get('users/{user}', [UserManagementController::class, 'show']);
    Route::put('users/{user}', [UserManagementController::class, 'update']);
    Route::post('users/{user}/approve', [UserManagementController::class, 'approve']);
    Route::post('users/{user}/suspend', [UserManagementController::class, 'suspend']);
    Route::post('users/{user}/reactivate', [UserManagementController::class, 'reactivate']);
    Route::post('users/{user}/deactivate', [UserManagementController::class, 'deactivate']);
    Route::delete('users/{user}', [UserManagementController::class, 'destroy']);

    Route::post('users/{user}/roles/assign', [RoleManagementController::class, 'assign']);
    Route::post('users/{user}/roles/revoke', [RoleManagementController::class, 'revoke']);
    Route::post('users/{user}/escalation/request', [RoleManagementController::class, 'escalationRequest']);
    Route::post('escalation/{approval}/approve', [RoleManagementController::class, 'escalationApprove']);
    Route::post('escalation/{approval}/reject', [RoleManagementController::class, 'escalationReject']);
});

// Named route used to build the password-reset link (front-end handles the view).
Route::get('reset-password/{token}', fn () => null)->name('password.reset');
