<?php

namespace App\Services\Crm;

use App\Events\Crm\CrmRecordAssigned;
use App\Events\Crm\LeadConverted;
use App\Events\Crm\LeadStageChanged;
use App\Events\Crm\OpportunityStageChanged;
use App\Models\Core\User;
use App\Models\Crm\Lead;
use App\Models\Crm\Opportunity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * CRM orchestration (Wave 1d). Stage transitions, assignment, and lead→opportunity
 * conversion flow through here so every governance event is fired exactly once
 * (audited under crm_management, D-054; also the analytics signal, D-025).
 */
class CrmService
{
    public function changeLeadStage(Lead $lead, string $toStage, User $actor): Lead
    {
        $from = $lead->stage;
        if ($from === $toStage) {
            return $lead;
        }

        $lead->forceFill(['stage' => $toStage])->save();
        event(new LeadStageChanged($lead, $from, $toStage, $actor->id, $this->roleOf($actor)));

        return $lead;
    }

    public function changeOpportunityStage(Opportunity $opp, string $toStage, User $actor): Opportunity
    {
        $from = $opp->stage;
        if ($from === $toStage) {
            return $opp;
        }

        $opp->forceFill(['stage' => $toStage])->save();
        event(new OpportunityStageChanged($opp, $from, $toStage, $actor->id, $this->roleOf($actor)));

        return $opp;
    }

    /** Reassign a CRM record's owner; fires the accountability event. */
    public function assign(Model $record, ?int $newAssignee, User $actor): Model
    {
        $previous = $record->assigned_to;
        if ((int) $previous === (int) $newAssignee) {
            return $record;
        }

        $record->forceFill(['assigned_to' => $newAssignee])->save();
        event(new CrmRecordAssigned($record, $previous, $newAssignee, $actor->id, $this->roleOf($actor)));

        return $record;
    }

    /**
     * Convert a qualified lead into an opportunity. Atomic; marks the lead closed_won
     * and carries account/value forward. Fires LeadConverted (+ the lead stage change).
     */
    public function convertLead(Lead $lead, User $actor): Opportunity
    {
        return DB::transaction(function () use ($lead, $actor): Opportunity {
            $opportunity = Opportunity::create([
                'tenant_id' => $lead->tenant_id,
                'account_id' => $lead->account_id,
                'lead_id' => $lead->id,
                'title' => $lead->title,
                'value' => $lead->value ?? 0,
                'currency' => $lead->currency ?? 'NGN',
                'stage' => 'qualification',
                'probability' => $lead->probability,
                'assigned_to' => $lead->assigned_to,
                'created_by' => $actor->id,
            ]);

            $this->changeLeadStage($lead, 'closed_won', $actor);
            event(new LeadConverted($lead, $opportunity, $actor->id, $this->roleOf($actor)));

            return $opportunity;
        });
    }

    private function roleOf(User $user): ?string
    {
        return $user->getRoleNames()->first();
    }
}
