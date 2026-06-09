<?php

namespace App\Http\Controllers\Program;

use App\Http\Controllers\Controller;
use App\Models\Startup\ProgramEnrollment;
use App\Services\Startup\ProgramEnrollmentService;
use App\Services\Startup\ProgramParticipationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Active-participation governance (Wave 5B / D-067). Graduation (completion-validated),
 * withdrawal (reason mandatory), forced removal (reason mandatory; HIGH audit), graduation
 * reversal (HIGH audit). Staff/coordinator-gated.
 */
class ParticipationController extends Controller
{
    public function __construct(
        private readonly ProgramEnrollmentService $enrollments,
        private readonly ProgramParticipationService $participation,
    ) {}

    public function graduate(Request $request, ProgramEnrollment $enrollment): JsonResponse
    {
        $this->authorizeManagement($request, $enrollment);
        $this->enrollments->graduate($enrollment, $request->user()); // completion-validated (D-067)

        return response()->json(['message' => __('Startup graduated.')]);
    }

    public function reverseGraduation(Request $request, ProgramEnrollment $enrollment): JsonResponse
    {
        $this->authorizeManagement($request, $enrollment);
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $this->enrollments->reverseGraduation($enrollment, $request->user(), $data['reason']);

        return response()->json(['message' => __('Graduation reversed.')]);
    }

    public function withdraw(Request $request, ProgramEnrollment $enrollment): JsonResponse
    {
        $this->authorizeManagement($request, $enrollment);
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]); // D-067
        $this->enrollments->withdraw($enrollment, $request->user(), $data['reason']);

        return response()->json(['message' => __('Participation withdrawn.')]);
    }

    public function forceRemove(Request $request, ProgramEnrollment $enrollment): JsonResponse
    {
        $this->authorizeManagement($request, $enrollment);
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]); // D-067
        $this->enrollments->forceRemove($enrollment, $request->user(), $data['reason']);

        return response()->json(['message' => __('Startup removed from program.')]);
    }

    // Renamed from authorize() during bootstrap recovery: the base Controller composes
    // AuthorizesRequests (public authorize()), which a private authorize() would illegally shadow.
    private function authorizeManagement(Request $request, ProgramEnrollment $enrollment): void
    {
        $cohort = $enrollment->cohort;
        abort_if($cohort === null, 422);
        abort_unless($this->participation->canManageCohort($request->user(), $cohort), 403);
    }
}
