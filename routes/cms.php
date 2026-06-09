<?php

/*
|--------------------------------------------------------------------------
| CMS Routes  (Sprint 2 · Wave 1c)
|--------------------------------------------------------------------------
| Decisions: D-010, D-023, D-038, D-051, D-052.
|
| REGISTER in bootstrap/app.php withRouting(then: ...):
|     Route::middleware('api')->group(base_path('routes/cms.php'));
|
| Public read surface is tier-scoped via ContentAccessService ONLY (never
| AccountScope). Admin surface is permission-gated (cms.*) behind auth:sanctum.
| Publish/archive are audited (content_management, W1c-4).
*/

use App\Http\Controllers\Admin\Cms\ArticleController;
use App\Http\Controllers\Admin\Cms\MediaController;
use App\Http\Controllers\Admin\Cms\PageController;
use App\Http\Controllers\Content\PublicContentController;
use Illuminate\Support\Facades\Route;

// ── Public content (read-only; drafts 404 to non-staff via draft override) ──
Route::prefix('api/v1/content')->group(function () {
    Route::get('search', [PublicContentController::class, 'searchArticles']);
    Route::get('pages/{slug}', [PublicContentController::class, 'showPage']);
    Route::get('articles/{slug}', [PublicContentController::class, 'showArticle']);
});

// ── Admin CMS (permission-gated; auth:sanctum) ──────────────────────────────
Route::prefix('api/v1/admin/cms')->middleware('auth:sanctum')->group(function () {

    // Pages
    Route::get('pages', [PageController::class, 'index']);
    Route::post('pages', [PageController::class, 'store']);
    Route::put('pages/{page}', [PageController::class, 'update']);
    Route::post('pages/{page}/publish', [PageController::class, 'publish']);
    Route::post('pages/{page}/archive', [PageController::class, 'archive']);

    // Articles
    Route::get('articles', [ArticleController::class, 'index']);
    Route::post('articles', [ArticleController::class, 'store']);
    Route::put('articles/{article}', [ArticleController::class, 'update']);
    Route::post('articles/{article}/publish', [ArticleController::class, 'publish']);
    Route::post('articles/{article}/archive', [ArticleController::class, 'archive']);

    // Media library
    Route::get('media', [MediaController::class, 'index']);
    Route::post('media', [MediaController::class, 'store']);
    Route::delete('media/{medium}', [MediaController::class, 'destroy']);
});
