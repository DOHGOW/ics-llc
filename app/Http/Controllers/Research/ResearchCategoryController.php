<?php

namespace App\Http\Controllers\Research;

use App\Http\Controllers\Controller;
use App\Models\Research\ResearchCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Research categories (Wave 3). Public read; staff create (research.publications.create). */
class ResearchCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            ResearchCategory::query()->orderBy('sort_order')->get(['id', 'name', 'slug', 'description', 'parent_id'])
        );
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('research.publications.create'), 403);

        $category = ResearchCategory::create($request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:150', 'unique:research_categories,slug'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', 'exists:research_categories,id'],
            'sort_order' => ['nullable', 'integer'],
        ]));

        return response()->json(['id' => $category->id], 201);
    }
}
