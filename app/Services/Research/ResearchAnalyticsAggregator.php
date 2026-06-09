<?php

namespace App\Services\Research;

use App\Models\Content\ContentEngagementEvent;
use App\Models\Research\ResearchPublication;
use Illuminate\Support\Facades\DB;

/**
 * Research Center → Analytics aggregation hook (D-025 / D-051). Reads the unified
 * content_engagement_events (view/download/citation) + cached counters for the analytics
 * layer. Scheduled job; dashboards read persisted aggregates.
 */
class ResearchAnalyticsAggregator
{
    /** @return array<string,mixed> */
    public function snapshot(): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'publications_by_tier' => ResearchPublication::query()->where('status', 'published')
                ->groupBy('access_tier')->select('access_tier', DB::raw('COUNT(*) as total'))
                ->pluck('total', 'access_tier')->all(),
            'engagement' => ContentEngagementEvent::query()
                ->where('content_type', ResearchPublication::class)
                ->groupBy('event_type')->select('event_type', DB::raw('COUNT(*) as total'))
                ->pluck('total', 'event_type')->all(),
            'top_cited' => ResearchPublication::query()->where('status', 'published')
                ->orderByDesc('citation_count')->limit(10)->pluck('citation_count', 'title')->all(),
            'total_downloads' => (int) ResearchPublication::query()->sum('download_count'),
        ];
    }
}
