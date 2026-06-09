<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Http\Resources\Community\CommunityProfileResource;
use App\Models\Community\CommunityProfile;
use App\Models\Community\Endorsement;
use App\Models\Community\ProfileSkill;
use App\Services\Community\CommunityProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Owner-scoped community profile management (Wave 4b). A user creates/updates their OWN
 * single profile (community.profile.*.own). Skill endorsement is a peer ANALYTICS action
 * (W4b-6 — not audited). Link integrity (W4b-2) enforced in the service.
 */
class CommunityProfileController extends Controller
{
    public function __construct(private readonly CommunityProfileService $service) {}

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('community.profile.create.own'), 403);

        $base = $request->validate([
            'profile_type' => ['required', Rule::in(CommunityProfile::TYPES)],
            'display_name' => ['required', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'location_country' => ['nullable', 'string', 'size:2'],
            'location_city' => ['nullable', 'string', 'max:100'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'twitter_url' => ['nullable', 'url', 'max:255'],
            'visibility' => ['nullable', 'in:public,authenticated'],
        ]);
        $extension = (array) $request->input('details', []); // type-specific fields + optional link id

        $profile = $this->service->createProfile($request->user(), $base, $extension);

        return response()->json(['id' => $profile->id], 201);
    }

    public function mine(Request $request): JsonResponse
    {
        $profile = CommunityProfile::query()->where('user_id', $request->user()->id)
            ->with(['founder', 'startup', 'consultant', 'trainer', 'partner', 'researcher'])->firstOrFail();

        return response()->json(new CommunityProfileResource($profile));
    }

    public function update(Request $request, CommunityProfile $profile): JsonResponse
    {
        abort_unless((int) $profile->user_id === (int) $request->user()->id
            && $request->user()->can('community.profile.update.own'), 403);

        $profile->update($request->validate([
            'display_name' => ['sometimes', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'location_country' => ['nullable', 'string', 'size:2'],
            'location_city' => ['nullable', 'string', 'max:100'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'twitter_url' => ['nullable', 'url', 'max:255'],
            'visibility' => ['sometimes', 'in:public,authenticated'],
        ]));

        return response()->json(['message' => __('Profile updated.')]);
    }

    /** Peer skill endorsement — analytics, not audited (W4b-6). */
    public function endorse(Request $request, CommunityProfile $profile): JsonResponse
    {
        abort_unless($request->user()->can('community.skills.endorse'), 403);
        abort_if((int) $profile->user_id === (int) $request->user()->id, 422, 'You cannot endorse your own profile.');

        $data = $request->validate(['skill_id' => ['required', 'integer', 'exists:community_skills,id']]);

        // Unique per (profile, skill, endorser) — re-endorsing is a no-op (no double count).
        $endorsement = Endorsement::firstOrCreate([
            'profile_id' => $profile->id,
            'skill_id' => $data['skill_id'],
            'endorsed_by_id' => $request->user()->id,
        ]);

        if ($endorsement->wasRecentlyCreated) {
            $skill = ProfileSkill::firstOrCreate(
                ['profile_id' => $profile->id, 'skill_id' => $data['skill_id']],
                ['endorsement_count' => 0],
            );
            $skill->increment('endorsement_count');
        }

        return response()->json(['message' => __('Endorsed.')]);
    }
}
