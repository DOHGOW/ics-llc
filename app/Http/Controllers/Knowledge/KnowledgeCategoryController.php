<?php

namespace App\Http\Controllers\Knowledge;

use App\Http\Controllers\Controller;
use App\Models\Knowledge\KnowledgeCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Knowledge categories (Wave 3). Public read; staff create (knowledge.articles.create). */
class KnowledgeCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            KnowledgeCategory::query()->orderBy('sort_order')->get(['id', 'name', 'slug', 'icon', 'parent_id', 'article_count'])
        );
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('knowledge.articles.create'), 403);

        $category = KnowledgeCategory::create($request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:150', 'unique:knowledge_categories,slug'],
            'icon' => ['nullable', 'string', 'max:100'],
            'parent_id' => ['nullable', 'integer', 'exists:knowledge_categories,id'],
            'sort_order' => ['nullable', 'integer'],
        ]));

        return response()->json(['id' => $category->id], 201);
    }
}
