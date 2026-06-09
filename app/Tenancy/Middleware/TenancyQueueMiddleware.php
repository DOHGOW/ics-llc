<?php

namespace App\Tenancy\Middleware;

use App\Tenancy\TenantContext;

/**
 * Queue job middleware (D-088). Restores the originating tenant context for the duration of a job's
 * execution, so TenantScope isolates correctly in the (console) queue worker — replacing the removed
 * blanket runningInConsole() bypass. A null tenant id means the job runs FAIL-CLOSED (no tenant), which
 * is the safe default; jobs that must operate cross-tenant do so EXPLICITLY via acrossTenants()/
 * runAsSuperTenant() instead of carrying a tenant id.
 *
 * Usage: a tenant-aware job adds `new TenancyQueueMiddleware($this->tenantId)` to its middleware().
 * (See App\Tenancy\Concerns\TenantAware.)
 */
class TenancyQueueMiddleware
{
    public function __construct(public readonly ?int $tenantId) {}

    public function handle(object $job, callable $next): mixed
    {
        return app(TenantContext::class)->runForTenant($this->tenantId, fn () => $next($job));
    }
}
