<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\MarketplaceApplication;
use App\Models\Marketplace\MarketplaceListing;
use App\Services\Marketplace\ApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Applications (Wave 4c / D-060). PRIVATE — applicant + listing poster + ICS only. Duplicate
 * prevented by DB unique (clean 422). Attachments streamed/gated (W4-7/W2-5). Status changes
 * (poster/ICS) audited.
 */
class ApplicationController extends Controller
{
    public function __construct(private readonly ApplicationService $service) {}

    public function apply(Request $request, MarketplaceListing $listing): JsonResponse
    {
        abort_unless($request->user()->can('marketplace.applications.create'), 403);
        abort_unless(MarketplaceListing::query()->publicVisible()->whereKey($listing->id)->exists(), 404);

        $data = $request->validate([
            'cover_letter' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array'],
        ]);

        $application = $this->service->apply($listing, $request->user(), $data);

        return response()->json(['id' => $application->id], 201);
    }

    public function mine(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('marketplace.applications.read.own'), 403);

        return response()->json(
            MarketplaceApplication::query()->where('applicant_id', $request->user()->id)
                ->with('listing:id,title,type')
                ->select(['id', 'listing_id', 'status', 'submitted_at'])->paginate(25)
        );
    }

    /** Poster (owns the listing) or ICS reviews applications to a listing. */
    public function forListing(Request $request, MarketplaceListing $listing): JsonResponse
    {
        abort_unless($this->canReview($request, $listing), 403);

        return response()->json($listing->applications()
            ->select(['id', 'applicant_id', 'status', 'submitted_at', 'cover_letter'])->paginate(25));
    }

    public function changeStatus(Request $request, MarketplaceApplication $application): JsonResponse
    {
        $listing = $application->listing;
        abort_unless($this->canReview($request, $listing), 403);
        $data = $request->validate(['status' => ['required', Rule::in(MarketplaceApplication::STATUSES)]]);

        $this->service->changeStatus($application, $data['status'], $request->user());

        return response()->json(['message' => __('Application updated.')]);
    }

    /** W4-7/W2-5: gated, streamed attachment delivery — applicant + poster + ICS only. */
    public function downloadAttachment(Request $request, MarketplaceApplication $application, int $index): StreamedResponse
    {
        $isApplicant = (int) $application->applicant_id === (int) $request->user()->id;
        abort_unless($isApplicant || $this->canReview($request, $application->listing), 403);

        $path = $application->attachments[$index] ?? null;
        abort_if($path === null, 404);

        $disk = Storage::disk(config('ics.media.disk', 'public'));
        abort_unless($disk->exists($path), 404);

        return $disk->download($path);
    }

    private function canReview(Request $request, MarketplaceListing $listing): bool
    {
        return $listing->ownedBy($request->user()) || $request->user()->can('marketplace.applications.read.all');
    }
}
