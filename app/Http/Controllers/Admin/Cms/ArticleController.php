<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Models\Content\Article;
use App\Services\Content\CmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Admin Article management (Wave 1c). Permission-gated (cms.articles.*); publish is
 * human-approved (P-1) and audited (content_management). Default-deny via Spatie.
 */
class ArticleController extends Controller
{
    public function __construct(private readonly CmsService $cms) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('cms.articles.update'), 403); // no read perm; manage implies list

        return response()->json(
            Article::query()->select(['id', 'title', 'slug', 'status', 'published_at'])->paginate(25)
        );
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('cms.articles.create'), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string'],
        ]);

        $article = Article::create($data); // slug + draft + authorship stamped

        return response()->json(['id' => $article->id, 'slug' => $article->slug], 201);
    }

    public function update(Request $request, Article $article): JsonResponse
    {
        abort_unless($request->user()->can('cms.articles.update'), 403);

        $article->update($request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string'],
        ]));

        return response()->json(['message' => __('Article updated.')]);
    }

    public function publish(Request $request, Article $article): JsonResponse
    {
        abort_unless($request->user()->can('cms.articles.publish'), 403); // P-1 human approval
        $this->cms->publish($article, $request->user());

        return response()->json(['message' => __('Article published.')]);
    }

    public function archive(Request $request, Article $article): JsonResponse
    {
        abort_unless($request->user()->can('cms.articles.delete'), 403);
        $this->cms->archive($article);

        return response()->json(['message' => __('Article archived.')]);
    }
}
