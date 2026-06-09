<?php

namespace App\Tenancy\Concerns;

use App\Tenancy\Middleware\TenancyQueueMiddleware;

/**
 * Makes a queued job TENANT-AWARE (D-088). The job carries the originating tenant id and, via the
 * tenancy queue middleware, restores that tenant context while it runs — so TenantScope isolates the
 * job's queries to its tenant. Dispatch with `->onTenant($id)` (defaults to the current context's
 * tenant). Jobs that must span tenants should NOT use this trait; they call acrossTenants()/
 * runAsSuperTenant() explicitly.
 */
trait TenantAware
{
    public ?int $tenantId = null;

    public function onTenant(?int $tenantId): static
    {
        $this->tenantId = $tenantId;

        return $this;
    }

    /** @return array<int,object> */
    public function middleware(): array
    {
        return [new TenancyQueueMiddleware($this->tenantId)];
    }
}
