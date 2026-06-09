<?php

namespace App\Http\Controllers\Research\Admin;

use App\Http\Controllers\Controller;
use App\Models\Research\ResearchAuthor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Research author management (Wave 3). Authors may be EXTERNAL (user_id NULL, W3-8).
 * Gated by research.publications.create (authoring staff manage the author roster).
 */
class ResearchAuthorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('research.publications.create'), 403);

        return response()->json(
            ResearchAuthor::query()->select(['id', 'name', 'organisation', 'orcid_id', 'user_id'])->paginate(50)
        );
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('research.publications.create'), 403);

        $author = ResearchAuthor::create($request->validate([
            'user_id' => ['nullable', 'integer', 'exists:core_users,id'], // NULL = external
            'name' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:150'],
            'bio' => ['nullable', 'string'],
            'email' => ['nullable', 'email', 'max:255'],
            'organisation' => ['nullable', 'string', 'max:255'],
            'orcid_id' => ['nullable', 'string', 'max:50'],
        ]));

        return response()->json(['id' => $author->id], 201);
    }

    public function update(Request $request, ResearchAuthor $author): JsonResponse
    {
        abort_unless($request->user()->can('research.publications.update'), 403);

        $author->update($request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:150'],
            'bio' => ['nullable', 'string'],
            'email' => ['nullable', 'email', 'max:255'],
            'organisation' => ['nullable', 'string', 'max:255'],
            'orcid_id' => ['nullable', 'string', 'max:50'],
        ]));

        return response()->json(['message' => __('Author updated.')]);
    }
}
