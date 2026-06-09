<?php

namespace App\Policies\Partner;

use App\Models\Core\User;
use App\Models\Partner\PartnerReferral;
use App\Policies\OrgOwnedPolicy;

/**
 * Partner referral authorisation (Wave 2). A partner submits/reads its own referrals
 * (read.own + sameAccount); ICS staff qualify/convert and set commission (staff-only).
 * The crm_lead link is never exposed to the partner (W2-3, model $hidden + serialisers).
 */
class PartnerReferralPolicy extends OrgOwnedPolicy
{
    public function view(User $user, PartnerReferral $referral): bool
    {
        return ($user->can('partner.referrals.read.own') || $user->can('partner.referrals.read.all'))
            && $this->accessible($user, $referral);
    }

    public function create(User $user): bool
    {
        return $user->can('partner.referrals.create');
    }

    /** Stage changes, qualification, conversion, and commission — ICS-staff-only. */
    public function administer(User $user, PartnerReferral $referral): bool
    {
        return $user->can('partner.referrals.read.all') && $this->isInternalStaff($user);
    }
}
