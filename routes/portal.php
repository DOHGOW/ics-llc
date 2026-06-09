<?php

/*
|--------------------------------------------------------------------------
| Portal Routes  (Sprint 2 · Wave 2)
|--------------------------------------------------------------------------
| Decisions: D-023, D-046, D-050, D-055, D-056; W2-1/W2-3/W2-4/W2-5.
|
| REGISTER in bootstrap/app.php withRouting(then: ...):
|     Route::middleware('api')->group(base_path('routes/portal.php'));
|
| ORG-OWNED portals. All routes behind auth:sanctum; AccountScope (Layer 1) + OrgOwnedPolicy
| (Layer 2) isolate by account. Child resources are nested + scopeBindings (W2-1 parent
| isolation). Lifecycle/financial events audited under portal_management (D-056).
*/

use App\Http\Controllers\Client\DeliverableController;
use App\Http\Controllers\Client\MilestoneController;
use App\Http\Controllers\Client\ProjectController;
use App\Http\Controllers\Client\TicketController;
use App\Http\Controllers\Client\TicketReplyController;
use App\Http\Controllers\Partner\AgreementController;
use App\Http\Controllers\Partner\PartnerDashboardController;
use App\Http\Controllers\Partner\PartnerProfileController;
use App\Http\Controllers\Partner\ReferralController;
use Illuminate\Support\Facades\Route;

// ── Client Portal ───────────────────────────────────────────────────────────
Route::prefix('api/v1/client')->middleware('auth:sanctum')->group(function () {

    Route::get('projects', [ProjectController::class, 'index']);
    Route::post('projects', [ProjectController::class, 'store']);
    Route::get('projects/{project}', [ProjectController::class, 'show']);
    Route::put('projects/{project}', [ProjectController::class, 'update']);
    Route::post('projects/{project}/status', [ProjectController::class, 'changeStatus']);

    // Children — nested + scoped to the parent project (W2-1)
    Route::scopeBindings()->group(function () {
        Route::get('projects/{project}/milestones', [MilestoneController::class, 'index']);
        Route::post('projects/{project}/milestones', [MilestoneController::class, 'store']);
        Route::put('projects/{project}/milestones/{milestone}', [MilestoneController::class, 'update']);

        Route::get('projects/{project}/deliverables', [DeliverableController::class, 'index']);
        Route::post('projects/{project}/deliverables', [DeliverableController::class, 'store']);
        Route::post('projects/{project}/deliverables/{deliverable}/status', [DeliverableController::class, 'changeStatus']);
        Route::get('projects/{project}/deliverables/{deliverable}/download', [DeliverableController::class, 'download']);
    });

    Route::get('tickets', [TicketController::class, 'index']);
    Route::post('tickets', [TicketController::class, 'store']);
    Route::get('tickets/{ticket}', [TicketController::class, 'show']);
    Route::post('tickets/{ticket}/resolve', [TicketController::class, 'resolve']);
    Route::post('tickets/{ticket}/replies', [TicketReplyController::class, 'store']); // child (W2-1)
});

// ── Partner Portal ──────────────────────────────────────────────────────────
Route::prefix('api/v1/partner')->middleware('auth:sanctum')->group(function () {

    Route::get('dashboard', [PartnerDashboardController::class, 'index']);

    Route::get('profiles', [PartnerProfileController::class, 'index']);
    Route::post('profiles', [PartnerProfileController::class, 'store']); // staff onboarding (D-055)
    Route::get('profiles/{profile}', [PartnerProfileController::class, 'show']);
    Route::put('profiles/{profile}', [PartnerProfileController::class, 'update']);
    Route::post('profiles/{profile}/status', [PartnerProfileController::class, 'changeStatus']); // staff

    Route::get('referrals', [ReferralController::class, 'index']);
    Route::post('referrals', [ReferralController::class, 'store']);
    Route::get('referrals/{referral}', [ReferralController::class, 'show']);
    Route::post('referrals/{referral}/qualify', [ReferralController::class, 'qualify']);           // staff (W2-3 seam)
    Route::post('referrals/{referral}/stage', [ReferralController::class, 'changeStage']);          // staff
    Route::post('referrals/{referral}/commission', [ReferralController::class, 'recordCommission']); // staff (HIGH audit)
    Route::post('referrals/{referral}/commission/pay', [ReferralController::class, 'payCommission']); // staff (HIGH audit)

    Route::get('agreements', [AgreementController::class, 'index']);
    Route::post('agreements', [AgreementController::class, 'store']);
    Route::post('agreements/{agreement}/sign', [AgreementController::class, 'sign']);              // HIGH audit
    Route::get('agreements/{agreement}/download', [AgreementController::class, 'download']);        // W2-5
});
