<?php

namespace Tests\Feature\Membership;

use App\Authorization\Roles;
use App\Authorization\Scopes\TenantScope;
use App\Content\AccessStrategy;
use App\Content\ContentAccessible;
use App\Models\Billing\BillingPlan;
use App\Models\Billing\BillingSubscription;
use App\Models\Core\User;
use App\Services\Content\ContentAccessService;
use App\Services\Membership\MembershipService;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Membership entitlement validation (1–8, D-087). Mandatory release gate. Covers immediate
 * activation/revocation, knowledge + research tier elevation, NO portal/CRM escalation (the C-2
 * boundary), TenantScope compatibility, and Billing integration integrity.
 *
 * Membership elevation is ELEVATE-ONLY (C-1), LIVE-status (C-3), content-tiers-ONLY (C-2). The
 * genuine premium-content elevation is demonstrable in RESEARCH (hierarchical/stacked); KNOWLEDGE
 * (lateral) confers the MEMBER dimension only and STRUCTURALLY cannot grant org tiers (M-DN-1).
 */
class MembershipEntitlementTest extends TestCase
{
    use RefreshDatabase;

    private function membershipPlan(array $overrides = []): BillingPlan
    {
        return BillingPlan::withoutGlobalScopes()->create(array_merge([
            'tenant_id' => 1, 'name' => 'Pro', 'slug' => 'pro-'.uniqid(), 'type' => 'subscription',
            'module' => 'membership', 'billing_period' => 'monthly', 'price' => 10, 'currency' => 'NGN',
        ], $overrides));
    }

    private function membership(int $tenant, int $userId, int $planId, string $status = 'active'): BillingSubscription
    {
        return BillingSubscription::withoutGlobalScopes()->create([
            'tenant_id' => $tenant, 'user_id' => $userId, 'plan_id' => $planId, 'status' => $status,
            'current_period_start' => now(), 'current_period_end' => now()->addMonth(),
            'gateway_subscription_id' => 'SUB-'.uniqid(),
        ]);
    }

    /** A non-DB ContentAccessible double — isolates the access decision from content schema. */
    private function content(string $module, string $strategy, int $tier, bool $published = true): ContentAccessible
    {
        return new class($module, $strategy, $tier, $published) implements ContentAccessible
        {
            public function __construct(
                private string $module,
                private string $strategy,
                private int $tier,
                private bool $published,
            ) {}

            public function accessStrategy(): string
            {
                return $this->strategy;
            }

            public function accessTier(): int
            {
                return $this->tier;
            }

            public function isPublished(): bool
            {
                return $this->published;
            }

            public function contentModule(): string
            {
                return $this->module;
            }
        };
    }

    /** 1 — immediate entitlement activation: an active membership grants the tier at once. */
    public function test_1_immediate_activation(): void
    {
        $user = User::factory()->create(['tenant_id' => 1]);
        $plan = $this->membershipPlan(['research_tier_grant' => 3]);
        $this->membership(1, $user->id, $plan->id, 'active');

        $this->assertTrue(app(MembershipService::class)->isMember($user));
        $this->assertSame(3, app(MembershipService::class)->entitlementFor($user)['research_tier']);
    }

    /** 2 — immediate entitlement revocation: cancelling drops the tier at once (no cached grant, C-3). */
    public function test_2_immediate_revocation(): void
    {
        $user = User::factory()->create(['tenant_id' => 1]);
        $plan = $this->membershipPlan(['research_tier_grant' => 3]);
        $sub = $this->membership(1, $user->id, $plan->id, 'active');

        app(MembershipService::class)->revokeManual($sub, $user, 'test');

        $this->assertFalse(app(MembershipService::class)->isMember($user->fresh()));
        $this->assertNull(app(MembershipService::class)->entitlementFor($user->fresh())['research_tier']);
    }

    /** 3 — knowledge tier elevation: the knowledge grant is surfaced + applied (member dimension). */
    public function test_3_knowledge_tier_elevation(): void
    {
        $user = User::factory()->create(['tenant_id' => 1]);
        $plan = $this->membershipPlan(['knowledge_tier_grant' => 2]);
        $this->membership(1, $user->id, $plan->id, 'active');

        $this->assertSame(2, app(MembershipService::class)->entitlementFor($user)['knowledge_tier']);
        // Member-tier knowledge is accessible through the membership-aware content gate.
        $this->assertTrue(app(ContentAccessService::class)->canAccess(
            $user->fresh(), $this->content('knowledge', AccessStrategy::LATERAL, 2)
        ));
    }

    /** 4 — research tier elevation: a non-member is DENIED, the member is GRANTED (genuine elevation). */
    public function test_4_research_tier_elevation(): void
    {
        $service = app(ContentAccessService::class);
        $tier3Research = $this->content('research', AccessStrategy::HIERARCHICAL, 3);

        $nonMember = User::factory()->create(['tenant_id' => 1]);
        $this->assertFalse($service->canAccess($nonMember->fresh(), $tier3Research), 'Baseline: non-member cannot see tier-3 research');

        $member = User::factory()->create(['tenant_id' => 1]);
        $plan = $this->membershipPlan(['research_tier_grant' => 3]);
        $this->membership(1, $member->id, $plan->id, 'active');
        $this->assertTrue($service->canAccess($member->fresh(), $tier3Research), 'Membership must elevate to tier-3 research');
    }

