<?php

namespace App\Services\Startup;

use App\Events\Program\ParticipationChanged;
use App\Models\Core\User;
use App\Models\Startup\ProgramCohort;
use App\Models\Startup\ProgramEnrollment;
use App\Models\Startup\Startup;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Governed program intake (M-1 / D-067). Flow: applied → under_review → accepted → active.
 * No direct enrollment bypass. D-067 guards: no double entry to a cohort; no conflicting active
 * program states. On acceptance→active, the startup's lifecycle is routed through the Startup
 * lifecycle governance layer (H-3) — NOT a parallel state.
 */
class IntakeService
{
    public function __construct(private readonly StartupGovernanceService $startupGovernance) {}

    /** A startup applies to a cohort (governed entry point — no bypass). */
    public function apply(ProgramCohort $cohort, Startup $startup): ProgramEnrollment
    {
        abort_unless($cohort->intakeIsOpen(), 422, 'Intake is not open for this cohort.');

        // D-067: no double entry to the same cohort.
        if (ProgramEnrollment::where('startup_id', $startup->id)->where('cohort_id', $cohort->id)->exists()) {
            throw ValidationException::withMessages(['cohort' => __('This startup has already applied to this cohort.')]);
        }

        // D-067: no conflicting active program states (one active participation at a time).
        $this->assertNoConflictingParticipation($startup);

        return ProgramEnrollment::create([
            'startup_id' => $startup->id,
            'program_id' => $cohort->program_id,
            'cohort_id' => $cohort->id,
            'status' => 'applied',
            'applied_at' => now(),
        ]);
    }

    public function review(ProgramEnrollment $enrollment, User $actor): ProgramEnrollment
    {
        $enrollment->forceFill(['status' => 'under_review'])->save();

        return $enrollment;
    }

    /** Accept → active enrollment; routes the startup lifecycle (H-3); audited (M-1). */
    public function accept(ProgramEnrollment $enrollment, User $actor): ProgramEnrollment
    {
        return DB::transaction(function () use ($enrollment, $actor): ProgramEnrollment {
            $startup = $enrollment->startup;
            $this->assertNoConflictingParticipation($startup, $enrollment->id);

            $enrollment->forceFill([
                'status' => 'active',
                'enrolled_at' => now(),
                'decided_at' => now(),
                'decided_by' => $actor->id,
            ])->save();

            // H-3: route lifecycle through the Startup governance layer (no parallel system).
            $stage = $enrollment->program?->type === 'accelerator' ? 'acceleration' : 'incubation';
            $this->startupGovernance->setLifecycleStage($startup, $stage);

            $this->fire($enrollment, 'accepted', $actor);
            $this->fire($enrollment, 'enrolled', $actor);

            return $enrollment;
        });
    }

    public function reject(ProgramEnrollment $enrollment, User $actor): ProgramEnrollment
    {
        $enrollment->forceFill(['status' => 'withdrawn', 'decided_at' => now(), 'decided_by' => $actor->id])->save();
        $this->fire($enrollment, 'rejected', $actor);

        return $enrollment;
    }

    private function assertNoConflictingParticipation(Startup $startup, ?int $exceptId = null): void
    {
        $conflict = ProgramEnrollment::where('startup_id', $startup->id)
            ->whereIn('status', ProgramEnrollment::ACTIVE_STATES)
            ->when($exceptId !== null, fn ($q) => $q->where('id', '!=', $exceptId))
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'startup' => __('This startup is already in an active program — it cannot be in conflicting program states (D-067).'),
            ]);
        }
    }

    private function fire(ProgramEnrollment $enrollment, string $action, User $actor): void
    {
        event(new ParticipationChanged($enrollment, $action, (string) $enrollment->program?->type, null, $actor->id, $actor->getRoleNames()->first()));
    }
}
