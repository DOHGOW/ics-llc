<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Models\Content\Page;
use App\Services\Content\CmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Admin Page management (Wave 1c). Permission-gated (cms.pages.*); publish is
 * human-approved (P-1) and audited (content_management).
 */
class PageController extends Controller
{
    public function __construct(private readonly CmsService $cms) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('cms.pages.read'), 403);

        return response()->json(
            Page::query()->select(['id', 'title', 'slug', 'status', 'published_at'])->paginate(25)
        );
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('cms.pages.create'), 403);

        $page = Page::create($request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'template' => ['nullable', 'string', 'max:100'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string'],
        ]));

        return response()->json(['id' => $page->id, 'slug' => $page->slug], 201);
    }

    public function update(Request $request, Page $page): JsonResponse
    {
        abort_unless($request->user()->can('cms.pages.update'), 403);

        $page->update($request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'template' => ['nullable', 'string', 'max:100'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string'],
        ]));

        return response()->json(['message' => __('Page updated.')]);
    }

    public function publish(Request $request, Page $page): JsonResponse
    {
        abort_unless($request->user()->can('cms.pages.publish'), 403); // P-1 human approval
        $this->cms->publish($page, $request->user());

        return response()->json(['message' => __('Page published.')]);
    }

    public function archive(Request $request, Page $page): JsonResponse
    {
        abort_unless($request->user()->can('cms.pages.delete'), 403);
        $this->cms->archive($page);

        return response()->json(['message' => __('Page archived.')]);
    }
}
