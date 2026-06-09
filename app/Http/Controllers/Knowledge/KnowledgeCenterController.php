<?php

namespace App\Http\Controllers\Knowledge;

use App\Http\Controllers\Controller;
use App\Http\Resources\Knowledge\KnowledgeArticleResource;
use App\Http\Resources\Knowledge\KnowledgeResourceResource;
use App\Models\Knowledge\KnowledgeArticle;
use App\Models\Knowledge\KnowledgeResource;
use App\Services\Content\ContentAccessService;
use App\Services\Content\EngagementRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Knowledge Center public surface (Wave 3, D-036 LATERAL). Tier access via
 * ContentAccessService ONLY (W3-1: strategy-relative tiers). W3-3: teasers are public;
 * body/files are gated in the resource + the download endpoint. Views/downloads recorded
 * to the unified content_engagement_events (D-051).
 */
class KnowledgeCenterController extends Controller
{
    public function __construct(
        private readonly ContentAccessService $access,
        private readonly EngagementRecorder $engagement,
    ) {}

    public function articles(Request $request): JsonResponse
    {
        // Public listing shows teasers for all published items; entitlement marked per row.
        $articles = KnowledgeArticle::query()->published()
            ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->integer('category_id')))
            ->select(['id', 'category_id', 'type', 'title', 'slug', 'excerpt', 'access_tier',
                'read_time_min', 'featured_image', 'seo_title', 'seo_description', 'published_at'])
            ->paginate(15);

        $user = $request->user();
        $articles->getCollection()->transform(
            fn ($a) => KnowledgeArticleResource::for($a, $this->access->canAccess($user, $a))
        );

        return response()->json($articles);
    }

    public function showArticle(Request $request, string $slug): JsonResponse
    {
        $article = KnowledgeArticle::query()->published()->where('slug', $slug)->firstOrFail();
        $entitled = $this->access->canAccess($request->user(), $article);

        $this->engagement->record($article, 'view', $request);
        $article->increment('view_count');

        return response()->json(KnowledgeArticleResource::for($article, $entitled));
    }

    public function resources(Request $request): JsonResponse
    {
        $resources = KnowledgeResource::query()->published()
            ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')))
            ->select(['id', 'category_id', 'type', 'title', 'slug', 'description', 'file_path',
                'access_tier', 'file_size_kb', 'seo_title', 'seo_description', 'published_at'])
            ->paginate(15);

        $user = $request->user();
        $resources->getCollection()->transform(
            fn ($r) => KnowledgeResourceResource::for($r, $this->access->canAccess($user, $r))
        );

        return response()->json($resources);
    }

    /** W3-3 / W2-5: gated download — entitlement-checked + streamed; never a public URL. */
    public function downloadResource(Request $request, string $slug): StreamedResponse
    {
        $resource = KnowledgeResource::query()->published()->where('slug', $slug)->firstOrFail();
        abort_unless($this->access->canDownload($request->user(), $resource, $resource->file_path !== null), 403);

        $disk = Storage::disk(config('ics.media.disk', 'public'));
        abort_unless($disk->exists($resource->file_path), 404);

        $this->engagement->record($resource, 'download', $request);
        $resource->increment('download_count');

        return $disk->download($resource->file_path, $resource->title);
    }
}
