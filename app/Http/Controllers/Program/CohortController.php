<?php

namespace App\Http\Controllers\Program;

use App\Http\Controllers\Controller;
use App\Models\Startup\ProgramCohort;
use App\Models\Startup\ProgramCoordinator;
use App\Models\Startup\StartupProgram;
use App\Services\Startup\ProgramGovernanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Cohort management (Wave 5B / D-065 / M-2). GENERIC — same for incubator & accelerator.
 * Cohorts (intake cycles) + coordinators are managed by ICS program staff (startup.programs.manage).
 */
class CohortController extends Controller
{
    public function __construct(private readonly ProgramGovernanceService $governance) {}

    public function index(Request $request, StartupProgram $program): JsonResponse
    {
        return response()->json($program->cohorts()->select(['id', 'name', 'status', 'start_date', 'end_date'])->paginate(25));
    }

    public function store(Request $request, StartupProgram $program): JsonResponse
    {
        abort_unless($request->user()->can('startup.programs.manage'), 403);

        $cohort = $program->cohorts()->create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'intake_opens_at' => ['nullable', 'date'],
            'intake_closes_at' => ['nullable', 'date'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'max_startups' => ['nullable', 'integer', 'min:1'],
        ]) + ['status' => 'planned']);

        return response()->json(['id' => $cohort->id], 201);
    }

    public function openIntake(Request $request, ProgramCohort $cohort): JsonResponse
    {
        abort_unless($request->user()->can('startup.programs.manage'), 403);
        $cohort->forceFill(['status' => 'intake_open'])->save();

        return response()->json(['message' => __('Intake opened.')]);
    }

    public function close(Request $request, ProgramCohort $cohort): JsonResponse
    {
        abort_unless($request->user()->can('startup.programs.manage'), 403);
        $this->governance->closeCohort($cohort, $request->user());

        return response()->json(['message' => __('Cohort closed.')]);
    }

    public function archive(Request $request, ProgramCohort $cohort): JsonResponse
    {
        abort_unless($request->user()->can('startup.programs.manage'), 403);
        $this->governance->archiveCohort($cohort, $request->user());

        return response()->json(['message' => __('Cohort archived.')]);
    }

    /** M-2: coordinators manage cohorts (NOT CRM assignment). */
    public function assignCoordinator(Request $request, ProgramCohort $cohort): JsonResponse
    {
        abort_unless($request->user()->can('startup.programs.manage'), 403);
        $data = $request->validate(['user_id' => ['required', 'integer', 'exists:core_users,id']]);

        $coordinator = ProgramCoordinator::firstOrCreate(
            ['cohort_id' => $cohort->id, 'user_id' => $data['user_id']],
            ['assigned_by' => $request->user()->id, 'assigned_at' => now()],
        );

        return response()->json(['id' => $coordinator->id], 201);
    }
}
