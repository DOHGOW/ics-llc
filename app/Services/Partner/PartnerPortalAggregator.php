<?php

namespace App\Services\Partner;

use App\Models\Partner\PartnerProfile;
use App\Models\Partner\PartnerReferral;
use Illuminate\Support\Facades\DB;

/**
 * Partner Portal → Analytics aggregation hook (D-025). Referral funnel + commission KPIs
 * for the analytics layer (scheduled; dashboards read persisted per-partner aggregates).
 * The partner-facing dashboard (PartnerDashboardController) is account-scoped separately.
 */
class PartnerPortalAggregator
{
    /** @return array<string,mixed> */
    public function snapshot(): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'referral_funnel' => PartnerReferral::acrossAccounts()
                ->groupBy('stage')->select('stage', DB::raw('COUNT(*) as total'))
                ->pluck('total', 'stage')->all(),
            'commission_paid_total' => (float) PartnerReferral::acrossAccounts()
                ->whereNotNull('commission_paid_at')->sum('commission_amount'),
            'commission_pending_total' => (float) PartnerReferral::acrossAccounts()
                ->whereNull('commission_paid_at')->whereNotNull('commission_amount')->sum('commission_amount'),
            'active_partners' => PartnerProfile::acrossAccounts()->where('status', 'active')->count(),
        ];
    }
}
