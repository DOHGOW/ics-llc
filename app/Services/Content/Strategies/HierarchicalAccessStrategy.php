<?php

namespace App\Services\Content\Strategies;

use App\Authorization\Roles;
use App\Content\ContentAccessible;
use App\Models\Core\User;

/**
 * Hierarchical access (Research Center — D-034). Higher roles include all lower
 * tiers: user_tier >= content_tier.
 *   1 public · 2 member (any auth) · 3 partner · 4 internal (ICS staff) · 5 super.
 *
 * Membership elevation (D-080/C-1): the effective tier is max(role-derived, membership-granted).
 * This is the genuine premium-content path — stacked tiers, so a membership research_tier_grant
 * lifts the holder up the stack. ELEVATE-ONLY (max() can never reduce the role baseline). The
 * membership tier is pre-clamped by ContentAccessService (never internal/super — C-2).
 */
class HierarchicalAccessStrategy implements AccessStrategyContract
{
    public function canAccess(?User $user, ContentAccessible $content, int $membershipTier = 0): bool
    {
        return max($this->userTier($user), $membershipTier) >= $content->accessTier();
    }

    private function userTier(?User $user): int
    {
        if ($user === null) {
            return 1;
        }
        if ($user->hasRole(Roles::SUPER_ADMIN)) {
            return 5;
        }
        if ($user->hasAnyRole([Roles::PLATFORM_ADMIN, Roles::ICS_CRM, Roles::ICS_TRAINING, Roles::ICS_CONTENT])) {
            return 4;
        }
        if ($user->hasRole(Roles::PARTNER_ADMIN)) {
            return 3;
        }

        return 2; // any authenticated user
    }
}
