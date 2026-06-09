<?php

namespace App\Http\Controllers\Startup\Admin;

use App\Http\Controllers\Controller;
use App\Models\Startup\Mentor;
use App\Models\Startup\Startup;
use App\Services\Startup\StartupGovernanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * ICS staff governance (Wave 5A / D-062 / D-064). Verification, suspension/reactivation,
 * graduation, lifecycle transitions, mentor/advisor assignment — audited under
 * STARTUP_MANAGEMENT (verify/suspend/reactivate HIGH).
 */
class StartupGovernanceController extends Controller
{
    public function __construct(private readonly StartupGovernanceService $governance) {}

    private function authorizeStaff(Request $request): void
    {
        abort_unless($request->user()->can('startup.profiles.read.all'), 403); // ICS staff marker
    }

    public function verify(Request $request, Startup $startup): JsonResponse
    {
        $this->authorizeStaff($request);
        $this->governance->verify($startup, $request->user());

        return response()->json(['message' => __('Startup verified.')]);
    }

    public function suspend(Request $request, Startup $startup): JsonResponse
    {
        $this->authorizeStaff($request);
        $this->governance->suspend($startup, $request->user());

        return response()->json(['message' => __('Startup suspended.')]);
    }

    public function reactivate(Request $request, Startup $startup): JsonResponse
    {
        $this->authorizeStaff($request);
        $this->governance->reactivate($startup, $request->user());

        return response()->json(['message' => __('Startup reactivated.')]);
    }

    public function graduate(Request $request, Startup $startup): JsonResponse
    {
        $this->authorizeStaff($request);
        $this->governance->graduateToAlumni($startup, $request->user());

        return response()->json(['message' => __('Startup graduated to alumni.')]);
    }

    public function setLifecycle(Request $request, Startup $startup): JsonResponse
    {
        $this->authorizeStaff($request);
        $data = $request->validate(['lifecycle_stage' => ['required', Rule::in(Startup::LIFECYCLE)]]);
        $this->governance->setLifecycleStage($startup, $data['lifecycle_stage']);

        return response()->json(['message' => __('Lifecycle stage updated.')]);
    }

    public function assignMentor(Request $request, Startup $startup): JsonResponse
    {
        abort_unless($request->user()->can('startup.mentors.manage'), 403);
        $data = $request->validate([
            'mentor_id' => ['required', 'integer', 'exists:core_users,id'],
            'type' => ['required', Rule::in(Mentor::TYPES)],
            'notes' => ['nullable', 'string'],
        ]);

        $mentor = Mentor::create([
            'startup_id' => $startup->id,
            'mentor_id' => $data['mentor_id'],
            'type' => $data['type'],
            'assigned_at' => now(),
            'assigned_by' => $request->user()->id,
            'status' => 'active',
            'notes' => $data['notes'] ?? null,
        ]);

        return response()->json(['id' => $mentor->id], 201);
    }
}
