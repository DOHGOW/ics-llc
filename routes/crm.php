<?php

/*
|--------------------------------------------------------------------------
| CRM Routes  (Sprint 2 · Wave 1d)
|--------------------------------------------------------------------------
| Decisions: D-012, D-023, D-053, D-054.
|
| REGISTER in bootstrap/app.php withRouting(then: ...):
|     Route::middleware('api')->group(base_path('routes/crm.php'));
|
| INTERNAL-ONLY CRM. All routes behind auth:sanctum; every action is permission-gated
| (crm.*) AND assignment-scoped (visibleTo / visibleToUser, D-053). NO AccountScope.
| Stage/assignment/conversion changes are audited under crm_management (D-054).
*/

use App\Http\Controllers\Crm\AccountController;
use App\Http\Controllers\Crm\ActivityController;
use App\Http\Controllers\Crm\ContactController;
use App\Http\Controllers\Crm\CrmReportController;
use App\Http\Controllers\Crm\LeadController;
use App\Http\Controllers\Crm\OpportunityController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/crm')->middleware('auth:sanctum')->group(function () {

    // Accounts
    Route::get('accounts', [AccountController::class, 'index']);
    Route::post('accounts', [AccountController::class, 'store']);
    Route::get('accounts/{account}', [AccountController::class, 'show']);
    Route::put('accounts/{account}', [AccountController::class, 'update']);
    Route::post('accounts/{account}/assign', [AccountController::class, 'assign']);
    Route::delete('accounts/{account}', [AccountController::class, 'destroy']);

    // Contacts
    Route::get('contacts', [ContactController::class, 'index']);
    Route::post('contacts', [ContactController::class, 'store']);
    Route::get('contacts/{contact}', [ContactController::class, 'show']);
    Route::put('contacts/{contact}', [ContactController::class, 'update']);
    Route::delete('contacts/{contact}', [ContactController::class, 'destroy']);

    // Leads
    Route::get('leads', [LeadController::class, 'index']);
    Route::post('leads', [LeadController::class, 'store']);
    Route::get('leads/{lead}', [LeadController::class, 'show']);
    Route::put('leads/{lead}', [LeadController::class, 'update']);
    Route::post('leads/{lead}/stage', [LeadController::class, 'changeStage']);
    Route::post('leads/{lead}/assign', [LeadController::class, 'assign']);
    Route::post('leads/{lead}/convert', [LeadController::class, 'convert']);
    Route::delete('leads/{lead}', [LeadController::class, 'destroy']);

    // Opportunities
    Route::get('opportunities', [OpportunityController::class, 'index']);
    Route::post('opportunities', [OpportunityController::class, 'store']);
    Route::get('opportunities/{opportunity}', [OpportunityController::class, 'show']);
    Route::put('opportunities/{opportunity}', [OpportunityController::class, 'update']);
    Route::post('opportunities/{opportunity}/stage', [OpportunityController::class, 'changeStage']);
    Route::post('opportunities/{opportunity}/assign', [OpportunityController::class, 'assign']);
    Route::delete('opportunities/{opportunity}', [OpportunityController::class, 'destroy']);

    // Activities (polymorphic timeline; notes = type 'note', W1d-2)
    Route::get('activities', [ActivityController::class, 'index']);
    Route::post('activities', [ActivityController::class, 'store']);
    Route::post('activities/{activity}/complete', [ActivityController::class, 'complete']);
    Route::delete('activities/{activity}', [ActivityController::class, 'destroy']);

    // Pipeline reporting (analytics hook, D-025)
    Route::get('reports/pipeline', [CrmReportController::class, 'pipeline']);
});
