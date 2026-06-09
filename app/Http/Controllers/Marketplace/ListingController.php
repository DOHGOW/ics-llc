<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\MarketplaceListing;
use App\Services\Marketplace\MarketplaceListingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Listing authoring (Wave 4c). Posting rights restricted (marketplace.listings.create:
 * ICS/approved partners/orgs, D-011). Owner drafts → submits for mandatory review. No
 * auto-publish.
 */
class ListingController extends Controller
{
    public function __construct(private readonly MarketplaceListingService $service) {}

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('marketplace.listings.create'), 403);

        $data = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:marketplace_categories,id'],
            'organisation_id' => ['nullable', 'integer', 'exists:crm_accounts,id'], // provenance only
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'type' => ['required', 'in:grant,tender,job,internship,scholarship,fellowship,accelerator'],
            'deadline' => ['nullable', 'date', 'after:today'],
            'value' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'requirements' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:150'],
            'is_remote' => ['nullable', 'boolean'],
        ]);

        $listing = MarketplaceListing::create($data + [
            'posted_by_id' => $request->user()->id,
            'status' => 'draft',
        ]);

        return response()->json(['id' => $listing->id], 201);
    }

    public function submit(Request $request, MarketplaceListing $listing): JsonResponse
    {
        abort_unless($listing->ownedBy($request->user()) && $request->user()->can('marketplace.listings.create'), 403);
        abort_unless(in_array($listing->status, ['draft', 'rejected'], true), 422);

        $result = $this->service->submit($listing); // → pending_review (no auto-publish)

        return response()->json($result);
    }

    public function mine(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('marketplace.listings.create'), 403);

        return response()->json(
            MarketplaceListing::query()->where('posted_by_id', $request->user()->id)
                ->select(['id', 'title', 'type', 'status', 'deadline', 'application_count'])->paginate(25)
        );
    }
}
