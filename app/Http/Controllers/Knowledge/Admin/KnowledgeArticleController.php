<?php

namespace App\Http\Controllers\Knowledge\Admin;

use App\Http\Controllers\Controller;
use App\Models\Knowledge\KnowledgeArticle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Knowledge article management (Wave 3). Permission-gated (knowledge.articles.*). Publish is
 * human-approved (P-1) and audited under module 'knowledge' (W3-2 fix). Tier set via
 * access_tier (D-036 LATERAL).
 */
class KnowledgeArticleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('knowledge.articles.create'), 403);

        return response()->json(
            KnowledgeArticle::query()->select(['id', 'title', 'slug', 'type', 'access_tier', 'status', 'published_at'])->paginate(25)
        );
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('knowledge.articles.create'), 403);

        $data = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:knowledge_categories,id'],
            'type' => ['required', 'in:article,news,guide,white_paper,case_study,video,internal_kb,client_doc'],
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'access_tier' => ['required', 'integer', 'between:1,5'],
            'read_time_min' => ['nullable', 'integer', 'min:0'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string'],
        ]);
        $data['created_by'] = $request->user()->id;

        $article = KnowledgeArticle::create($data);

        return response()->json(['id' => $article->id, 'slug' => $article->slug], 201);
    }

    public function update(Request $request, KnowledgeArticle $article): JsonResponse
    {
        abort_unless($request->user()->can('knowledge.articles.update.own'), 403);

        $article->update($request->validate([
            'category_id' => ['nullable', 'integer', 'exists:knowledge_categories,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'access_tier' => ['sometimes', 'integer', 'between:1,5'],
            'read_time_min' => ['nullable', 'integer', 'min:0'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string'],
        ]));

        return response()->json(['message' => __('Article updated.')]);
    }

    public function publish(Request $request, KnowledgeArticle $article): JsonResponse
    {
        abort_unless($request->user()->can('knowledge.articles.publish'), 403); // P-1
        $article->publish(); // fires ContentPublished → audited (module 'knowledge', W3-2)

        return response()->json(['message' => __('Article published.')]);
    }

    public function archive(Request $request, KnowledgeArticle $article): JsonResponse
    {
        abort_unless($request->user()->can('knowledge.articles.delete'), 403);
        $article->archive();

        return response()->json(['message' => __('Article archived.')]);
    }
}
