<?php

namespace App\Http\Controllers\Research;

use App\Http\Controllers\Controller;
use App\Http\Resources\Research\ResearchPublicationResource;
use App\Models\Research\ResearchPublication;
use App\Services\Content\ContentAccessService;
use App\Services\Content\EngagementRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Research Center public surface (Wave 3, D-034 HIERARCHICAL). Tier access via
 * ContentAccessService ONLY. W3-3: abstract is public; body/file gated. Views/downloads/
 * citations recorded to content_engagement_events (D-051).
 */
class ResearchCenterController extends Controller
{
    public function __construct(
        private readonly ContentAccessService $access,
        private readonly EngagementRecorder $engagement,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $publications = ResearchPublication::query()->published()
            ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->integer('category_id')))
            ->with('authors')
            ->select(['id', 'category_id', 'content_group', 'title', 'slug', 'abstract', 'doi',
                'access_tier', 'publish_date', 'seo_title', 'seo_description', 'file_path', 'body'])
            ->paginate(15);

        $user = $request->user();
        $publications->getCollection()->transform(
            fn ($p) => ResearchPublicationResource::for($p, $this->access->canAccess($user, $p))
        );

        return response()->json($publications);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $publication = ResearchPublication::query()->published()->with('authors')->where('slug', $slug)->firstOrFail();
        $entitled = $this->access->canAccess($request->user(), $publication);

        $this->engagement->record($publication, 'view', $request);
        $publication->increment('view_count');

        return response()->json(ResearchPublicationResource::for($publication, $entitled));
    }

    /** W3-3 / W2-5: gated download — entitlement-checked + streamed; never a public URL. */
    public function download(Request $request, string $slug): StreamedResponse
    {
        $publication = ResearchPublication::query()->published()->where('slug', $slug)->firstOrFail();
        abort_unless($this->access->canDownload($request->user(), $publication, $publication->file_path !== null), 403);

        $disk = Storage::disk(config('ics.media.disk', 'public'));
        abort_unless($disk->exists($publication->file_path), 404);

        $this->engagement->record($publication, 'download', $request);
        $publication->increment('download_count');

        return $disk->download($publication->file_path, $publication->title);
    }
}
