<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\ListingReport;
use App\Models\Marketplace\MarketplaceApplication;
use App\Models\Marketplace\MarketplaceListing;
use Illuminate\Support\Facades\DB;

/**
 * Marketplace → Analytics aggregation hook (D-025 / W4-9). Own aggregator (NOT
 * content_engagement_events). Listing views / application creation / report creation are
 * analytics (cached counters / counts), not audit. Scheduled job.
 */
class MarketplaceAnalyticsAggregator
{
    /** @return array<string,mixed> */
    public function snapshot(): array
    {
        $applications = MarketplaceApplication::query()->count();
        $accepted = MarketplaceApplication::query()->where('status', 'accepted')->count();

        return [
            'generated_at' => now()->toIso8601String(),
            'listings_by_status' => MarketplaceListing::query()
                ->groupBy('status')->select('status', DB::raw('COUNT(*) as total'))
                ->pluck('total', 'status')->all(),
            'listings_by_type' => MarketplaceListing::query()->where('status', 'published')
                ->groupBy('type')->select('type', DB::raw('COUNT(*) as total'))
                ->pluck('total', 'type')->all(),
            'applications' => $applications,
            'application_acceptance_rate' => $applications > 0 ? round($accepted / $applications * 100, 1) : 0.0,
            'open_reports' => ListingReport::query()->where('status', 'open')->count(),
        ];
    }
}
