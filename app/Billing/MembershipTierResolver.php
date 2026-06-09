<?php

namespace App\Billing;

use App\Models\Billing\BillingSubscription;
use App\Models\Core\User;

/**
 * Membership entitlement HOOK (D-080 / scope item 9) — READ-ONLY. This is the integration seam for
 * the Membership wave: it computes the content-tier grants a user is entitled to from their LIVE
 * membership subscriptions (status ∈ {trial, active}, period not lapsed — C-3, no cached grant).
 *
 * IMPORTANT: the Billing wave provides this resolver ONLY. ContentAccessService is NOT modified here
 * (Membership is a separate approval gate). When Membership lands, the strategies will consult this
 * resolver as `effectiveTier = max(roleTier, membershipTier)` — ELEVATE-ONLY (C-1), content tiers
 * ONLY (C-2: knowledge/research, never org/CRM/portal/admin).
 */
class MembershipTierResolver
{
    /** @return array{knowledge:?int, research:?int} the highest membership-granted tier per content module. */
    public function grantsFor(?User $user): array
    {
        if ($user === null) {
            return ['knowledge' => null, 'research' => null];
        }

        $subs = BillingSubscription::query()
            ->where('user_id', $user->id)
            ->whereIn('status', BillingSubscription::ENTITLING_STATUSES)
            ->with('plan')
            ->get()
            ->filter(fn (BillingSubscription $s) => $s->isEntitling() && $s->plan?->module === 'membership');

        return [
            'knowledge' => $this->maxGrant($subs, 'knowledge_tier_grant'),
            'research' => $this->maxGrant($subs, 'research_tier_grant'),
        ];
    }

    private function maxGrant($subs, string $column): ?int
    {
        $max = $subs->map(fn (BillingSubscription $s) => $s->plan?->{$column})->filter()->max();

        return $max !== null ? (int) $max : null;
    }
}
