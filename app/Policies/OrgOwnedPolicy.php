<?php

namespace App\Policies;

use App\Authorization\Roles;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Base policy for organisation-owned models (Wave 1a / D-050) — Layer 2 of the
 * isolation control. Provides `accessible()`: ICS-internal staff bypass the account
 * check (still permission-gated); everyone else must be in the same organisation.
 *
 * Module policies extend this and gate each ability with both a permission and
 * accessible(), e.g.:
 *     public function view(User $u, ClientProject $m): bool {
 *         return $u->can('client.projects.read.own') && $this->accessible($u, $m);
 *     }
 *
 * Super Admin bypasses all via Gate::before. Default-deny otherwise.
 */
abstract class OrgOwnedPolicy extends BasePolicy
{
    protected function isInternalStaff(User $user): bool
    {
        return $user->hasAnyRole(Roles::ICS_INTERNAL);
    }

    /** Internal staff (cross-org per permission) OR same organisation. */
    protected function accessible(User $user, Model $model): bool
    {
        return $this->isInternalStaff($user) || $this->sameAccount($user, $model);
    }
}
