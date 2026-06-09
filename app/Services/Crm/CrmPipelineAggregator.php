<?php

namespace App\Services\Crm;

use App\Models\Crm\Lead;
use App\Models\Crm\Opportunity;
use Illuminate\Support\Facades\DB;

/**
 * CRM → Analytics aggregation hook (D-025). Computes pipeline KPIs from CURRENT state
 * for the central analytics layer. Designed to be called by a SCHEDULED job (Laravel
 * Task Scheduling), never on the per-request read path — dashboards read the persisted
 * aggregates, not these queries live (D-025 rule).
 *
 * This is the seam: the analytics sprint persists the returned arrays into the
 * aggregation tables / views. CRM does not own those tables.
 */
class CrmPipelineAggregator
{
    /** @return array<string,mixed> */
    public function snapshot(): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'lead_pipeline_value_by_stage' => $this->sumBy(Lead::query(), 'stage', 'value'),
            'opportunity_pipeline_value_by_stage' => $this->sumBy(Opportunity::query(), 'stage', 'value'),
            'leads_by_source' => $this->countBy(Lead::query(), 'source'),
            'opportunity_win_loss' => [
                'won' => Opportunity::where('stage', 'closed_won')->count(),
                'lost' => Opportunity::where('stage', 'closed_lost')->count(),
            ],
            'lead_conversion' => [
                'qualified_or_beyond' => Lead::whereIn('stage', ['qualified', 'proposal', 'negotiation', 'closed_won'])->count(),
                'total' => Lead::count(),
            ],
        ];
    }

    /** @return array<string,float> */
    private function sumBy($query, string $groupColumn, string $sumColumn): array
    {
        return $query->groupBy($groupColumn)
            ->select($groupColumn, DB::raw("COALESCE(SUM({$sumColumn}),0) as total"))
            ->pluck('total', $groupColumn)
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    /** @return array<string,int> */
    private function countBy($query, string $groupColumn): array
    {
        return $query->groupBy($groupColumn)
            ->select($groupColumn, DB::raw('COUNT(*) as total'))
            ->pluck('total', $groupColumn)
            ->map(fn ($v) => (int) $v)
            ->all();
    }
}
