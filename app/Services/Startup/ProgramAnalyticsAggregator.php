<?php

namespace App\Services\Startup;

use App\Models\Startup\ProgramEnrollment;
use Illuminate\Support\Facades\DB;

/**
 * Program → Analytics aggregation hook (D-025 / W4-9). GENERIC — reused by Incubator AND
 * Accelerator (D-065), split by program type. Own aggregator (NOT content_engagement_events).
 * No financial/ownership data (C-1).
 */
class ProgramAnalyticsAggregator
{
    /** @param string|null $type filter to 'incubator' / 'accelerator', or null for all. */
    public function snapshot(?string $type = null): array
    {
        $base = ProgramEnrollment::query()
            ->when($type !== null, fn ($q) => $q->whereIn('program_id', function ($sub) use ($type) {
                $sub->select('id')->from('startup_programs')->where('type', $type);
            }));

        $total = (clone $base)->count();
        $accepted = (clone $base)->whereIn('status', ['active', 'graduated'])->count();
        $graduated = (clone $base)->where('status', 'graduated')->count();
        $withdrawn = (clone $base)->whereIn('status', ['withdrawn', 'removed'])->count();

        return [
            'generated_at' => now()->toIso8601String(),
            'program_type' => $type ?? 'all',
            'participation_by_status' => (clone $base)
                ->groupBy('status')->select('status', DB::raw('COUNT(*) as total'))
                ->pluck('total', 'status')->all(),
            'intake_acceptance_rate' => $total > 0 ? round($accepted / $total * 100, 1) : 0.0,
            'graduation_rate' => $accepted > 0 ? round($graduated / $accepted * 100, 1) : 0.0,
            'withdrawal_rate' => $total > 0 ? round($withdrawn / $total * 100, 1) : 0.0,
            'active' => (clone $base)->where('status', 'active')->count(),
        ];
    }
}
