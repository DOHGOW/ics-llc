<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Http\Resources\Community\CommunityProfileResource;
use App\Models\Community\CommunityProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public community directory + discoverability (Wave 4b). VISIBILITY-scoped (W4b-5):
 * visibleTo() returns active+public (guests) / +authenticated (logged-in) / all (staff).
 * Profile views are ANALYTICS only (W4b-6) — a cached counter, not an audit event.
 * W4b-1: responses go through CommunityProfileResource (public-only projection).
 */
class CommunityDirectoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $profiles = CommunityProfile::query()
            ->visibleTo($request->user())
            ->when($request->filled('type'), fn ($q) => $q->where('profile_type', $request->string('type')))
            ->when($request->filled('country'), fn ($q) => $q->where('location_country', $request->string('country')))
            ->when($request->filled('q'), fn ($q) => $q->whereFullText(['display_name', 'tagline', 'bio'], (string) $request->string('q')))
            ->with(['founder', 'startup', 'consultant', 'trainer', 'partner', 'researcher'])
            ->paginate(15);

        return response()->json(CommunityProfileResource::collection($profiles)->response()->getData(true));
    }

    public function show(Request $request, CommunityProfile $profile): JsonResponse
    {
        // Visibility check (404 to non-entitled, like the content draft override).
        $visible = CommunityProfile::query()->visibleTo($request->user())->whereKey($profile->id)->exists();
        abort_unless($visible, 404);

        $profile->increment('view_count'); // analytics counter (W4b-6) — not audited

        return response()->json(new CommunityProfileResource($profile->load([
            'founder', 'startup', 'consultant', 'trainer', 'partner', 'researcher',
        ])));
    }
}
