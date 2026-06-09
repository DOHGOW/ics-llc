<?php

namespace App\Services\Content\Strategies;

use App\Authorization\Roles;
use App\Content\ContentAccessible;
use App\Models\Core\User;

/**
 * Lateral access (Knowledge Center — D-036). Tiers 3 and 4 are PARALLEL, not stacked.
 *   1 public · 2 member (any auth) · 3 CLIENT · 4 PARTNER · 5 internal (ICS staff).
 * Gov Rep is capped at tiers 1/2 (D-044/EP-2 removed Gov tier-4 knowledge).
 * ICS staff / Super Admin access all tiers.
 *
 * Membership boundary (D-082/C-2): tiers 3 (CLIENT) and 4 (PARTNER) are ORG-ROLE gates that
 * require the actual org role — a membership can NEVER satisfy them (it confers the MEMBER
 * dimension, tier 2, ONLY). This is enforced by construction: membership only ever raises the
 * member tier; the org-tier branches below remain role-only. The $membershipTier argument is
 * therefore clamped to the member dimension here — it can grant tier 2, never 3/4/5.
 */
class LateralAccessStrategy implements AccessStrategyContract
{
    public function canAccess(?User $user, ContentAccessible $content, int $membershipTier = 0): bool
    {
        $tier = $content->accessTier();

        if ($tier === 1) {
            return true; // public
        }

        if ($user === null) {
            return false;
        }

        // ICS staff & Super Admin see all tiers.
        if ($user->hasAnyRole(Roles::ICS_INTERNAL)) {
            return true;
        }

        // Membership elevates the MEMBER dimension ONLY (tier 2). It NEVER satisfies the org
        // tiers 3 (CLIENT) / 4 (PARTNER) / 5 (internal) — those require the real role (C-2).
        if ($tier === 2 && $membershipTier >= 2) {
            return true;
        }

        return match ($tier) {
            2 => true,                                   // any authenticated member
            3 => $user->hasRole(Roles::CLIENT_ADMIN),    // client (membership can NOT grant — C-2)
            4 => $user->hasRole(Roles::PARTNER_ADMIN),   // partner (NOT Gov Rep — D-044; membership can NOT grant — C-2)
            5 => false,                                  // internal only (handled above)
            default => false,
        };
    }
}
