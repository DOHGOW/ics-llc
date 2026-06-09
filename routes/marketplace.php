<?php

/*
|--------------------------------------------------------------------------
| Opportunity Marketplace Routes  (Sprint 2 · Wave 4c)
|--------------------------------------------------------------------------
| Decisions: D-011, D-023, D-025, D-046, D-057, D-058, D-060.
|
| REGISTER in bootstrap/app.php withRouting(then: ...):
|     Route::middleware('api')->group(base_path('routes/marketplace.php'));
|
| Access = LISTING-STATUS + REVIEW + OWNER/APPLICANT (D-057) — NOT AccountScope, NOT
| ContentAccessible; organisation_id is provenance only. Published listings are public;
| applications are private. Mandatory pre-publication review (no auto-publish). Submit/apply/
| report are throttled (anti-spam, D-060). Governance events → MARKETPLACE_MANAGEMENT.
*/

use App\Http\Controllers\Marketplace\Admin\ModerationController;
use App\Http\Controllers\Marketplace\ApplicationController;
use App\Http\Controllers\Marketplace\ListingController;
use App\Http\Controllers\Marketplace\MarketplaceController;
use App\Http\Controllers\Marketplace\MarketplaceReportController;
use App\Http\Controllers\Marketplace\ReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/marketplace')->group(function () {

    // Public: published + non-expired listings only
    Route::get('listings', [MarketplaceController::class, 'index']);
    Route::get('listings/{listing}', [MarketplaceController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        // Authoring (restricted posting rights) → mandatory review
        Route::post('listings', [ListingController::class, 'store']);
        Route::post('listings/{listing}/submit', [ListingController::class, 'submit'])->middleware('throttle:public-forms');
        Route::get('my/listings', [ListingController::class, 'mine']);

        // Applications (private; duplicate prevented by DB)
        Route::post('listings/{listing}/apply', [ApplicationController::class, 'apply'])->middleware('throttle:public-forms');
        Route::get('my/applications', [ApplicationController::class, 'mine']);
        Route::get('listings/{listing}/applications', [ApplicationController::class, 'forListing']);
        Route::post('applications/{application}/status', [ApplicationController::class, 'changeStatus']);
        Route::get('applications/{application}/attachments/{index}', [ApplicationController::class, 'downloadAttachment']);

        // Abuse reporting (D-060)
        Route::post('listings/{listing}/report', [ReportController::class, 'report'])->middleware('throttle:public-forms');
        Route::get('admin/reports', [ReportController::class, 'index']);
        Route::post('admin/reports/{report}/resolve', [ReportController::class, 'resolve']);

        // Moderation (pre + post publication)
        Route::get('admin/queue', [ModerationController::class, 'queue']);
        Route::post('admin/listings/{listing}/approve', [ModerationController::class, 'approve']);
        Route::post('admin/listings/{listing}/reject', [ModerationController::class, 'reject']);
        Route::post('admin/listings/{listing}/remove', [ModerationController::class, 'remove']);

        // Analytics
        Route::get('reports/analytics', [MarketplaceReportController::class, 'index']);
    });
});
