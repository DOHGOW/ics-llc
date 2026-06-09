<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use App\Services\Training\TrainingAnalyticsAggregator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Training analytics (Wave 4a, D-025 hook). Gated by training.reports.view. */
class TrainingReportController extends Controller
{
    public function __construct(private readonly TrainingAnalyticsAggregator $aggregator) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('training.reports.view'), 403);

        return response()->json($this->aggregator->snapshot());
    }
}
