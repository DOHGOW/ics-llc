<?php

namespace App\Http\Controllers\Startup;

use App\Http\Controllers\Controller;
use App\Models\Startup\ProgramEnrollment;
use App\Models\Startup\Startup;
use App\Models\Startup\StartupProgram;
use App\Services\Startup\StartupAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Startup programs + enrollment (Wave 5A; Incubator/Accelerator extend in 5b/5c). Program
 * participation is the membership key for the incubation/acceleration lifecycle stages.
 */
class ProgramController extends Controller
{
    public function __construct(private readonly StartupAccessService $access) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            StartupProgram::query()->whereIn('status', ['planned', 'active'])
                ->select(['id', 'name', 'type', 'cohort_name', 'start_date', 'status'])->paginate(25)
        );
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('startup.programs.manage'), 403);

        $program = StartupProgram::create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(StartupProgram::TYPES)],
            'cohort_name' => ['nullable', 'string', 'max:100'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'max_startups' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
        ]));

        return response()->json(['id' => $program->id], 201);
    }

    public function enrol(Request $request, Startup $startup): JsonResponse
    {
        // Founder/admin requests enrolment; staff approve programmatically. Here: managed by staff/owner.
        abort_unless($this->access->canManage($request->user(), $startup), 403);
        $data = $request->validate(['program_id' => ['required', 'integer', 'exists:startup_programs,id']]);

        $enrollment = ProgramEnrollment::firstOrCreate(
            ['startup_id' => $startup->id, 'program_id' => $data['program_id']],
            ['enrolled_at' => now(), 'status' => 'active'],
        );

        return response()->json(['enrollment_id' => $enrollment->id], 201);
    }
}
