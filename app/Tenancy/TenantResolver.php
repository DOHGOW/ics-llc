<?php

namespace App\Tenancy;

use App\Models\Core\Tenant;
use Illuminate\Http\Request;

/**
 * Resolves the current tenant for a request (D-076). Mode is config-driven (ics.tenancy.resolver):
 *   - 'user'   → the authenticated user's tenant_id
 *   - 'domain' → core_tenants.domain matching the request host
 * Returns null when tenancy is disabled or unresolved (TenantScope then fails closed for non-system).
 */
class TenantResolver
{
    public function resolve(Request $request): ?int
    {
        if (! (bool) config('ics.tenancy.enabled', false)) {
            return (int) config('ics.tenancy.default_tenant_id', 1); // single-tenant: the root tenant
        }

        $mode = (string) config('ics.tenancy.resolver', 'user');

        if ($mode === 'domain') {
            $tenant = Tenant::query()->where('domain', $request->getHost())->first();

            return $tenant?->id;
        }

        // Default: by authenticated user.
        return $request->user()?->tenant_id;
    }
}
