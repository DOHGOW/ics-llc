<?php

namespace App\Services\Startup;

use App\Authorization\Roles;
use App\Models\Core\User;
use App\Models\Startup\Startup;
use App\Models\Startup\TeamMember;

/**
 * Startup participation access (D-061) — the MEMBERSHIP/PARTICIPATION family (same family as
 * TrainingAccessService). NOT AccountScope, NOT ContentAccessService, NOT HasAssignmentVisibility.
 * "Is the user a participant in this startup?" Staff/owner bypass; default-deny; NEVER falls
 * back to AccountScope.
 */
class StartupAccessService
{
    private const ICS_STAFF = [Roles::SUPER_ADMIN, Roles::PLATFORM_ADMIN, Roles::ICS_CRM, Roles::ICS_TRAINING, Roles::ICS_CONTENT];

    public function isStaff(?User $user): bool
    {
        return $user !== null && $user->hasAnyRole(self::ICS_STAFF);
    }

    public function isFounder(?User $user, Startup $startup): bool
    {
        if ($user === null) {
            return false;
        }
        if ((int) $startup->founder_id === (int) $user->id) {
            return true;
        }

        return TeamMember::where('startup_id', $startup->id)->where('user_id', $user->id)
            ->where('is_founder', true)->where('status', 'active')->exists();
    }

    public function isTeamMember(?User $user, Startup $startup): bool
    {
        if ($user === null) {
            return false;
        }
        if ((int) $startup->founder_id === (int) $user->id) {
            return true;
        }

        return TeamMember::where('startup_id', $startup->id)->where('user_id', $user->id)
            ->where('status', 'active')->exists();
    }

    /** Founder/admin OR ICS staff may manage the startup. */
    public function canManage(?User $user, Startup $startup): bool
    {
        if ($this->isStaff($user)) {
            return true;
        }
        if ($user === null) {
            return false;
        }
        if ((int) $startup->founder_id === (int) $user->id) {
            return true;
        }

        return TeamMember::where('startup_id', $startup->id)->where('user_id', $user->id)
            ->whereIn('role', ['founder', 'co_founder', 'admin'])->where('status', 'active')->exists();
    }

    /**
     * C-1 gate: ownership/cap-table data is visible ONLY to founders, authorized startup
     * admins, and approved ICS staff (granted investors join in Wave 5d). Never public.
     */
    public function canViewOwnership(?User $user, Startup $startup): bool
    {
        return $this->canManage($user, $startup);
    }
}
