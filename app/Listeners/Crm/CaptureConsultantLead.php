<?php

namespace App\Listeners\Crm;

use App\Events\Community\ConsultantProfileCreated;
use App\Models\Crm\Lead;

/**
 * ONE-WAY CRM lead capture from a new consultant community profile (W4b-3 / D-012 / D-053).
 *
 * Direction is strictly Community → CRM. The created crm_lead is internal, assignment-scoped
 * (D-053), and is NEVER surfaced back to the consultant or any community response. This
 * listener writes into CRM and returns nothing to Community.
 */
class CaptureConsultantLead
{
    public function handle(ConsultantProfileCreated $event): void
    {
        $profile = $event->profile;

        Lead::create([
            'tenant_id' => $profile->tenant_id,
            'source' => 'community',
            'source_detail' => 'Consultant profile #'.$profile->id,
            'title' => $profile->display_name,
            'stage' => 'new',
            'created_by' => $profile->user_id,
        ]);
    }
}
