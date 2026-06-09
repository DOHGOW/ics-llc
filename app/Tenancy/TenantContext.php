<?php

namespace App\Tenancy;

/**
 * Request-scoped tenant context (D-076). Holds the current tenant id and the explicit
 * super-tenant flag. Bound as a singleton. Resolution is LAZY (on first id() read) so it works
 * regardless of middleware ordering — Sanctum token auth runs at the route level, so the tenant
 * is resolved at QUERY time (inside the controller, after authentication), never pre-auth.
 *
 * Super-tenant (HQ cross-tenant) access is EXPLICIT — entered only via runAsSuperTenant() and
 * audited by the caller (TENANT_MANAGEMENT). It is never implied by a role alone.
 */
class TenantContext
{
    private ?int $tenantId = null;

    private bool $superTenant = false;

    private bool $resolved = false;

    /** Explicit override (domain-based middleware, HQ context, or tests). */
    public function set(?int $tenantId): void
    {
        $this->tenantId = $tenantId;
        $this->resolved = true;
    }

    /** Lazily resolves on first read (post-auth, query time). */
    public function id(): ?int
    {
        if (! $this->resolved) {
            $this->tenantId = app(TenantResolver::class)->resolve(request());
            $this->resolved = true;
        }

        return $this->tenantId;
    }

    public function isResolved(): bool
    {
        return $this->resolved;
    }

    public function isSuperTenant(): bool
    {
        return $this->superTenant;
    }

    /** Tenancy is active only when enabled by config (D-037 config-only). */
    public function tenancyEnabled(): bool
    {
        return (bool) config('ics.tenancy.enabled', false);
    }

    /**
     * Run a callback with cross-tenant (HQ) visibility. EXPLICIT + the caller MUST audit the act
     * (TENANT_MANAGEMENT). Restores the prior flag afterwards.
     */
    public function runAsSuperTenant(callable $callback): mixed
    {
        $previous = $this->superTenant;
        $this->superTenant = true;
        try {
            return $callback();
        } finally {
            $this->superTenant = $previous;
        }
    }

    /**
     * Run a callback bound to a specific tenant (D-088). The mechanism queue workers and scheduled
     * jobs use to RESTORE tenant context in console/async — so TenantScope isolates correctly instead
     * of relying on the removed runningInConsole() bypass. A null id keeps the fail-closed default.
     * Fully restores the prior context (id/resolved/super) afterwards.
     */
    public function runForTenant(?int $tenantId, callable $callback): mixed
    {
        $prevId = $this->tenantId;
        $prevResolved = $this->resolved;
        $prevSuper = $this->superTenant;

        $this->tenantId = $tenantId;
        $this->resolved = true;
        $this->superTenant = false;

        try {
            return $callback();
        } finally {
            $this->tenantId = $prevId;
            $this->resolved = $prevResolved;
            $this->superTenant = $prevSuper;
        }
    }
}
