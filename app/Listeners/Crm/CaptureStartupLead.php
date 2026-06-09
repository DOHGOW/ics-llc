<?php

namespace App\Listeners\Crm;

use App\Events\Startup\StartupCreated;
use App\Models\Crm\Lead;

/**
 * ONE-WAY CRM lead capture from a new startup (D-053 / H-3). Direction is strictly
 * Startup Hub → CRM. The created crm_lead is internal + assignment-scoped (D-053) and is
 * NEVER surfaced back to the founder or any Startup Hub response. No CRM ownership is
 * inherited by the startup (founder-centric, H-3).
 */
class CaptureStartupLead
{
    public function handle(StartupCreated $event): void
    {
        $startup = $event->startup;

        Lead::create([
            'tenant_id' => $startup->tenant_id,
            'source' => 'startup',
            'source_detail' => 'Startup #'.$startup->id.' ('.$startup->name.')',
            'title' => $startup->name,
            'stage' => 'new',
            'created_by' => $startup->founder_id,
        ]);
    }
}
