<?php

namespace App\Events\Program;

use App\Models\Startup\ProgramEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Program-event activity (D-068, reuses PROGRAM_MANAGEMENT). action ∈ event_created|
 * scoring_finalized|readiness_determined|showcase_access_granted|score_override|
 * readiness_override|showcase_access_revoked. Override/revoke actions are HIGH (handler).
 */
class EventActivity
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ProgramEvent $event,
        public string $action,
        public string $programType,
        public ?int $actorId = null,
        public ?string $actorRole = null,
    ) {}
}
