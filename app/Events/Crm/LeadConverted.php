<?php

namespace App\Events\Crm;

use App\Models\Crm\Lead;
use App\Models\Crm\Opportunity;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** A qualified lead was converted into an opportunity (D-054 audit + D-025 analytics). */
class LeadConverted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Lead $lead,
        public Opportunity $opportunity,
        public ?int $actorId = null,
        public ?string $actorRole = null,
    ) {}
}
