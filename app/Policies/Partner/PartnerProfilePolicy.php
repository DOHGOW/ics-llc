<?php

namespace App\Policies\Partner;

use App\Models\Core\User;
use App\Models\Partner\PartnerProfile;
use App\Policies\OrgOwnedPolicy;

/**
 * Partner profile authorisation (Wave 2). A partner views/updates its own org's profile
 * (read.own/update + sameAccount); ICS staff manage/approve/suspend (staff-only).
 * Super Admin via Gate::before; default-deny.
 */
class PartnerProfilePolicy extends OrgOwnedPolicy
{
    public function view(User $user, PartnerProfile $profile): bool
    {
        return ($user->can('partner.profiles.read.own') || $user->can('partner.profiles.read.all'))
            && $this->accessible($user, $profile);
    }

    public function update(User $user, PartnerProfile $profile): bool
    {
        return $user->can('partner.profiles.update') && $this->accessible($user, $profile);
    }

    /** Approval/suspension/termination are ICS-staff-only governance actions. */
    public function administer(User $user, PartnerProfile $profile): bool
    {
        return $user->can('partner.profiles.approve') && $this->isInternalStaff($user);
    }
}
