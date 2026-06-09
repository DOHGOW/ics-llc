<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Services\Marketplace\MarketplaceAnalyticsAggregator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Marketplace analytics (Wave 4c, D-025 hook). Gated by marketplace.reports.view. */
class MarketplaceReportController extends Controller
{
    public function __construct(private readonly MarketplaceAnalyticsAggregator $aggregator) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('marketplace.reports.view'), 403);

        return response()->json($this->aggregator->snapshot());
    }
}
