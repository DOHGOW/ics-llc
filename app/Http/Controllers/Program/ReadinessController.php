<?php

namespace App\Http\Controllers\Program;

use App\Http\Controllers\Controller;
use App\Models\Startup\ProgramCohort;
use App\Models\Startup\Startup;
use App\Services\Startup\ProgramParticipationService;
use App\Services\Startup\ReadinessCalculator;
use App\Services\Startup\StartupAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Investment readiness signal (Wave 5C / H-3). OPERATIONAL-MATURITY score only — never
 * valuation/equity/financial. Visible to the startup's team or program staff/coordinators.
 * This signal gates accelerator graduation via CompletionValidator (M-2).
 */
class ReadinessController extends Controller
{
    public function __construct(
        private readonly ReadinessCalculator $readiness,
        private readonly StartupAccessService $startupAccess,
        private readonly ProgramParticipationService $participation,
    ) {}

    public function show(Request $request, ProgramCohort $cohort, Startup $startup): JsonResponse
    {
        $allowed = $this->startupAccess->isTeamMember($request->user(), $startup)
            || $this->participation->canManageCohort($request->user(), $cohort);
        abort_unless($allowed, 403);

        $score = $this->readiness->score($startup->id, $cohort->id);

        return response()->json([
            'startup_id' => $startup->id,
            'readiness_score' => $score,            // maturity only (H-3)
            'meets_threshold' => $score !== null && $score >= ReadinessCalculator::GRADUATION_THRESHOLD,
        ]);
    }
}
