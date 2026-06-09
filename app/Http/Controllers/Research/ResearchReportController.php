<?php

namespace App\Http\Controllers\Research;

use App\Http\Controllers\Controller;
use App\Services\Research\ResearchAnalyticsAggregator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Research analytics (Wave 3, D-025 hook). Gated by research.reports.view. */
class ResearchReportController extends Controller
{
    public function __construct(private readonly ResearchAnalyticsAggregator $aggregator) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('research.reports.view'), 403);

        return response()->json($this->aggregator->snapshot());
    }
}
