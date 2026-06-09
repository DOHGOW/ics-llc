<?php

namespace App\Events\Crm;

use App\Models\Crm\Opportunity;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** An opportunity moved between pipeline stages (D-054 audit + D-025 analytics signal). */
class OpportunityStageChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Opportunity $opportunity,
        public string $fromStage,
        public string $toStage,
        public ?int $actorId = null,
        public ?string $actorRole = null,
    ) {}
}
