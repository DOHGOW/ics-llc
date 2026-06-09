<?php

namespace App\Services\Startup;

use App\Events\Program\ProgramGovernanceChanged;
use App\Models\Core\User;
use App\Models\Startup\ProgramCohort;
use App\Models\Startup\StartupProgram;

/**
 * Program/cohort governance (D-066 / D-067 / H-2). Cohort closure + archival are audited;
 * program suspension/reinstatement/termination are HIGH-audited. All carry program type context.
 */
class ProgramGovernanceService
{
    public function closeCohort(ProgramCohort $cohort, User $actor): ProgramCohort
    {
        $cohort->forceFill(['status' => 'closed'])->save();
        $this->fire($cohort->program, 'cohort_closed', $cohort->id, $actor);

        return $cohort;
    }

    public function archiveCohort(ProgramCohort $cohort, User $actor): ProgramCohort
    {
        $cohort->forceFill(['status' => 'archived'])->save();
        $this->fire($cohort->program, 'cohort_archived', $cohort->id, $actor);

        return $cohort;
    }

    public function suspendProgram(StartupProgram $program, User $actor): StartupProgram
    {
        $program->forceFill(['status' => 'suspended'])->save();
        $this->fire($program, 'program_suspended', null, $actor); // HIGH

        return $program;
    }

    public function reinstateProgram(StartupProgram $program, User $actor): StartupProgram
    {
        $program->forceFill(['status' => 'active'])->save();
        $this->fire($program, 'program_reinstated', null, $actor); // HIGH

        return $program;
    }

    public function terminateProgram(StartupProgram $program, User $actor): StartupProgram
    {
        $program->forceFill(['status' => 'terminated'])->save();
        $this->fire($program, 'program_terminated', null, $actor); // HIGH

        return $program;
    }

    public function archiveProgram(StartupProgram $program, User $actor): StartupProgram
    {
        $program->forceFill(['status' => 'archived'])->save();
        $this->fire($program, 'program_archived', null, $actor);

        return $program;
    }

    private function fire(StartupProgram $program, string $action, ?int $cohortId, User $actor): void
    {
        event(new ProgramGovernanceChanged($program, $action, $cohortId, $actor->id, $actor->getRoleNames()->first()));
    }
}
