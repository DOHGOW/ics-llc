<?php

namespace App\Services\Partner;

use App\Events\Portal\AgreementSigned;
use App\Events\Portal\CommissionPaid;
use App\Events\Portal\CommissionRecorded;
use App\Events\Portal\PartnerProfileStatusChanged;
use App\Events\Portal\ReferralStageChanged;
use App\Models\Core\User;
use App\Models\Crm\Account;
use App\Models\Crm\Lead;
use App\Models\Partner\PartnerAgreement;
use App\Models\Partner\PartnerProfile;
use App\Models\Partner\PartnerReferral;
use Illuminate\Support\Facades\DB;

/**
 * Partner Portal orchestration (Wave 2). Profile/referral/commission/agreement lifecycle
 * flow through here; each event is audited under portal_management (D-056; agreement/
 * commission/suspension = HIGH).
 *
 * W2-3 BOUNDARY: qualifyReferral() creates an INTERNAL crm_lead and links lead_id, but
 * that lead is never exposed to the partner — only ICS staff invoke this and only ICS
 * sees the lead side.
 */
class PartnerPortalService
{
    /**
     * Onboard a partner (D-055): provision a crm_account (type='partner') for the partner —
     * org OR individual — link the partner user to it, and create the (pending) profile.
     * This guarantees account_id is ALWAYS present on partner rows, so AccountScope is the
     * sole, gap-free isolation mechanism. Atomic.
     */
    public function onboardPartner(User $partnerUser, string $organisationName, ?int $tierId, User $actor): PartnerProfile
    {
        return DB::transaction(function () use ($partnerUser, $organisationName, $tierId, $actor): PartnerProfile {
            $account = $partnerUser->account_id
                ? Account::findOrFail($partnerUser->account_id)
                : Account::create([
                    'name' => $organisationName,
                    'type' => 'partner',
                    'status' => 'active',
                    'assigned_to' => $actor->id,
                    'created_by' => $actor->id,
                ]);

            // Bind the partner user to the account if not already (D-050/D-055).
            if ($partnerUser->account_id === null) {
                $partnerUser->forceFill(['account_id' => $account->id])->save();
            }

            return PartnerProfile::create([
                'user_id' => $partnerUser->id,
                'account_id' => $account->id,
                'tier_id' => $tierId,
                'organisation_name' => $organisationName,
                'status' => 'pending',
            ]);
        });
    }

    public function changeProfileStatus(PartnerProfile $profile, string $toStatus, User $actor): PartnerProfile
    {
        $from = $profile->status;
        if ($from === $toStatus) {
            return $profile;
        }

        $profile->forceFill([
            'status' => $toStatus,
            'approved_at' => $toStatus === 'active' ? ($profile->approved_at ?? now()) : $profile->approved_at,
            'approved_by' => $toStatus === 'active' ? ($profile->approved_by ?? $actor->id) : $profile->approved_by,
        ])->save();

        event(new PartnerProfileStatusChanged($profile, $from, $toStatus, $actor->id, $this->roleOf($actor)));

        return $profile;
    }

    public function changeReferralStage(PartnerReferral $referral, string $toStage, User $actor): PartnerReferral
    {
        $from = $referral->stage;
        if ($from === $toStage) {
            return $referral;
        }

        $referral->forceFill(['stage' => $toStage])->save();
        event(new ReferralStageChanged($referral, $from, $toStage, $actor->id, $this->roleOf($actor)));

        return $referral;
    }

    /**
     * ICS qualifies a referral → creates the internal crm_lead (W2-3: ICS-only) and links
     * it, advancing the referral to 'qualified'. Atomic.
     */
    public function qualifyReferral(PartnerReferral $referral, User $actor): PartnerReferral
    {
        return DB::transaction(function () use ($referral, $actor): PartnerReferral {
            if ($referral->lead_id === null) {
                $lead = Lead::create([
                    'tenant_id' => $referral->tenant_id,
                    'source' => 'referral',
                    'source_detail' => 'Partner referral #'.$referral->id,
                    'title' => $referral->referred_org_name,
                    'stage' => 'qualified',
                    'assigned_to' => $actor->id,
                    'created_by' => $actor->id,
                ]);
                $referral->forceFill(['lead_id' => $lead->id])->save();
            }

            return $this->changeReferralStage($referral, 'qualified', $actor);
        });
    }

    public function recordCommission(PartnerReferral $referral, string $amount, string $currency, User $actor): PartnerReferral
    {
        $referral->forceFill(['commission_amount' => $amount, 'commission_currency' => $currency])->save();
        event(new CommissionRecorded($referral, $amount, $currency, $actor->id, $this->roleOf($actor)));

        return $referral;
    }

    public function payCommission(PartnerReferral $referral, User $actor): PartnerReferral
    {
        $referral->forceFill(['commission_paid_at' => now()])->save();
        event(new CommissionPaid($referral, $actor->id, $this->roleOf($actor)));

        return $referral;
    }

    public function signAgreement(PartnerAgreement $agreement, User $actor): PartnerAgreement
    {
        $agreement->forceFill(['signed_at' => now()])->save();
        event(new AgreementSigned($agreement, $actor->id, $this->roleOf($actor)));

        return $agreement;
    }

    private function roleOf(User $user): ?string
    {
        return $user->getRoleNames()->first();
    }
}
