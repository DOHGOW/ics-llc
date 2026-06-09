<?php

namespace App\Services\Billing;

use App\Events\Billing\SubscriptionStateChanged;
use App\Models\Billing\BillingPlan;
use App\Models\Billing\BillingSubscription;
use App\Models\Core\User;

/**
 * Subscription state machine (D-084). trial → active → (past_due →) cancelled/expired.
 * Entitlement is DERIVED from status (BillingSubscription::isEntitling) — there is NO separate
 * grant store, so a state change is itself the entitlement change (immediate revocation, C-3).
 * System transitions are normal-sensitivity; admin overrides are HIGH (D-085).
 */
class SubscriptionService
{
    public function startTrialOrActive(User $user, BillingPlan $plan): BillingSubscription
    {
        $trial = $plan->trial_days > 0;
        $sub = BillingSubscription::create([
            'tenant_id' => $plan->tenant_id,
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => $trial ? 'trial' : 'active',
            'trial_ends_at' => $trial ? now()->addDays($plan->trial_days) : null,
            'current_period_start' => now(),
            'current_period_end' => now()->add($this->periodInterval($plan)),
        ]);
        $this->fire($sub, 'created');

        return $sub;
    }

    public function activate(BillingSubscription $sub): BillingSubscription
    {
        $sub->forceFill(['status' => 'active'])->save();
        $this->fire($sub, 'activated');

        return $sub;
    }

    public function markPastDue(BillingSubscription $sub): BillingSubscription
    {
        $sub->forceFill(['status' => 'past_due'])->save(); // entitlement removed immediately (C-3)
        $this->fire($sub, 'past_due');

        return $sub;
    }

    public function expire(BillingSubscription $sub): BillingSubscription
    {
        $sub->forceFill(['status' => 'expired', 'ends_at' => now()])->save();
        $this->fire($sub, 'expired');

        return $sub;
    }

    /** System or user cancellation (normal). Admin cancellation passes $actor (→ HIGH). */
    public function cancel(BillingSubscription $sub, ?string $reason = null, ?User $actor = null): BillingSubscription
    {
        $sub->forceFill(['status' => 'cancelled', 'cancelled_at' => now(), 'cancellation_reason' => $reason])->save();
        $this->fire($sub, $actor ? 'admin_cancelled' : 'cancelled', $actor !== null, $actor);

        return $sub;
    }

    /** Admin reactivation — HIGH (D-085). */
    public function reactivate(BillingSubscription $sub, User $actor): BillingSubscription
    {
        $sub->forceFill([
            'status' => 'active', 'cancelled_at' => null, 'cancellation_reason' => null,
            'current_period_end' => now()->add($this->periodInterval($sub->plan)),
        ])->save();
        $this->fire($sub, 'admin_reactivated', true, $actor);

        return $sub;
    }

    /** Manual override (admin) — HIGH (D-085). */
    public function override(BillingSubscription $sub, string $status, User $actor): BillingSubscription
    {
        abort_unless(in_array($status, BillingSubscription::STATUSES, true), 422);
        $sub->forceFill(['status' => $status])->save();
        $this->fire($sub, 'override', true, $actor);

        return $sub;
    }

    private function periodInterval(?BillingPlan $plan): \DateInterval
    {
        return match ($plan?->billing_period) {
            'annual' => new \DateInterval('P1Y'),
            'quarterly' => new \DateInterval('P3M'),
            default => new \DateInterval('P1M'),
        };
    }

    private function fire(BillingSubscription $sub, string $action, bool $high = false, ?User $actor = null): void
    {
        event(new SubscriptionStateChanged($sub, $action, $high, $actor?->id, $actor?->getRoleNames()->first()));
    }
}
