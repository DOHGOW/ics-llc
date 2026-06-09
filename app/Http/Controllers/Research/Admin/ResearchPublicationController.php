<?php

namespace App\Http\Controllers\Research\Admin;

use App\Http\Controllers\Controller;
use App\Models\Research\ResearchPublication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Research publication management (Wave 3). Permission-gated (research.publications.*).
 * Publish is human-approved (P-1) and audited under module 'research' (W3-2). Tier set via
 * access_tier (D-034 HIERARCHICAL). Authors attached with order.
 */
class ResearchPublicationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('research.publications.create'), 403);

        return response()->json(
            ResearchPublication::query()->select(['id', 'title', 'slug', 'content_group', 'access_tier', 'status', 'published_at'])->paginate(25)
        );
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('research.publications.create'), 403);

        $data = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:research_categories,id'],
            'content_group' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'abstract' => ['required', 'string'],
            'body' => ['nullable', 'string'],
            'doi' => ['nullable', 'string', 'max:100'],
            'publish_date' => ['nullable', 'date'],
            'access_tier' => ['required', 'integer', 'between:1,5'],
            'author_ids' => ['nullable', 'array'],
            'author_ids.*' => ['integer', 'exists:research_authors,id'],
        ]);
        $data['created_by'] = $request->user()->id;
        $authorIds = $data['author_ids'] ?? [];
        unset($data['author_ids']);

        $publication = ResearchPublication::create($data);
        $this->syncAuthors($publication, $authorIds);

        return response()->json(['id' => $publication->id, 'slug' => $publication->slug], 201);
    }

    public function update(Request $request, ResearchPublication $publication): JsonResponse
    {
        abort_unless($request->user()->can('research.publications.update'), 403);

        $data = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:research_categories,id'],
            'content_group' => ['sometimes', 'string', 'max:50'],
            'title' => ['sometimes', 'string', 'max:255'],
            'abstract' => ['sometimes', 'string'],
            'body' => ['nullable', 'string'],
            'doi' => ['nullable', 'string', 'max:100'],
            'publish_date' => ['nullable', 'date'],
            'access_tier' => ['sometimes', 'integer', 'between:1,5'],
            'author_ids' => ['nullable', 'array'],
            'author_ids.*' => ['integer', 'exists:research_authors,id'],
        ]);
        if (array_key_exists('author_ids', $data)) {
            $this->syncAuthors($publication, $data['author_ids'] ?? []);
            unset($data['author_ids']);
        }
        $publication->update($data);

        return response()->json(['message' => __('Publication updated.')]);
    }

    public function publish(Request $request, ResearchPublication $publication): JsonResponse
    {
        abort_unless($request->user()->can('research.publications.publish'), 403); // P-1
        $publication->publish(); // ContentPublished → audited (module 'research', W3-2)

        return response()->json(['message' => __('Publication published.')]);
    }

    public function archive(Request $request, ResearchPublication $publication): JsonResponse
    {
        abort_unless($request->user()->can('research.publications.delete'), 403);
        $publication->archive();

        return response()->json(['message' => __('Publication archived.')]);
    }

    /** @param array<int,int> $authorIds */
    private function syncAuthors(ResearchPublication $publication, array $authorIds): void
    {
        $sync = [];
        foreach (array_values($authorIds) as $i => $authorId) {
            $sync[$authorId] = ['author_order' => $i + 1];
        }
        $publication->authors()->sync($sync);
    }
}
