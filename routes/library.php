<?php

/*
|--------------------------------------------------------------------------
| Content Library Routes — Knowledge + Research Centers  (Sprint 2 · Wave 3)
|--------------------------------------------------------------------------
| Decisions: D-014, D-023, D-025, D-034, D-036, D-038, D-046, D-051.
|
| REGISTER in bootstrap/app.php withRouting(then: ...):
|     Route::middleware('api')->group(base_path('routes/library.php'));
|
| Tier access via ContentAccessService ONLY (Knowledge=LATERAL D-036, Research=HIERARCHICAL
| D-034; tiers strategy-relative, W3-1). Public reads are open (guest = tier 1); gated body/
| files enforced in resources (W3-3) + gated download endpoints (W2-5). Publish/archive
| audited under module 'knowledge'/'research' (W3-2). Admin routes behind auth:sanctum +
| permission gates.
*/

use App\Http\Controllers\Knowledge\Admin\KnowledgeArticleController;
use App\Http\Controllers\Knowledge\Admin\KnowledgeResourceController;
use App\Http\Controllers\Knowledge\KnowledgeCategoryController;
use App\Http\Controllers\Knowledge\KnowledgeCenterController;
use App\Http\Controllers\Knowledge\KnowledgeReportController;
use App\Http\Controllers\Research\Admin\ResearchAuthorController;
use App\Http\Controllers\Research\Admin\ResearchPublicationController;
use App\Http\Controllers\Research\ResearchCategoryController;
use App\Http\Controllers\Research\ResearchCenterController;
use App\Http\Controllers\Research\ResearchReportController;
use Illuminate\Support\Facades\Route;

// ── Knowledge Center ─────────────────────────────────────────────────────────
Route::prefix('api/v1/knowledge')->group(function () {
    // Public read (guest = tier 1; entitlement marked per row, W3-3)
    Route::get('categories', [KnowledgeCategoryController::class, 'index']);
    Route::get('articles', [KnowledgeCenterController::class, 'articles']);
    Route::get('articles/{slug}', [KnowledgeCenterController::class, 'showArticle']);
    Route::get('resources', [KnowledgeCenterController::class, 'resources']);
    Route::get('resources/{slug}/download', [KnowledgeCenterController::class, 'downloadResource']); // gated

    // Admin (permission-gated)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('categories', [KnowledgeCategoryController::class, 'store']);

        Route::get('admin/articles', [KnowledgeArticleController::class, 'index']);
        Route::post('admin/articles', [KnowledgeArticleController::class, 'store']);
        Route::put('admin/articles/{article}', [KnowledgeArticleController::class, 'update']);
        Route::post('admin/articles/{article}/publish', [KnowledgeArticleController::class, 'publish']);
        Route::post('admin/articles/{article}/archive', [KnowledgeArticleController::class, 'archive']);

        Route::get('admin/resources', [KnowledgeResourceController::class, 'index']);
        Route::post('admin/resources', [KnowledgeResourceController::class, 'store']);
        Route::post('admin/resources/{resource}/publish', [KnowledgeResourceController::class, 'publish']);
        Route::post('admin/resources/{resource}/archive', [KnowledgeResourceController::class, 'archive']);

        Route::get('reports', [KnowledgeReportController::class, 'index']);
    });
});

// ── Research Center ──────────────────────────────────────────────────────────
Route::prefix('api/v1/research')->group(function () {
    // Public read
    Route::get('categories', [ResearchCategoryController::class, 'index']);
    Route::get('publications', [ResearchCenterController::class, 'index']);
    Route::get('publications/{slug}', [ResearchCenterController::class, 'show']);
    Route::get('publications/{slug}/download', [ResearchCenterController::class, 'download']); // gated

    // Admin (permission-gated)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('categories', [ResearchCategoryController::class, 'store']);

        Route::get('admin/authors', [ResearchAuthorController::class, 'index']);
        Route::post('admin/authors', [ResearchAuthorController::class, 'store']);
        Route::put('admin/authors/{author}', [ResearchAuthorController::class, 'update']);

        Route::get('admin/publications', [ResearchPublicationController::class, 'index']);
        Route::post('admin/publications', [ResearchPublicationController::class, 'store']);
        Route::put('admin/publications/{publication}', [ResearchPublicationController::class, 'update']);
        Route::post('admin/publications/{publication}/publish', [ResearchPublicationController::class, 'publish']);
        Route::post('admin/publications/{publication}/archive', [ResearchPublicationController::class, 'archive']);

        Route::get('reports', [ResearchReportController::class, 'index']);
    });
});
