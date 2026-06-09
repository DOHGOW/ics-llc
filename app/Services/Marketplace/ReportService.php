<?php

namespace App\Services\Marketplace;

use App\Events\Marketplace\ListingReportResolved;
use App\Models\Core\User;
use App\Models\Marketplace\ListingReport;
use App\Models\Marketplace\MarketplaceListing;
use Illuminate\Validation\ValidationException;

/**
 * Abuse reporting (Wave 4c / D-060). Report CREATION is analytics (not audited). When OPEN
 * reports on a published listing reach the configured threshold, the listing is auto-hidden
 * (→ pending_review, fail-safe). Report RESOLUTION is audited (MARKETPLACE_MANAGEMENT).
 */
class ReportService
{
    public function report(MarketplaceListing $listing, User $reporter, string $reason, ?string $details): ListingReport
    {
        if (ListingReport::where('listing_id', $listing->id)->where('reporter_id', $reporter->id)->exists()) {
            throw ValidationException::withMessages(['listing' => __('You have already reported this listing.')]);
        }

        $report = ListingReport::create([
            'tenant_id' => $listing->tenant_id,
            'listing_id' => $listing->id,
            'reporter_id' => $reporter->id,
            'reason' => $reason,
            'details' => $details,
            'status' => 'open',
        ]);

        $this->autoHideIfOverThreshold($listing);

        return $report;
    }

    /** Fail-safe: threshold of OPEN reports pulls a published listing back to review. */
    private function autoHideIfOverThreshold(MarketplaceListing $listing): void
    {
        if ($listing->status !== 'published') {
            return;
        }

        $threshold = (int) config('ics.marketplace.report_autohide_threshold', 3);
        $open = ListingReport::where('listing_id', $listing->id)->where('status', 'open')->count();

        if ($open >= $threshold) {
            $listing->forceFill(['status' => 'pending_review'])->save(); // hidden from public until re-reviewed
        }
    }

    public function resolve(ListingReport $report, string $resolution, User $actor): ListingReport
    {
        $report->forceFill(['status' => $resolution, 'reviewed_by' => $actor->id])->save();
        event(new ListingReportResolved($report, $resolution, $actor->id, $actor->getRoleNames()->first()));

        return $report;
    }
}
