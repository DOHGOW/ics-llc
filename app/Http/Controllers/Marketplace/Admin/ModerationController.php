<?php

namespace App\Http\Controllers\Marketplace\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\MarketplaceListing;
use App\Services\Marketplace\MarketplaceListingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Listing moderation (Wave 4c / D-011 / D-060). ICS reviewers approve/reject pending listings
 * and remove published ones. Every decision is recorded (marketplace_listing_reviews) and
 * audited (MARKETPLACE_MANAGEMENT; remove = HIGH).
 */
class ModerationController extends Controller
{
    public function __construct(private readonly MarketplaceListingService $service) {}

    /** Review queue: pending_review listings (incl. auto-hidden by reports). */
    public function queue(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('marketplace.listings.approve'), 403);

        return response()->json(
            MarketplaceListing::query()->where('status', 'pending_review')
                ->select(['id', 'title', 'type', 'posted_by_id', 'created_at'])->paginate(25)
        );
    }

    public function approve(Request $request, MarketplaceListing $listing): JsonResponse
    {
        abort_unless($request->user()->can('marketplace.listings.approve'), 403);
        abort_unless($listing->status === 'pending_review', 422);

        $this->service->approve($listing, $request->user(), $request->input('notes'));

        return response()->json(['message' => __('Listing published.')]);
    }

    public function reject(Request $request, MarketplaceListing $listing): JsonResponse
    {
        abort_unless($request->user()->can('marketplace.listings.reject'), 403);
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:1000']]);

        $this->service->reject($listing, $request->user(), $data['notes'] ?? null);

        return response()->json(['message' => __('Listing rejected.')]);
    }

    public function remove(Request $request, MarketplaceListing $listing): JsonResponse
    {
        abort_unless($request->user()->can('marketplace.listings.reject'), 403);
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:1000']]);

        $this->service->remove($listing, $request->user(), $data['notes'] ?? null);

        return response()->json(['message' => __('Listing removed.')]);
    }
}
