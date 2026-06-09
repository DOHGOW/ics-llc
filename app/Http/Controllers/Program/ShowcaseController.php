<?php

namespace App\Http\Controllers\Program;

use App\Http\Controllers\Controller;
use App\Models\Startup\ProgramCohort;
use App\Services\Startup\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Investor Showcase (Wave 5C / H-1 / D-069). EXPOSURE / DISCOVERY ONLY — a curated, public-
 * projection list of a cohort's startups. NOT a deal room, fundraising workflow, or investor
 * portal. No cap-table / financials / data-room (C-1/H-3). Investor identities are referenced
 * from existing ecosystem identities (H-2) — there is NO investor registry here.
 */
class ShowcaseController extends Controller
{
    public function __construct(private readonly EventService $events) {}

    public function exposure(Request $request, ProgramCohort $cohort): JsonResponse
    {
        // Discovery: any authenticated user may browse the showcase (curated public fields only).
        // Finer investor gating arrives with the Investment Network (5d).
        return response()->json(['startups' => $this->events->showcaseExposure($cohort)]);
    }
}
