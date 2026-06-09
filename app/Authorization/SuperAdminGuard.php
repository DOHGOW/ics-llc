<?php

namespace App\Authorization;

use App\Models\Core\User;

/**
 * Last-Super-Admin protection (R-3 / D-047). Used at both the service layer and the
 * policy layer to prevent removing the platform's final administrative control.
 */
final class SuperAdminGuard
{
    /** Count of ACTIVE Super Admins (excludes pending/suspended/deactivated/soft-deleted). */
    public static function activeCount(): int
    {
        return User::role(Roles::SUPER_ADMIN)->where('status', 'active')->count();
    }

    /** True if removing this user/role would leave zero active Super Admins. */
    public static function isLastActive(User $user): bool
    {
        return $user->hasRole(Roles::SUPER_ADMIN) && self::activeCount() <= 1;
    }
}
