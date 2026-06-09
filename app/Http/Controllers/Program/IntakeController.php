<?php

namespace App\Http\Controllers\Program;

use App\Http\Controllers\Controller;
use App\Models\Startup\ProgramCohort;
use App\Models\Startup\ProgramEnrollment;
use App\Models\Startup\Startup;
use App\Services\Startup\IntakeService;
use App\Services\Startup\ProgramParticipationService;
use App\Services\Startup\StartupAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Governed program intake (Wave 5B / M-1 / D-067). A startup APPLIES to a cohort (founder/admin);
 * ICS staff / cohort coordinators review → accept/reject. No direct enrollment bypass; all
 * acceptance decisions audited.
 */
class IntakeController extends Controller
{
    public function __construct(
        private readonly IntakeService $intake,
        private readonly ProgramParticipationService $participation,
        private readonly StartupAccessService $startupAccess,
    ) {}

    /** Founder/admin of the startup applies to a cohort. */
    public function apply(Request $request, ProgramCohort $cohort): JsonResponse
    {
        $data = $request->validate(['startup_id' => ['required', 'integer', 'exists:startup_profiles,id']]);
        $startup = Startup::findOrFail($data['startup_id']);
        abort_unless($this->startupAccess->canManage($request->user(), $startup), 403);

        $enrollment = $this->intake->apply($cohort, $startup);

        return response()->json(['enrollment_id' => $enrollment->id, 'status' => $enrollment->status], 201);
    }

    public function review(Request $request, ProgramEnrollment $enrollment): JsonResponse
    {
        $this->authorizeReviewer($request, $enrollment);
        $this->intake->review($enrollment, $request->user());

        return response()->json(['message' => __('Marked under review.')]);
    }

    public function accept(Request $request, ProgramEnrollment $enrollment): JsonResponse
    {
        $this->authorizeReviewer($request, $enrollment);
        $this->intake->accept($enrollment, $request->user());

        return response()->json(['message' => __('Application accepted; startup enrolled.')]);
    }

    public function reject(Request $request, ProgramEnrollment $enrollment): JsonResponse
    {
        $this->authorizeReviewer($request, $enrollment);
        $this->intake->reject($enrollment, $request->user());

        return response()->json(['message' => __('Application rejected.')]);
    }

    private function authorizeReviewer(Request $request, ProgramEnrollment $enrollment): void
    {
        $cohort = $enrollment->cohort;
        abort_if($cohort === null, 422);
        abort_unless($this->participation->canManageCohort($request->user(), $cohort), 403);
    }
}
