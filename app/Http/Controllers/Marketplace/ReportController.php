<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\ListingReport;
use App\Models\Marketplace\MarketplaceListing;
use App\Services\Marketplace\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Abuse reporting (Wave 4c / D-060). Any authenticated user may report a published listing
 * (one open report per reporter+listing). Reaching the threshold auto-hides the listing.
 * Report creation = analytics; resolution = audited (MARKETPLACE_MANAGEMENT).
 */
class ReportController extends Controller
{
    public function __construct(private readonly ReportService $service) {}

    public function report(Request $request, MarketplaceListing $listing): JsonResponse
    {
        // Any authenticated user; only published listings are reportable.
        abort_unless(MarketplaceListing::query()->publicVisible()->whereKey($listing->id)->exists(), 404);

        $data = $request->validate([
            'reason' => ['required', Rule::in(ListingReport::REASONS)],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->service->report($listing, $request->user(), $data['reason'], $data['details'] ?? null);

        return response()->json(['message' => __('Report submitted. Thank you.')], 201);
    }

    /** Moderator queue of open reports. */
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('marketplace.reports.view'), 403);

        return response()->json(
            ListingReport::query()->where('status', 'open')
                ->select(['id', 'listing_id', 'reason', 'created_at'])->paginate(25)
        );
    }

    public function resolve(Request $request, ListingReport $report): JsonResponse
    {
        abort_unless($request->user()->can('marketplace.listings.reject'), 403); // moderator
        $data = $request->validate(['resolution' => ['required', Rule::in(['reviewed', 'dismissed', 'actioned'])]]);

        $this->service->resolve($report, $data['resolution'], $request->user());

        return response()->json(['message' => __('Report resolved.')]);
    }
}
