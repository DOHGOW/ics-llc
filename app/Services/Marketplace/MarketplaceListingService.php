<?php

namespace App\Services\Marketplace;

use App\Events\Marketplace\ListingReviewed;
use App\Models\Core\User;
use App\Models\Marketplace\ListingReview;
use App\Models\Marketplace\MarketplaceListing;
use Illuminate\Support\Str;

/**
 * Marketplace listing lifecycle (Wave 4c / D-011 / D-060). Mandatory pre-publication review
 * (no auto-publish). Distinct from HasContentLifecycle (W4-3). Approve/reject/remove are
 * audited via ListingReviewed (MARKETPLACE_MANAGEMENT).
 */
class MarketplaceListingService
{
    /** Owner submits a draft for review. Returns a duplicate-suspected flag for the reviewer. */
    public function submit(MarketplaceListing $listing): array
    {
        $listing->forceFill(['status' => 'pending_review'])->save();

        return ['status' => 'pending_review', 'duplicate_suspected' => $this->duplicateSuspected($listing)];
    }

    public function approve(MarketplaceListing $listing, User $reviewer, ?string $notes = null): MarketplaceListing
    {
        $listing->forceFill(['status' => 'published', 'published_at' => $listing->published_at ?? now()])->save();
        $this->recordReview($listing, $reviewer, 'approve', $notes);
        event(new ListingReviewed($listing, 'approved', $notes, $reviewer->id, $reviewer->getRoleNames()->first()));

        return $listing;
    }

    public function reject(MarketplaceListing $listing, User $reviewer, ?string $notes = null): MarketplaceListing
    {
        $listing->forceFill(['status' => 'rejected'])->save();
        $this->recordReview($listing, $reviewer, 'reject', $notes);
        event(new ListingReviewed($listing, 'rejected', $notes, $reviewer->id, $reviewer->getRoleNames()->first()));

        return $listing;
    }

    /** Post-publication moderation removal (HIGH audit). */
    public function remove(MarketplaceListing $listing, User $reviewer, ?string $notes = null): MarketplaceListing
    {
        $listing->forceFill(['status' => 'removed'])->save();
        event(new ListingReviewed($listing, 'removed', $notes, $reviewer->id, $reviewer->getRoleNames()->first()));

        return $listing;
    }

    /** Scheduled auto-expiry: published listings past their deadline → expired (D-060). */
    public function expireOverdue(): int
    {
        return MarketplaceListing::query()
            ->where('status', 'published')
            ->whereNotNull('deadline')
            ->whereDate('deadline', '<', now()->toDateString())
            ->update(['status' => 'expired']);
    }

    private function recordReview(MarketplaceListing $listing, User $reviewer, string $decision, ?string $notes): void
    {
        ListingReview::create([
            'listing_id' => $listing->id,
            'reviewed_by' => $reviewer->id,
            'decision' => $decision,
            'notes' => $notes,
            'created_at' => now(),
        ]);
    }

    /** Lightweight duplicate signal (same poster + same normalised title, not rejected). */
    private function duplicateSuspected(MarketplaceListing $listing): bool
    {
        $normalised = Str::slug($listing->title);

        return MarketplaceListing::query()
            ->where('posted_by_id', $listing->posted_by_id)
            ->where('id', '!=', $listing->id)
            ->whereNotIn('status', ['rejected', 'removed', 'expired'])
            ->get(['id', 'title'])
            ->contains(fn ($other) => Str::slug($other->title) === $normalised);
    }
}
