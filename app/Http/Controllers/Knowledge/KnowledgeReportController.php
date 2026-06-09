<?php

namespace App\Http\Controllers\Knowledge;

use App\Http\Controllers\Controller;
use App\Services\Knowledge\KnowledgeAnalyticsAggregator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Knowledge analytics (Wave 3, D-025 hook). Gated by knowledge.reports.view. */
class KnowledgeReportController extends Controller
{
    public function __construct(private readonly KnowledgeAnalyticsAggregator $aggregator) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('knowledge.reports.view'), 403);

        return response()->json($this->aggregator->snapshot());
    }
}
