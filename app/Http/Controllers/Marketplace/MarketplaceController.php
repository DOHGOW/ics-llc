<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\MarketplaceListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public marketplace (Wave 4c). Only PUBLISHED + non-expired listings are visible
 * (publicVisible scope, D-060 lazy expiry). Applicant PII is never here — public sees the
 * listing + application_count only.
 */
class MarketplaceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $listings = MarketplaceListing::query()->publicVisible()
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('q'), fn ($q) => $q->whereFullText(['title', 'description'], (string) $request->string('q')))
            ->select(['id', 'title', 'type', 'deadline', 'value', 'currency', 'location', 'is_remote', 'application_count', 'published_at'])
            ->orderByDesc('published_at')
            ->paginate(15);

        return response()->json($listings);
    }

    public function show(MarketplaceListing $listing): JsonResponse
    {
        abort_unless(MarketplaceListing::query()->publicVisible()->whereKey($listing->id)->exists(), 404);

        return response()->json($listing->only([
            'id', 'title', 'description', 'type', 'deadline', 'value', 'currency',
            'requirements', 'location', 'is_remote', 'application_count', 'published_at',
        ]));
    }
}
