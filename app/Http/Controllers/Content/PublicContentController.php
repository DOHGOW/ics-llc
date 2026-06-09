<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Models\Content\Article;
use App\Models\Content\Page;
use App\Services\Content\ContentAccessService;
use App\Services\Content\EngagementRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public CMS read surface (Wave 1c). Tier-scoped via ContentAccessService ONLY
 * (never AccountScope — requirement 2). Article views recorded via EngagementRecorder
 * (content_engagement_events). Drafts return 404 to non-staff (draft override).
 */
class PublicContentController extends Controller
{
    public function __construct(
        private readonly ContentAccessService $access,
        private readonly EngagementRecorder $engagement,
    ) {}

    public function showPage(Request $request, string $slug): JsonResponse
    {
        $page = Page::query()->where('slug', $slug)->firstOrFail();
        abort_unless($this->access->canAccess($request->user(), $page), 404);

        return response()->json($this->presentPage($page));
    }

    public function showArticle(Request $request, string $slug): JsonResponse
    {
        $article = Article::query()->where('slug', $slug)->firstOrFail();
        abort_unless($this->access->canAccess($request->user(), $article), 404);

        $this->engagement->record($article, 'view', $request);
        $article->increment('view_count');

        return response()->json($this->presentArticle($article));
    }

    public function searchArticles(Request $request): JsonResponse
    {
        $term = (string) $request->query('q', '');

        $results = Article::query()
            ->published()
            ->when($term !== '', fn ($q) => $q->search($term))
            ->select(['id', 'title', 'slug', 'excerpt', 'published_at'])
            ->paginate(15);

        return response()->json($results);
    }

    private function presentPage(Page $page): array
    {
        return [
            'title' => $page->title,
            'slug' => $page->slug,
            'body' => $page->body,
            'seo' => $this->seo($page->seo_title ?: $page->title, $page->seo_description, $page->slug, $page->published_at),
        ];
    }

    private function presentArticle(Article $article): array
    {
        return [
            'title' => $article->title,
            'slug' => $article->slug,
            'excerpt' => $article->excerpt,
            'body' => $article->body,
            'seo' => $this->seo($article->seo_title ?: $article->title, $article->seo_description ?: $article->excerpt, $article->slug, $article->published_at),
        ];
    }

    /** SEO/JSON-LD payload (canonical from slug; falls back to title/excerpt). */
    private function seo(string $title, ?string $description, string $slug, $publishedAt): array
    {
        return [
            'title' => $title,
            'description' => $description,
            'canonical' => url('/content/'.$slug),
            'published_at' => optional($publishedAt)->toIso8601String(),
        ];
    }
}
