<?php

/*
|--------------------------------------------------------------------------
| Generic Program Routes — Incubator + Accelerator share this  (Sprint 3 · Wave 5B)
|--------------------------------------------------------------------------
| Decisions: D-023, D-025, D-046, D-053, D-063, D-065, D-066, D-067; H-1/H-2/H-3, M-1/M-2.
|
| REGISTER in bootstrap/app.php withRouting(then: ...):
|     Route::middleware('api')->group(base_path('routes/program.php'));
|
| ONE Program Architecture (D-065). Incubator instantiates it (type=incubator); Accelerator (5c)
| specializes it — NO duplicate routes. Access = ProgramParticipationService (participation
| family). Governed intake (M-1, no bypass). Governance events → PROGRAM_MANAGEMENT (D-066).
*/

use App\Http\Controllers\Program\CohortController;
use App\Http\Controllers\Program\EventController;
use App\Http\Controllers\Program\IntakeController;
use App\Http\Controllers\Program\ParticipationController;
use App\Http\Controllers\Program\ProgramGovernanceController;
use App\Http\Controllers\Program\ReadinessController;
use App\Http\Controllers\Program\ShowcaseController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/programs')->middleware('auth:sanctum')->group(function () {

    // Cohorts (intake cycles) + coordinators — ICS program staff
    Route::get('{program}/cohorts', [CohortController::class, 'index']);
    Route::post('{program}/cohorts', [CohortController::class, 'store']);
    Route::post('cohorts/{cohort}/open-intake', [CohortController::class, 'openIntake']);
    Route::post('cohorts/{cohort}/close', [CohortController::class, 'close']);
    Route::post('cohorts/{cohort}/archive', [CohortController::class, 'archive']);
    Route::post('cohorts/{cohort}/coordinators', [CohortController::class, 'assignCoordinator']);

    // Governed intake (M-1) — apply → review → accept/reject
    Route::post('cohorts/{cohort}/apply', [IntakeController::class, 'apply']);
    Route::post('enrollments/{enrollment}/review', [IntakeController::class, 'review']);
    Route::post('enrollments/{enrollment}/accept', [IntakeController::class, 'accept']);
    Route::post('enrollments/{enrollment}/reject', [IntakeController::class, 'reject']);

    // Active-participation governance (D-067)
    Route::post('enrollments/{enrollment}/graduate', [ParticipationController::class, 'graduate']);
    Route::post('enrollments/{enrollment}/graduation/reverse', [ParticipationController::class, 'reverseGraduation']);
    Route::post('enrollments/{enrollment}/withdraw', [ParticipationController::class, 'withdraw']);
    Route::post('enrollments/{enrollment}/remove', [ParticipationController::class, 'forceRemove']);

    // Program-level governance (HIGH) + analytics
    Route::post('{program}/suspend', [ProgramGovernanceController::class, 'suspend']);
    Route::post('{program}/reinstate', [ProgramGovernanceController::class, 'reinstate']);
    Route::post('{program}/terminate', [ProgramGovernanceController::class, 'terminate']);
    Route::post('{program}/archive', [ProgramGovernanceController::class, 'archive']);
    Route::get('reports/analytics', [ProgramGovernanceController::class, 'analytics']);

    // ── Generic Program Events layer (Wave 5C / D-068 / M-1) — ONE mechanism; reusable.
    // demo_day / pitch_event / showcase / readiness_review / graduation_showcase + judging/scoring.
    Route::get('cohorts/{cohort}/events', [EventController::class, 'index']);
    Route::post('cohorts/{cohort}/events', [EventController::class, 'store']);
    Route::post('events/{event}/judges', [EventController::class, 'assignJudge']);
    Route::post('events/{event}/scores', [EventController::class, 'submitScore']);   // judge
    Route::post('events/{event}/finalize', [EventController::class, 'finalize']);
    Route::get('events/{event}/ranking', [EventController::class, 'ranking']);

    // Investor Showcase (H-1 exposure/discovery) + Readiness signal (H-3)
    Route::get('cohorts/{cohort}/showcase', [ShowcaseController::class, 'exposure']);
    Route::get('cohorts/{cohort}/readiness/{startup}', [ReadinessController::class, 'show']);
});
