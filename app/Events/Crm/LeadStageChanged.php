<?php

namespace App\Events\Crm;

use App\Models\Crm\Lead;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** A lead moved between pipeline stages (D-054 audit + D-025 analytics signal). */
class LeadStageChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Lead $lead,
        public string $fromStage,
        public string $toStage,
        public ?int $actorId = null,
        public ?string $actorRole = null,
    ) {}
}
