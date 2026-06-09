<?php

namespace App\Services\Startup;

use App\Models\Startup\ProgramEnrollment;
use App\Models\Startup\Startup;
use Illuminate\Support\Facades\DB;

/**
 * Startup → Analytics aggregation hook (D-025 / W4-9). Own aggregator (NOT
 * content_engagement_events). C-1: NO identifiable ownership/cap-table data in any projection —
 * only counts/rates and lifecycle distribution. Scheduled job.
 */
class StartupAnalyticsAggregator
{
    /** @return array<string,mixed> */
    public function snapshot(): array
    {
        $enrollments = ProgramEnrollment::query()->count();
        $graduated = ProgramEnrollment::query()->where('status', 'graduated')->count();

        return [
            'generated_at' => now()->toIso8601String(),
            'startups_by_lifecycle' => Startup::query()
                ->groupBy('lifecycle_stage')->select('lifecycle_stage', DB::raw('COUNT(*) as total'))
                ->pluck('total', 'lifecycle_stage')->all(),
            'startups_by_industry' => Startup::query()->whereNotNull('industry')
                ->groupBy('industry')->select('industry', DB::raw('COUNT(*) as total'))
                ->pluck('total', 'industry')->all(),
            'verified' => Startup::query()->where('is_verified', true)->count(),
            'alumni' => Startup::query()->where('lifecycle_stage', 'alumni')->count(),
            'program_graduation_rate' => $enrollments > 0 ? round($graduated / $enrollments * 100, 1) : 0.0,
            // funding-readiness signal = count at investment_ready (NO ownership/financial data, C-1)
            'investment_ready' => Startup::query()->where('lifecycle_stage', 'investment_ready')->count(),
        ];
    }
}
