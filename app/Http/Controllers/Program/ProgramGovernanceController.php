<?php

namespace App\Http\Controllers\Program;

use App\Http\Controllers\Controller;
use App\Models\Startup\StartupProgram;
use App\Services\Startup\ProgramAnalyticsAggregator;
use App\Services\Startup\ProgramGovernanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Program-level governance + analytics (Wave 5B / D-066 / D-067). Suspension/reinstatement/
 * termination are HIGH-audited (H-2). Analytics are GENERIC (reused for incubator + accelerator).
 */
class ProgramGovernanceController extends Controller
{
    public function __construct(
        private readonly ProgramGovernanceService $governance,
        private readonly ProgramAnalyticsAggregator $analytics,
    ) {}

    public function suspend(Request $request, StartupProgram $program): JsonResponse
    {
        abort_unless($request->user()->can('startup.programs.manage'), 403);
        $this->governance->suspendProgram($program, $request->user());

        return response()->json(['message' => __('Program suspended.')]);
    }

    public function reinstate(Request $request, StartupProgram $program): JsonResponse
    {
        abort_unless($request->user()->can('startup.programs.manage'), 403);
        $this->governance->reinstateProgram($program, $request->user());

        return response()->json(['message' => __('Program reinstated.')]);
    }

    public function terminate(Request $request, StartupProgram $program): JsonResponse
    {
        abort_unless($request->user()->can('startup.programs.manage'), 403);
        $this->governance->terminateProgram($program, $request->user());

        return response()->json(['message' => __('Program terminated.')]);
    }

    public function archive(Request $request, StartupProgram $program): JsonResponse
    {
        abort_unless($request->user()->can('startup.programs.manage'), 403);
        $this->governance->archiveProgram($program, $request->user());

        return response()->json(['message' => __('Program archived.')]);
    }

    public function analytics(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('startup.reports.view'), 403);
        $type = $request->query('type'); // null | incubator | accelerator
        if ($type !== null) {
            $request->validate(['type' => [Rule::in(StartupProgram::TYPES)]]);
        }

        return response()->json($this->analytics->snapshot($type));
    }
}
