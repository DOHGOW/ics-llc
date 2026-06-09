<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Services\Community\CommunityAnalyticsAggregator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Community analytics (Wave 4b, D-025 hook). Gated by community.profile.read.all (staff). */
class CommunityReportController extends Controller
{
    public function __construct(private readonly CommunityAnalyticsAggregator $aggregator) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('community.profile.read.all'), 403);

        return response()->json($this->aggregator->snapshot());
    }
}
