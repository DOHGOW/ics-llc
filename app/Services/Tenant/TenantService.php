<?php

namespace App\Services\Tenant;

use App\Authorization\Roles;
use App\Events\Tenant\TenantLifecycleChanged;
use App\Models\Core\Tenant;
use App\Models\Core\User;

/**
 * Tenant / franchise governance (D-079). Every mutation fires TenantLifecycleChanged → audited
 * under TENANT_MANAGEMENT, HIGH (requirement 6). HQ-only (Platform/Super Admin) provisioning.
 */
class TenantService
{
    public function create(array $data, User $actor): Tenant
    {
        $tenant = Tenant::create($data + ['status' => 'active']);
        $this->fire($tenant, 'created', $actor);

        return $tenant;
    }

    public function suspend(Tenant $tenant, User $actor): Tenant
    {
        $tenant->forceFill(['status' => 'suspended'])->save();
        $this->fire($tenant, 'suspended', $actor);

        return $tenant;
    }

    public function activate(Tenant $tenant, User $actor): Tenant
    {
        $tenant->forceFill(['status' => 'active'])->save();
        $this->fire($tenant, 'activated', $actor);

        return $tenant;
    }

    public function transferOwnership(Tenant $tenant, int $ownerUserId, User $actor): Tenant
    {
        $tenant->forceFill(['owner_user_id' => $ownerUserId])->save();
        $this->fire($tenant, 'ownership_transferred', $actor);

        return $tenant;
    }

    /** Elevate a user to Franchise Admin of this tenant (must be in the tenant). */
    public function elevateAdmin(Tenant $tenant, User $user, User $actor): void
    {
        // The user must belong to this tenant.
        abort_unless((int) $user->tenant_id === (int) $tenant->id, 422, 'User does not belong to this tenant.');
        $user->assignRole(Roles::FRANCHISE_ADMIN);
        $this->fire($tenant, 'admin_elevated', $actor);
    }

    public function changeResidency(Tenant $tenant, ?string $countryCode, ?string $region, User $actor): Tenant
    {
        $tenant->forceFill(['country_code' => $countryCode, 'residency_region' => $region])->save();
        $this->fire($tenant, 'residency_changed', $actor);

        return $tenant;
    }

    private function fire(Tenant $tenant, string $action, User $actor): void
    {
        event(new TenantLifecycleChanged($tenant, $action, $actor->id, $actor->getRoleNames()->first()));
    }
}
