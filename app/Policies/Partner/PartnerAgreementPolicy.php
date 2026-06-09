<?php

namespace App\Policies\Partner;

use App\Models\Core\User;
use App\Models\Partner\PartnerAgreement;
use App\Policies\OrgOwnedPolicy;

/**
 * Partner agreement authorisation (Wave 2). A partner reads its own agreements
 * (read.own + sameAccount); ICS staff manage (create/sign). Files are policy-gated
 * (W2-5). Agreement events are audited HIGH (D-056).
 */
class PartnerAgreementPolicy extends OrgOwnedPolicy
{
    public function view(User $user, PartnerAgreement $agreement): bool
    {
        return ($user->can('partner.agreements.read.own') || $user->can('partner.agreements.manage'))
            && $this->accessible($user, $agreement);
    }

    public function manage(User $user, PartnerAgreement $agreement): bool
    {
        return $user->can('partner.agreements.manage') && $this->accessible($user, $agreement);
    }

    public function create(User $user): bool
    {
        return $user->can('partner.agreements.manage');
    }
}
