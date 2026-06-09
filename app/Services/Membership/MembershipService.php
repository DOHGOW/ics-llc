<?php

namespace App\Services\Membership;

use App\Billing\MembershipTierResolver;
use App\Events\Membership\MembershipEntitlementChanged;
use App\Models\Billing\BillingPlan;
use App\Models\Billing\BillingSubscription;
use App\Models\Core\User;
use Illuminate\Support\Collection;

/**
 * Membership entitlement PROJECTION (D-080/D-087, scope item 4). Membership is NOT a new module — it
 * is a typed use of Billing: a membership = an active billing_subscription to a module='membership'
 * plan. This service PROJECTS live subscription state into a clean membership view + content-tier
 * entitlement (delegating tier computation to MembershipTierResolver, the ContentAccessService hook).
 *
 * Entitlement is LIVE (C-3): every read derives from BillingSubscription::isEntitling() — there is NO
 * cached/stored grant, so cancel/expire/refund/past_due revokes membership IMMEDIATELY. Tenant-aware:
 * BillingSubscription/BillingPlan are TenantScoped (BelongsToTenant), so all reads/writes are scoped
 * to the current tenant automatically (C-4).
 */
class MembershipService
{
    public function __construct(private readonly MembershipTierResolver $tiers) {}

    /** The user's currently-entitling membership subscriptions (live status ∈ {trial, active}). */
    public function activeMembershipsFor(User $user): Collection
    {
        return BillingSubscription::query()
            ->where('user_id', $user->id)
            ->whereIn('status', BillingSubscription::ENTITLING_STATUSES)
            ->with('plan')
            ->get()
            ->filter(fn (BillingSubscription $s) => $s->isEntitling() && $s->plan?->module === 'membership')
            ->values();
    }

    /** Whether the user has ANY live membership entitlement right now. */
    public function isMember(User $user): bool
    {
        return $this->activeMembershipsFor($user)->isNotEmpty();
    }

    /**
     * Entitlement projection: the membership status + the content tiers it grants (Knowledge/Research
     * ONLY — C-2). This is what the UI/consumers read; it is computed live, never cached.
     *
     * @return array{is_member:bool, knowledge_tier:?int, research_tier:?int, memberships:array<int,array<string,mixed>>}
     */
    public function entitlementFor(?User $user): array
    {
        if ($user === null) {
            return ['is_member' => false, 'knowledge_tier' => null, 'research_tier' => null, 'memberships' => []];
        }

        $active = $this->activeMembershipsFor($user);
        $grants = $this->tiers->grantsFor($user); // {knowledge, research}

        return [
            'is_member' => $active->isNotEmpty(),
            'knowledge_tier' => $grants['knowledge'],
            'research_tier' => $grants['research'],
            'memberships' => $active->map(fn (BillingSubscription $s) => [
                'subscription_id' => $s->id,
                'plan' => $s->plan?->name,
                'status' => $s->status,
                'current_period_end' => optional($s->current_period_end)->toIso8601String(),
            ])->all(),
        ];
    }

    /**
     * MANUAL entitlement grant (admin comp / migration) — D-081 HIGH. Creates an ACTIVE membership
     * subscription without payment. Tenant is taken from the plan (TenantScoped). Fires the membership
     * governance event so the manual grant is audited under MEMBERSHIP_MANAGEMENT.
     */
    public function grantManual(User $member, BillingPlan $plan, User $actor, ?string $reason = null): BillingSubscription
    {
        abort_unless($plan->module === 'membership', 422, 'Plan is not a membership plan.');

        $sub = BillingSubscription::create([
            'tenant_id' => $plan->tenant_id,
            'user_id' => $member->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->add($this->periodInterval($plan)),
            'metadata' => ['manual_grant' => true, 'granted_by' => $actor->id, 'reason' => $reason],
        ]);

        event(new MembershipEntitlementChanged($sub, 'granted', true, $actor->id, $actor->getRoleNames()->first()));

        return $sub;
    }

    /**
     * MANUAL entitlement removal (admin) — D-081 HIGH. Cancels the membership subscription; because
     * entitlement is live-status derived, content tiers drop IMMEDIATELY (C-3, no cached grant).
     */
    public function revokeManual(BillingSubscription $sub, User $actor, ?string $reason = null): BillingSubscription
    {
        $sub->forceFill([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason ?? 'admin_membership_revocation',
        ])->save();

        event(new MembershipEntitlementChanged($sub, 'revoked', true, $actor->id, $actor->getRoleNames()->first()));

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
}
