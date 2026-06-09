<?php

namespace Tests\Feature\Tenancy;

use App\Authorization\Scopes\TenantScope;
use App\Models\Knowledge\KnowledgeArticle;
use App\Tenancy\Concerns\TenantAware;
use App\Tenancy\Middleware\TenancyQueueMiddleware;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * D-088 async tenancy verification. Proves that, with the runningInConsole() blanket bypass REMOVED,
 * tenant isolation is enforced in console/async contexts: queue jobs restore tenant context, the
 * fail-closed default holds when no tenant is bound, super-tenant sees across, and the explicit
 * cross-tenant (scheduler/reconciliation) path still works.
 */
class TenantScopeAsyncTest extends TestCase
{
    use RefreshDatabase;

    private function context(): TenantContext
    {
        return app(TenantContext::class);
    }

    private function makeArticle(int $tenantId, string $title): void
    {
        KnowledgeArticle::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'title' => $title,
            'slug' => Str::slug($title).'-'.uniqid(),
            'access_tier' => 1,
            'status' => 'published',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['ics.tenancy.enabled' => true]);
        $this->makeArticle(1, 'Tenant 1 Article');
        $this->makeArticle(2, 'Tenant 2 Article');
    }

    /** Queue-context restoration: a job bound to tenant 1 sees ONLY tenant 1 rows. */
    public function test_queue_middleware_restores_tenant_context(): void
    {
        $seen = (new TenancyQueueMiddleware(1))->handle(
            new \stdClass,
            fn () => KnowledgeArticle::query()->pluck('title')->all()
        );

        $this->assertContains('Tenant 1 Article', $seen);
        $this->assertNotContains('Tenant 2 Article', $seen, 'Queue job leaked another tenant\'s rows');
    }

    /** Fail-closed in async: a job with NO tenant bound (null) sees nothing. */
    public function test_async_fails_closed_without_tenant(): void
    {
        $count = (new TenancyQueueMiddleware(null))->handle(
            new \stdClass,
            fn () => KnowledgeArticle::query()->count()
        );

        $this->assertSame(0, $count, 'Async fail-closed violated: rows visible with no tenant bound');
    }

    /** Super-tenant (HQ) context sees across tenants. */
    public function test_super_tenant_sees_across_in_async(): void
    {
        $all = $this->context()->runAsSuperTenant(fn () => KnowledgeArticle::query()->count());
        $this->assertSame(2, $all);
    }

    /**
     * Scheduler/reconciliation pattern: an EXPLICIT cross-tenant query spans tenants (downgrade-only
     * jobs). Trait-using models expose acrossTenants(); registry-scoped models (like KnowledgeArticle)
     * use the underlying withoutGlobalScope(TenantScope::class) that acrossTenants() wraps.
     */
    public function test_scheduler_explicit_cross_tenant_spans_tenants(): void
    {
        $this->context()->set(1); // even bound to one tenant…
        $all = KnowledgeArticle::withoutGlobalScope(TenantScope::class)->count();
        $this->assertSame(2, $all); // …explicit cross-tenant still spans all
    }

    /** The TenantAware trait wires the queue middleware with the chosen tenant id. */
    public function test_tenant_aware_trait_attaches_middleware(): void
    {
        $job = new class
        {
            use TenantAware;
        };
        $job->onTenant(5);

        $mw = $job->middleware();
        $this->assertInstanceOf(TenancyQueueMiddleware::class, $mw[0]);
        $this->assertSame(5, $mw[0]->tenantId);
    }
}
