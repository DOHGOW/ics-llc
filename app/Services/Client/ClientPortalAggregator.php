<?php

namespace App\Services\Client;

use App\Models\Client\ClientProject;
use App\Models\Client\Deliverable;
use App\Models\Client\Ticket;
use Illuminate\Support\Facades\DB;

/**
 * Client Portal → Analytics aggregation hook (D-025). Per-account KPIs for the analytics
 * layer. Intended for a SCHEDULED job; dashboards read the persisted aggregates scoped to
 * the org — never these live queries per request. Uses acrossAccounts() because aggregation
 * runs in a system context over all orgs (then persists per-account rows).
 */
class ClientPortalAggregator
{
    /** @return array<string,mixed> */
    public function snapshot(): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'projects_by_status' => ClientProject::acrossAccounts()
                ->groupBy('status')->select('status', DB::raw('COUNT(*) as total'))
                ->pluck('total', 'status')->all(),
            'deliverable_approval' => [
                'approved' => Deliverable::where('status', 'approved')->count(),
                'rejected' => Deliverable::where('status', 'rejected')->count(),
                'pending' => Deliverable::whereIn('status', ['draft', 'submitted'])->count(),
            ],
            'tickets_by_status' => Ticket::acrossAccounts()
                ->groupBy('status')->select('status', DB::raw('COUNT(*) as total'))
                ->pluck('total', 'status')->all(),
            'avg_ticket_resolution_hours' => $this->avgResolutionHours(),
        ];
    }

    private function avgResolutionHours(): ?float
    {
        $value = Ticket::acrossAccounts()
            ->whereNotNull('resolved_at')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as hrs'))
            ->value('hrs');

        return $value !== null ? round((float) $value, 1) : null;
    }
}
