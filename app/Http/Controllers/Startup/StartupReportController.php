<?php

namespace App\Http\Controllers\Startup;

use App\Http\Controllers\Controller;
use App\Services\Startup\StartupAnalyticsAggregator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Startup analytics (Wave 5A, D-025 hook). Gated by startup.reports.view. No ownership data (C-1). */
class StartupReportController extends Controller
{
    public function __construct(private readonly StartupAnalyticsAggregator $aggregator) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('startup.reports.view'), 403);

        return response()->json($this->aggregator->snapshot());
    }
}
