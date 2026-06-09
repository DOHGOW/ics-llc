<?php

namespace App\Services\Community;

use App\Models\Community\CommunityProfile;
use App\Models\Community\Skill;
use Illuminate\Support\Facades\DB;

/**
 * Community → Analytics aggregation hook (D-025 / W4-9). Views/follows/endorsements are
 * ANALYTICS, not audit (W4b-6) — captured here from cached counters. Own aggregator (NOT
 * content_engagement_events; Community is not ContentAccessible). Scheduled job.
 */
class CommunityAnalyticsAggregator
{
    /** @return array<string,mixed> */
    public function snapshot(): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'profiles_by_type' => CommunityProfile::query()
                ->groupBy('profile_type')->select('profile_type', DB::raw('COUNT(*) as total'))
                ->pluck('total', 'profile_type')->all(),
            'verified' => CommunityProfile::query()->where('is_verified', true)->count(),
            'active' => CommunityProfile::query()->where('status', 'active')->count(),
            'suspended' => CommunityProfile::query()->where('status', 'suspended')->count(),
            'total_profile_views' => (int) CommunityProfile::query()->sum('view_count'),
            'total_followers' => (int) CommunityProfile::query()->sum('follower_count'),
            'skills_catalogue' => Skill::query()->count(),
        ];
    }
}
