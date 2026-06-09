<?php

namespace App\Services\Startup;

use App\Models\Core\User;
use App\Models\Startup\ProgramCohort;
use App\Models\Startup\ProgramCoordinator;
use App\Models\Startup\ProgramEnrollment;
use App\Models\Startup\Startup;

/**
 * Program participation access (D-065 / M-2) — the MEMBERSHIP/PARTICIPATION family. Composes
 * WITH StartupAccessService (it does NOT replace or overload it). "Is the user a participant
 * in this program/cohort?" = the user is on the team of a startup with an active participation,
 * OR a program coordinator, OR ICS staff. NOT a new access-control family; never AccountScope.
 */
class ProgramParticipationService
{
    public function __construct(private readonly StartupAccessService $startupAccess) {}

    /** A startup participates in a cohort if it has a non-terminal enrollment there. */
    public function startupParticipates(Startup $startup, ProgramCohort $cohort): bool
    {
        return ProgramEnrollment::where('startup_id', $startup->id)->where('cohort_id', $cohort->id)
            ->whereIn('status', ProgramEnrollment::ACTIVE_STATES)->exists();
    }

    /** A user participates if they are on the team of a participating startup. */
    public function userParticipates(?User $user, ProgramCohort $cohort): bool
    {
        if ($user === null) {
            return false;
        }

        return ProgramEnrollment::query()
            ->where('cohort_id', $cohort->id)
            ->whereIn('status', ProgramEnrollment::ACTIVE_STATES)
            ->whereIn('startup_id', function ($q) use ($user) {
                $q->select('startup_id')->from('startup_team_members')
                    ->where('user_id', $user->id)->where('status', 'active');
            })
            ->exists();
    }

    public function isCoordinator(?User $user, ProgramCohort $cohort): bool
    {
        return $user !== null && ProgramCoordinator::where('cohort_id', $cohort->id)
            ->where('user_id', $user->id)->exists();
    }

    /** Program management = ICS staff (startup.programs.manage) OR a cohort coordinator. */
    public function canManageCohort(?User $user, ProgramCohort $cohort): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->can('startup.programs.manage') || $this->isCoordinator($user, $cohort);
    }
}
