<?php

namespace App\Services\Membership;

use App\Models\Billing\BillingSubscription;
use Illuminate\Support\Carbon;

/**
 * Membership analytics (D-025/D-087, scope item 7). Per-tenant aggregates over the membership
 * subscription stream — active members, trialing, MRR (normalized to a monthly figure), tier
 * distribution, and churn over a window. Tenant-aware automatically (BillingSubscription is
 * TenantScoped). FINANCIAL AGGREGATES ONLY — no card data, no PII (D-032).
 */
class MembershipAnalyticsService
{
    /** @return array<string,mixed> */
    public function summary(int $churnWindowDays = 30): array
    {
        $active = $this->membershipSubs()
            ->whereIn('status', BillingSubscription::ENTITLING_STATUSES)
            ->with('plan:id,name,billing_period,price,knowledge_tier_grant,research_tier_grant')
            ->get();

        $entitling = $active->filter(fn (BillingSubscription $s) => $s->isEntitling());

        $since = Carbon::now()->subDays($churnWindowDays);
        $churned = $this->membershipSubs()
            ->whereIn('status', ['cancelled', 'expired'])
            ->where('cancelled_at', '>=', $since)
            ->count();

        return [
            'active_members' => $entitling->where('status', 'active')->count(),
            'trialing' => $entitling->where('status', 'trial')->count(),
            'mrr' => round($entitling->sum(fn (BillingSubscription $s) => $this->monthlyValue($s)), 2),
            'currency' => config('ics.billing.currency'),
            'tier_distribution' => $this->tierDistribution($entitling),
            'churn_last_'.$churnWindowDays.'d' => $churned,
        ];
    }

    /** Base query: subscriptions to membership plans (within the current tenant). */
    private function membershipSubs()
    {
        return BillingSubscription::query()->whereHas('plan', fn ($q) => $q->where('module', 'membership'));
    }

    /** Normalize a subscription's plan price to a monthly recurring figure. */
    private function monthlyValue(BillingSubscription $s): float
    {
        $price = (float) ($s->plan?->price ?? 0);

        return match ($s->plan?->billing_period) {
            'annual' => $price / 12,
            'quarterly' => $price / 3,
            default => $price,
        };
    }

    /** @return array<string,int> distribution by plan name (no PII). */
    private function tierDistribution($subs): array
    {
        return $subs->groupBy(fn (BillingSubscription $s) => $s->plan?->name ?? 'unknown')
            ->map->count()
            ->toArray();
    }
}
