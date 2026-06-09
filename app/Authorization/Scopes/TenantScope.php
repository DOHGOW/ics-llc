<?php

namespace App\Authorization\Scopes;

use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Tenant isolation scope (D-076 / FT-1). The OUTER wall — composes ABOVE AccountScope
 * (tenant > account > user, D-050 #4). Additive: it modifies NO existing access-control family.
 *
 * Bypass hierarchy (D-088 — context-aware; the blanket runningInConsole() bypass was REMOVED):
 *   - tenancy DISABLED (config, D-037)    → no filter (single-tenant mode; covers migrate/seed default)
 *   - explicit super-tenant (HQ/maint.)   → no filter (cross-tenant; runAsSuperTenant/acrossTenants)
 *   - a resolved tenant                   → WHERE tenant_id = <current>  (HTTP, or queue-restored, D-088)
 *   - else (enabled, unresolved, not super) → WHERE 1=0  (FAIL CLOSED — now incl. console/async)
 *
 * D-088: fail-closed now holds in console/async. System maintenance (migrate/seed) runs with tenancy
 * DISABLED (so this scope is a no-op); queue/scheduled jobs RESTORE the tenant context
 * (TenantContext::runForTenant via the tenancy queue middleware) or operate cross-tenant EXPLICITLY
 * (acrossTenants/runAsSuperTenant, e.g. ReconciliationService). No code path silently crosses tenants.
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        /** @var TenantContext $ctx */
        $ctx = app(TenantContext::class);

        if (! $ctx->tenancyEnabled()) {
            return; // single-tenant: scope is a no-op
        }

        if ($ctx->isSuperTenant()) {
            return; // explicit HQ cross-tenant (audited by the caller)
        }

        $tenantId = $ctx->id();
        if ($tenantId === null) {
            $builder->whereRaw('1 = 0'); // FAIL CLOSED — no tenant resolved

            return;
        }

        $builder->where($model->getTable().'.tenant_id', $tenantId);
    }
}
