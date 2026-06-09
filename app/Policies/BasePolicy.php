<?php

namespace App\Policies;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Base policy — default-deny + ownership/account-scoping helpers.
 *
 * Every module policy extends this. Abilities not explicitly granted return false
 * (default-deny). Org-owned policies (client_/partner_/startup_) MUST use these
 * helpers — in Phase 1 they are the SOLE isolation control between organizations
 * because the database TenantScope is deferred to Phase 3 (D-037 / audit R-3).
 */
abstract class BasePolicy
{
    /** Record is owned by the user (by owner_id key, default 'user_id'). */
    protected function owns(User $user, Model $model, string $ownerKey = 'user_id'): bool
    {
        return isset($model->{$ownerKey}) && (int) $model->{$ownerKey} === (int) $user->id;
    }

    /**
     * Record belongs to the user's organisation/account. Requires the user→account
     * linkage established in the CRM/Client/Partner sprints (account_id). Until then
     * this denies (safe default).
     */
    protected function sameAccount(User $user, Model $model, string $accountKey = 'account_id'): bool
    {
        return isset($user->account_id, $model->{$accountKey})
            && (int) $model->{$accountKey} === (int) $user->account_id;
    }

    /**
     * Tenant match — present for Phase 3 multi-tenancy. In Phase 1 (single tenant)
     * tenant_id is NULL for ICS-owned data; this returns true for the NULL/NULL case.
     */
    protected function sameTenant(User $user, Model $model, string $tenantKey = 'tenant_id'): bool
    {
        return ($user->tenant_id ?? null) === ($model->{$tenantKey} ?? null);
    }
}
