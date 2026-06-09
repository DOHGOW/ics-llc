<?php

namespace App\Services\Knowledge;

use App\Models\Content\ContentEngagementEvent;
use App\Models\Knowledge\KnowledgeArticle;
use App\Models\Knowledge\KnowledgeResource;
use Illuminate\Support\Facades\DB;

/**
 * Knowledge Center → Analytics aggregation hook (D-025 / D-051). Reads the unified
 * content_engagement_events (+ cached counters) for the analytics layer. Scheduled job;
 * dashboards read persisted aggregates, never these live queries per request.
 */
class KnowledgeAnalyticsAggregator
{
    /** @return array<string,mixed> */
    public function snapshot(): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'articles_by_tier' => KnowledgeArticle::query()->where('status', 'published')
                ->groupBy('access_tier')->select('access_tier', DB::raw('COUNT(*) as total'))
                ->pluck('total', 'access_tier')->all(),
            'top_articles_by_views' => KnowledgeArticle::query()->where('status', 'published')
                ->orderByDesc('view_count')->limit(10)->pluck('view_count', 'title')->all(),
            'engagement' => $this->engagementCounts([KnowledgeArticle::class, KnowledgeResource::class]),
            'resource_downloads' => (int) KnowledgeResource::query()->sum('download_count'),
        ];
    }

    /** @param array<int,string> $types */
    private function engagementCounts(array $types): array
    {
        return ContentEngagementEvent::query()
            ->whereIn('content_type', $types)
            ->groupBy('event_type')->select('event_type', DB::raw('COUNT(*) as total'))
            ->pluck('total', 'event_type')->all();
    }
}
