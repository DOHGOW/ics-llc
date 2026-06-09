<?php

namespace App\Events\Program;

use App\Models\Startup\StartupProgram;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Program/cohort governance transition (D-066). action ∈ cohort_closed|cohort_archived|
 * program_suspended|program_reinstated|program_terminated|program_archived. Program TYPE is
 * carried as context. suspend/reinstate/terminate are HIGH (resolved in the handler, H-2).
 */
class ProgramGovernanceChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public StartupProgram $program,
        public string $action,
        public ?int $cohortId = null,
        public ?int $actorId = null,
        public ?string $actorRole = null,
    ) {}
}
