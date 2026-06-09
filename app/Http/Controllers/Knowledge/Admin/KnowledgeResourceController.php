<?php

namespace App\Http\Controllers\Knowledge\Admin;

use App\Http\Controllers\Controller;
use App\Models\Knowledge\KnowledgeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Knowledge resource (downloadable asset) management (Wave 3). Reuses knowledge.articles.*
 * permissions (resources are a Knowledge asset class). File stored via Storage; tier-gated
 * download served by KnowledgeCenterController (W3-3/W2-5). Publish audited under 'knowledge'.
 */
class KnowledgeResourceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('knowledge.articles.create'), 403);

        return response()->json(
            KnowledgeResource::query()->select(['id', 'title', 'slug', 'type', 'access_tier', 'status', 'published_at'])->paginate(25)
        );
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('knowledge.articles.create'), 403);

        $data = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:knowledge_categories,id'],
            'type' => ['required', 'in:template,toolkit,sop,checklist,dataset,download,other'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'access_tier' => ['required', 'integer', 'between:1,5'],
            'file' => ['required', 'file', 'max:'.(int) config('ics.media.max_kb', 10240)],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string'],
        ]);

        $file = $request->file('file');
        $path = $file->store(config('ics.media.path', 'media').'/knowledge', config('ics.media.disk', 'public'));

        $resource = KnowledgeResource::create([
            'category_id' => $data['category_id'] ?? null,
            'type' => $data['type'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'access_tier' => $data['access_tier'],
            'file_path' => $path,
            'file_size_kb' => (int) ceil($file->getSize() / 1024),
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['id' => $resource->id, 'slug' => $resource->slug], 201);
    }

    public function publish(Request $request, KnowledgeResource $resource): JsonResponse
    {
        abort_unless($request->user()->can('knowledge.articles.publish'), 403);
        $resource->publish();

        return response()->json(['message' => __('Resource published.')]);
    }

    public function archive(Request $request, KnowledgeResource $resource): JsonResponse
    {
        abort_unless($request->user()->can('knowledge.articles.delete'), 403);
        $resource->archive();

        return response()->json(['message' => __('Resource archived.')]);
    }
}
