<?php

namespace App\Policies;

use App\Authorization\SuperAdminGuard;
use App\Models\Core\User;

/**
 * Authorization for managing core_users (D-021 / D-047 / PERMISSION_MATRIX Module 1).
 *
 * Super Admin bypasses via Gate::before. All others are permission-gated and
 * default-deny. Invariants enforced HERE (policy layer) in addition to the service
 * layer (R-3 last-Super-Admin; no self-action on suspend/deactivate/delete).
 */
class UserPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('platform.users.read.all');
    }

    public function view(User $user, User $target): bool
    {
        return $user->can('platform.users.read.all') || $user->id === $target->id;
    }

    public function create(User $user): bool
    {
        return $user->can('platform.users.create');
    }

    public function update(User $user, User $target): bool
    {
        return $user->can('platform.users.update.all');
    }

    public function approve(User $user, User $target): bool
    {
        return $user->can('platform.users.update.all') && $target->status === 'pending';
    }

    public function suspend(User $user, User $target): bool
    {
        return $user->can('platform.users.deactivate')
            && $user->id !== $target->id
            && ! SuperAdminGuard::isLastActive($target);
    }

    public function reactivate(User $user, User $target): bool
    {
        return $user->can('platform.users.deactivate')
            && in_array($target->status, ['suspended', 'deactivated'], true);
    }

    public function deactivate(User $user, User $target): bool
    {
        return $user->can('platform.users.deactivate')
            && $user->id !== $target->id
            && ! SuperAdminGuard::isLastActive($target);
    }

    public function delete(User $user, User $target): bool
    {
        return $user->can('platform.users.delete')
            && $user->id !== $target->id
            && ! SuperAdminGuard::isLastActive($target);
    }
}
