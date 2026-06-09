<?php

namespace Tests\Feature\Tenancy;

use App\Models\Knowledge\KnowledgeArticle;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * MANDATORY RELEASE GATE (Wave FT-1 / FT-1 critical risk). Cross-tenant isolation for the
 * TenantScope mechanism. Representative coverage on a tenant-scoped engine model (KnowledgeArticle);
 * the same matrix is replicated per module in CI. Asserts: scoped reads isolate by tenant; reads
 * FAIL CLOSED when no tenant resolved; explicit super-tenant context sees across tenants; tenancy
 * DISABLED is a no-op (single-tenant). The mechanism is ADDITIVE — AccountScope and the other
 * access families are unmodified.
 */
class CrossTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function context(): TenantContext
    {
        return app(TenantContext::class);
    }

    private function makeArticle(int $tenantId, string $title): KnowledgeArticle
    {
        // tenant_id set explicitly to seed both tenants regardless of context.
        return KnowledgeArticle::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'title' => $title,
            'slug' => Str::slug($title).'-'.uniqid(),
            'access_tier' => 1,
            'status' => 'published',
        ]);
    }

    public function test_tenant_a_cannot_see_tenant_b_rows_when_enabled(): void
    {
        config(['ics.tenancy.enabled' => true]);
        $this->makeArticle(1, 'Tenant 1 Article');
        $this->makeArticle(2, 'Tenant 2 Article');

        $this->context()->set(1);
        $titles = KnowledgeArticle::query()->pluck('title');

        $this->assertTrue($titles->contains('Tenant 1 Article'));
        $this->assertFalse($titles->contains('Tenant 2 Article'), 'Cross-tenant leakage: tenant 1 saw tenant 2 data');
    }

    public function test_reads_fail_closed_when_no_tenant_resolved(): void
    {
        config(['ics.tenancy.enabled' => true]);
        $this->makeArticle(1, 'Some Article');

        $this->context()->set(null); // enabled + unresolved + not super → fail closed
        $this->assertSame(0, KnowledgeArticle::query()->count(), 'Fail-closed violated: rows returned with no tenant');
    }

    public function test_super_tenant_context_sees_across_tenants(): void
    {
        config(['ics.tenancy.enabled' => true]);
        $this->makeArticle(1, 'A1');
        $this->makeArticle(2, 'A2');

        $this->context()->set(1);
        $all = $this->context()->runAsSuperTenant(fn () => KnowledgeArticle::query()->count());

        $this->assertSame(2, $all, 'Super-tenant must see across tenants');
    }

    public function test_disabled_tenancy_is_a_noop(): void
    {
        config(['ics.tenancy.enabled' => false]);
        $this->makeArticle(1, 'X1');
        $this->makeArticle(2, 'X2');

        // No context set; disabled → scope is a no-op (single-tenant).
        $this->assertSame(2, KnowledgeArticle::query()->count());
    }
}
