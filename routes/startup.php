<?php

/*
|--------------------------------------------------------------------------
| Startup Hub Routes  (Sprint 3 · Wave 5A)
|--------------------------------------------------------------------------
| Decisions: D-023, D-025, D-046, D-053, D-061, D-062, D-063, D-064; C-1.
|
| REGISTER in bootstrap/app.php withRouting(then: ...):
|     Route::middleware('api')->group(base_path('routes/startup.php'));
|
| Access = PARTICIPATION family (StartupAccessService, D-061) — founder/team/program; NOT
| AccountScope/ContentAccessible. Public directory is public-projection only (C-1/M-1).
| Ownership endpoints are gated (founder/admin/staff, C-1). Governance events → STARTUP_MANAGEMENT.
*/

use App\Http\Controllers\Startup\Admin\StartupGovernanceController;
use App\Http\Controllers\Startup\MilestoneController;
use App\Http\Controllers\Startup\OwnershipController;
use App\Http\Controllers\Startup\ProgramController;
use App\Http\Controllers\Startup\StartupController;
use App\Http\Controllers\Startup\StartupReportController;
use App\Http\Controllers\Startup\TeamController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/startups')->group(function () {

    // Public directory (public projection only)
    Route::get('/', [StartupController::class, 'index']);
    Route::get('{startup}', [StartupController::class, 'show']);
    Route::get('programs/list', [ProgramController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [StartupController::class, 'store']);
        Route::get('my/startups', [StartupController::class, 'mine']);
        Route::put('{startup}', [StartupController::class, 'update']);

        // Team + founder governance (H-2)
        Route::get('{startup}/team', [TeamController::class, 'index']);
        Route::post('{startup}/team/invite', [TeamController::class, 'invite']);
        Route::post('team/invitations/{token}/accept', [TeamController::class, 'accept']);
        Route::delete('{startup}/team/{member}', [TeamController::class, 'remove']);
        Route::post('{startup}/team/transfer-ownership', [TeamController::class, 'transferOwnership']);

        // Ownership / cap-table (gated, C-1)
        Route::get('{startup}/ownership', [OwnershipController::class, 'show']);
        Route::post('{startup}/ownership', [OwnershipController::class, 'set']);

        // Milestones (internal)
        Route::get('{startup}/milestones', [MilestoneController::class, 'index']);
        Route::post('{startup}/milestones', [MilestoneController::class, 'store']);
        Route::put('{startup}/milestones/{milestone}', [MilestoneController::class, 'update']);

        // Programs
        Route::post('programs', [ProgramController::class, 'store']);
        Route::post('{startup}/programs/enrol', [ProgramController::class, 'enrol']);

        // ICS governance (audited)
        Route::post('admin/{startup}/verify', [StartupGovernanceController::class, 'verify']);
        Route::post('admin/{startup}/suspend', [StartupGovernanceController::class, 'suspend']);
        Route::post('admin/{startup}/reactivate', [StartupGovernanceController::class, 'reactivate']);
        Route::post('admin/{startup}/graduate', [StartupGovernanceController::class, 'graduate']);
        Route::post('admin/{startup}/lifecycle', [StartupGovernanceController::class, 'setLifecycle']);
        Route::post('admin/{startup}/mentors', [StartupGovernanceController::class, 'assignMentor']);

        // Analytics
        Route::get('reports/analytics', [StartupReportController::class, 'index']);
    });
});