    /** 5 — NO portal privilege escalation: membership never grants Client/Partner (lateral org) access. */
    public function test_5_no_portal_escalation(): void
    {
        $service = app(ContentAccessService::class);
        $member = User::factory()->create(['tenant_id' => 1]);
        // Even a maxed knowledge grant must NOT unlock the CLIENT(3)/PARTNER(4) org tiers (C-2).
        $plan = $this->membershipPlan(['knowledge_tier_grant' => 4]);
        $this->membership(1, $member->id, $plan->id, 'active');

        $this->assertFalse($service->canAccess($member->fresh(), $this->content('knowledge', AccessStrategy::LATERAL, 3)),
            'Membership must NOT grant CLIENT (portal) knowledge');
        $this->assertFalse($service->canAccess($member->fresh(), $this->content('knowledge', AccessStrategy::LATERAL, 4)),
            'Membership must NOT grant PARTNER (portal) knowledge');
        // And membership confers NO org role.
        $this->assertFalse($member->fresh()->hasRole(Roles::CLIENT_ADMIN));
        $this->assertFalse($member->fresh()->hasRole(Roles::PARTNER_ADMIN));
    }

    /** 6 — NO CRM privilege escalation: the resolver output is consumed ONLY by content access. */
    public function test_6_no_crm_escalation(): void
    {
        $member = User::factory()->create(['tenant_id' => 1]);
        $plan = $this->membershipPlan(['research_tier_grant' => 3, 'knowledge_tier_grant' => 2]);
        $this->membership(1, $member->id, $plan->id, 'active');

        // Membership grants content tiers; it confers no CRM/admin role whatsoever (C-2).
        foreach ([Roles::ICS_CRM, Roles::PLATFORM_ADMIN, Roles::SUPER_ADMIN, Roles::FRANCHISE_ADMIN] as $role) {
            $this->assertFalse($member->fresh()->hasRole($role), "Membership must NOT grant {$role}");
        }
        // CMS content is NOT a membership-elevatable module — grant has no effect there.
        $this->assertSame(0, $this->invokeMembershipTier($member, $this->content('cms', AccessStrategy::LATERAL, 3)));
    }

    /**
     * 7 — TenantScope compatibility (C-4). Membership entitlement rides the SAME billing models that
     * join the TenantScope family, and tenant context stamps membership writes. (Functional cross-
     * tenant isolation filtering is exercised by the Billing substrate's own test_d under the same
     * scope — Membership adds NO new tenancy mechanism; it reuses Billing's.)
     */
    public function test_7_tenant_scope_compatibility(): void
    {
        // C-4: the membership-bearing billing models are in the TenantScope family.
        $this->assertArrayHasKey(TenantScope::class, (new BillingSubscription)->getGlobalScopes());
        $this->assertArrayHasKey(TenantScope::class, (new BillingPlan)->getGlobalScopes());

        // Tenant-aware writes: a membership created under tenant 7 is stamped tenant 7.
        config(['ics.tenancy.enabled' => true]);
        app(TenantContext::class)->set(7);

        $plan = $this->membershipPlan(['tenant_id' => 7, 'research_tier_grant' => 3]);
        // User tenant_id left null: core_users.tenant_id is an FK to core_tenants (which has no row 7
        // in this test). The assertion under test is the SUBSCRIPTION's tenant-stamp, not the user's.
        $user = User::factory()->create();
        $sub = BillingSubscription::create([
            'user_id' => $user->id, 'plan_id' => $plan->id, 'status' => 'active',
            'current_period_start' => now(), 'current_period_end' => now()->addMonth(),
        ]);

        $this->assertSame(7, (int) $sub->tenant_id, 'Membership write must be stamped with the current tenant');
    }

    /** 8 — Billing integration integrity: only ENTITLING billing statuses (trial/active) confer membership. */
    public function test_8_billing_integration_integrity(): void
    {
        $service = app(MembershipService::class);
        $plan = $this->membershipPlan(['research_tier_grant' => 3]);

        foreach (['past_due', 'cancelled', 'expired'] as $deadStatus) {
            $user = User::factory()->create(['tenant_id' => 1]);
            $this->membership(1, $user->id, $plan->id, $deadStatus);
            $this->assertFalse($service->isMember($user), "Status {$deadStatus} must NOT entitle membership");
        }

        foreach (['trial', 'active'] as $liveStatus) {
            $user = User::factory()->create(['tenant_id' => 1]);
            $this->membership(1, $user->id, $plan->id, $liveStatus);
            $this->assertTrue($service->isMember($user), "Status {$liveStatus} must entitle membership");
        }
    }

    /** Reach the private ContentAccessService::membershipTierFor to assert CMS yields no elevation. */
    private function invokeMembershipTier(User $user, ContentAccessible $content): int
    {
        $service = app(ContentAccessService::class);
        $ref = new \ReflectionMethod($service, 'membershipTierFor');
        $ref->setAccessible(true);

        return (int) $ref->invoke($service, $user->fresh(), $content);
    }
}
