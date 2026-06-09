<?php

/*
|--------------------------------------------------------------------------
| Community Platform Routes  (Sprint 2 · Wave 4b)
|--------------------------------------------------------------------------
| Decisions: D-023, D-025, D-035, D-046, D-053, D-057, D-058; W4b-1..W4b-6.
|
| REGISTER in bootstrap/app.php withRouting(then: ...):
|     Route::middleware('api')->group(base_path('routes/community.php'));
|
| Access = VISIBILITY (public/authenticated) + OWNER (D-057) — NOT ContentAccessible, NOT the
| four proven mechanisms. Directory is public (visibleTo). Responses go through
| CommunityProfileResource (public-only projection, W4b-1). Cross-module links are
| ownership-validated (W4b-2). Verify/suspend audited under COMMUNITY_MANAGEMENT (W4b-6).
*/

use App\Http\Controllers\Community\Admin\CommunityModerationController;
use App\Http\Controllers\Community\CommunityDirectoryController;
use App\Http\Controllers\Community\CommunityProfileController;
use App\Http\Controllers\Community\CommunityReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/community')->group(function () {

    // Public directory + discoverability (guests see public+active only)
    Route::get('profiles', [CommunityDirectoryController::class, 'index']);
    Route::get('profiles/{profile}', [CommunityDirectoryController::class, 'show']);

    // Authenticated
    Route::middleware('auth:sanctum')->group(function () {
        // Owner-scoped profile
        Route::get('my/profile', [CommunityProfileController::class, 'mine']);
        Route::post('profiles', [CommunityProfileController::class, 'store']);
        Route::put('profiles/{profile}', [CommunityProfileController::class, 'update']);
        Route::post('profiles/{profile}/endorse', [CommunityProfileController::class, 'endorse']);

        // Staff moderation (audited)
        Route::post('admin/profiles/{profile}/verify', [CommunityModerationController::class, 'verify']);
        Route::post('admin/profiles/{profile}/status', [CommunityModerationController::class, 'changeStatus']);

        // Analytics
        Route::get('reports', [CommunityReportController::class, 'index']);
    });
});
