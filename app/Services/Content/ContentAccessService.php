<?php

namespace App\Services\Content;

use App\Authorization\Roles;
use App\Billing\MembershipTierResolver;
use App\Content\AccessStrategy;
use App\Content\ContentAccessible;
use App\Models\Core\User;
use App\Services\Content\Strategies\AccessStrategyContract;
use App\Services\Content\Strategies\HierarchicalAccessStrategy;
use App\Services\Content\Strategies\LateralAccessStrategy;

/**
 * Unified content access (D-038 / D-051). ONE service evaluating BOTH approved tier
 * patterns, selected by the content's strategy flag. Replaces the retired
 * KnowledgeAccessService and ResearchAccessService.
 *
 * Tier-scoped ONLY — never account-scoped (complete separation from AccountScope,
 * requirement 4). Draft override: unpublished content is visible to ICS staff only.
 *
 * Membership elevation (D-080/D-087, C-1): an active membership subscription may ELEVATE the
 * effective content tier for Knowledge/Research ONLY (C-2), via MembershipTierResolver reading
 * LIVE subscription status (no cached grant — C-3). This is the ONE pre-designed, controlled
 * extension of this service: elevate-only (max), content-tiers-only, regression-tested. The
 * resolver output is consumed ONLY here — it never touches portal/CRM/admin code paths.
 */
class ContentAccessService
{
    public function __construct(
        private readonly HierarchicalAccessStrategy $hierarchical,
        private readonly LateralAccessStrategy $lateral,
        private readonly MembershipTierResolver $membership,
    ) {}

    public function canAccess(?User $user, ContentAccessible $content): bool
    {
        // Draft/unpublished → ICS staff only, regardless of tier.
        if (! $content->isPublished()) {
            return $user !== null && $user->hasAnyRole(Roles::ICS_INTERNAL);
        }

        return $this->strategyFor($content)->canAccess($user, $content, $this->membershipTierFor($user, $content));
    }

    /**
     * Membership-granted content tier for THIS content's module (Knowledge/Research ONLY — C-2).
     * Returns 0 (no elevation) for guests, CMS content, or users without a membership grant.
     * Clamped to ics.membership.max_grant_tier so membership never reaches internal/super content.
     */
    private function membershipTierFor(?User $user, ContentAccessible $content): int
    {
        $module = $content->contentModule();
        if ($user === null || ! in_array($module, ['knowledge', 'research'], true)) {
            return 0; // membership elevates Knowledge/Research only (D-082)
        }

        $grants = $this->membership->grantsFor($user);
        $tier = $module === 'research' ? $grants['research'] : $grants['knowledge'];
        if ($tier === null) {
            return 0;
        }

        return min((int) $tier, (int) config('ics.membership.max_grant_tier', 3));
    }

    public function canDownload(?User $user, ContentAccessible $content, bool $hasFile = true): bool
    {
        return $hasFile && $this->canAccess($user, $content);
    }

    private function strategyFor(ContentAccessible $content): AccessStrategyContract
    {
        return match ($content->accessStrategy()) {
            AccessStrategy::HIERARCHICAL => $this->hierarchical,
            AccessStrategy::LATERAL => $this->lateral,
            default => $this->lateral, // safe default
        };
    }
}
