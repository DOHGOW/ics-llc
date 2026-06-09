<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Services\Crm\CrmPipelineAggregator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRM pipeline reporting (Wave 1d). Surfaces the analytics aggregation hook (D-025).
 * Gated by crm.reports.view. In production the Executive Dashboard reads the PERSISTED
 * aggregates (refreshed by a scheduled job); this endpoint exposes the same snapshot for
 * in-module reporting.
 */
class CrmReportController extends Controller
{
    public function __construct(private readonly CrmPipelineAggregator $aggregator) {}

    public function pipeline(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('crm.reports.view'), 403);

        return response()->json($this->aggregator->snapshot());
    }
}
