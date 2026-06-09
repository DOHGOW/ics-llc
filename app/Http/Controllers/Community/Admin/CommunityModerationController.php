<?php

namespace App\Http\Controllers\Community\Admin;

use App\Http\Controllers\Controller;
use App\Models\Community\CommunityProfile;
use App\Services\Community\CommunityProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Community moderation (Wave 4b) — ICS staff governance. Verify + status change are audited
 * under COMMUNITY_MANAGEMENT (D-058); suspension/hiding HIGH. These are the ONLY community
 * actions that hit the audit trail (W4b-6).
 */
class CommunityModerationController extends Controller
{
    public function __construct(private readonly CommunityProfileService $service) {}

    public function verify(Request $request, CommunityProfile $profile): JsonResponse
    {
        abort_unless($request->user()->can('community.profile.verify'), 403);
        $this->service->verify($profile, $request->user());

        return response()->json(['message' => __('Profile verified.')]);
    }

    public function changeStatus(Request $request, CommunityProfile $profile): JsonResponse
    {
        abort_unless($request->user()->can('community.profile.suspend'), 403);
        $data = $request->validate(['status' => ['required', Rule::in(['active', 'suspended', 'hidden'])]]);

        $this->service->changeStatus($profile, $data['status'], $request->user());

        return response()->json(['message' => __('Profile status updated.')]);
    }
}
