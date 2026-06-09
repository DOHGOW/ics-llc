<?php

namespace App\Services\Billing;

use App\Models\Billing\BillingSubscription;

/**
 * Failure recovery / reconciliation (D-084). Recovers from missed webhooks, gateway outages, and
 * transient processing failures by re-deriving subscription state from the authoritative facts
 * (period end, gateway). It may RESTORE consistency but may NEVER CREATE an entitlement unsupported
 * by subscription state (D-084) — it only expires/downgrades stale subscriptions, never grants.
 */
class ReconciliationService
{
    public function __construct(private readonly SubscriptionService $subscriptions) {}

    /** Expire active/trial subscriptions whose period has lapsed (idempotent; safe to re-run). */
    public function expireLapsed(): int
    {
        $count = 0;
        BillingSubscription::acrossTenants()
            ->whereIn('status', ['active', 'trial'])
            ->whereNotNull('current_period_end')
            ->where('current_period_end', '<', now())
            ->each(function (BillingSubscription $sub) use (&$count) {
                // Re-derive ONLY downward — never grants entitlement (D-084).
                $this->subscriptions->expire($sub);
                $count++;
            });

        return $count;
    }
}
