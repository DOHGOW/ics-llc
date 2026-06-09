<?php

namespace App\Http\Controllers\Startup;

use App\Http\Controllers\Controller;
use App\Models\Startup\Startup;
use App\Models\Startup\TeamInvitation;
use App\Models\Startup\TeamMember;
use App\Services\Startup\FounderService;
use App\Services\Startup\StartupAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Founder/team management (Wave 5A / H-2 / M-2). Invitation flow (token + accept). Founder
 * removal + ownership transfer go through FounderService (orphan guard, immutable transfer).
 */
class TeamController extends Controller
{
    public function __construct(
        private readonly StartupAccessService $access,
        private readonly FounderService $founders,
    ) {}

    public function index(Request $request, Startup $startup): JsonResponse
    {
        abort_unless($this->access->isTeamMember($request->user(), $startup) || $this->access->isStaff($request->user()), 403);

        // ownership_percent is $hidden on the model — not exposed here (C-1).
        return response()->json($startup->teamMembers()->where('status', 'active')
            ->get(['id', 'user_id', 'name', 'role', 'is_founder', 'status']));
    }

    public function invite(Request $request, Startup $startup): JsonResponse
    {
        abort_unless($this->access->canManage($request->user(), $startup), 403);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', Rule::in(['co_founder', 'admin', 'member'])],
        ]);

        $invitation = TeamInvitation::create([
            'startup_id' => $startup->id,
            'email' => $data['email'],
            'role' => $data['role'],
            'token' => Str::random(64),
            'status' => 'pending',
            'invited_by' => $request->user()->id,
            'expires_at' => now()->addDays(14),
        ]);

        return response()->json(['invitation_id' => $invitation->id], 201);
    }

    public function accept(Request $request, string $token): JsonResponse
    {
        $invitation = TeamInvitation::where('token', $token)->where('status', 'pending')->firstOrFail();
        abort_if($invitation->expires_at !== null && $invitation->expires_at->isPast(), 410, 'Invitation expired.');

        TeamMember::create([
            'startup_id' => $invitation->startup_id,
            'user_id' => $request->user()->id,
            'name' => $request->user()->name,
            'role' => $invitation->role,
            'is_founder' => $invitation->role === 'co_founder',
            'status' => 'active',
        ]);
        $invitation->forceFill(['status' => 'accepted'])->save();

        return response()->json(['message' => __('Joined the startup team.')]);
    }

    public function remove(Request $request, Startup $startup, TeamMember $member): JsonResponse
    {
        abort_unless($this->access->canManage($request->user(), $startup), 403);
        abort_unless((int) $member->startup_id === (int) $startup->id, 404);

        $this->founders->removeMember($startup, $member); // orphan guard (H-2)

        return response()->json(['message' => __('Member removed.')]);
    }

    public function transferOwnership(Request $request, Startup $startup): JsonResponse
    {
        // Only the current primary founder or ICS staff may transfer ownership.
        abort_unless((int) $startup->founder_id === (int) $request->user()->id || $this->access->isStaff($request->user()), 403);

        $data = $request->validate([
            'to_founder_id' => ['required', 'integer', 'exists:core_users,id'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $this->founders->transferOwnership($startup, (int) $data['to_founder_id'], $request->user(), $data['reason'] ?? null);

        return response()->json(['message' => __('Ownership transferred.')]);
    }
}
