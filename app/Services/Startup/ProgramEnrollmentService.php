<?php

namespace App\Services\Startup;

use App\Events\Program\ParticipationChanged;
use App\Models\Core\User;
use App\Models\Startup\ProgramEnrollment;
use Illuminate\Validation\ValidationException;

/**
 * Active-participation lifecycle (D-067). Graduation requires completion validation; withdrawal
 * and forced removal require a mandatory reason. Forced removal + graduation reversal are HIGH
 * (D-066). Graduation does NOT auto-advance the startup lifecycle — that remains a staff decision
 * routed through StartupGovernanceService (H-3).
 */
class ProgramEnrollmentService
{
    public function __construct(private readonly CompletionValidator $completion) {}

    /** D-067: graduation requires completion validation. */
    public function graduate(ProgramEnrollment $enrollment, User $actor): ProgramEnrollment
    {
        abort_unless($enrollment->status === 'active', 422, 'Only active participations can graduate.');

        if (! $this->completion->isComplete($enrollment)) {
            throw ValidationException::withMessages(['completion' => __('Completion requirements are not met; graduation is blocked (D-067).')]);
        }

        $enrollment->forceFill(['status' => 'graduated', 'graduated_at' => now()])->save();
        $this->fire($enrollment, 'graduated', $actor);

        return $enrollment;
    }

    /** Reverse a graduation (HIGH audit, D-066). */
    public function reverseGraduation(ProgramEnrollment $enrollment, User $actor, string $reason): ProgramEnrollment
    {
        abort_unless($enrollment->status === 'graduated', 422);
        $enrollment->forceFill(['status' => 'active', 'graduated_at' => null])->save();
        $this->fire($enrollment, 'graduation_reversed', $actor, $reason);

        return $enrollment;
    }

    /** D-067: withdrawal reason mandatory. */
    public function withdraw(ProgramEnrollment $enrollment, User $actor, string $reason): ProgramEnrollment
    {
        $enrollment->forceFill(['status' => 'withdrawn', 'withdrawal_reason' => $reason])->save();
        $this->fire($enrollment, 'withdrawn', $actor, $reason);

        return $enrollment;
    }

    /** D-067: forced removal reason mandatory; HIGH audit (D-066). */
    public function forceRemove(ProgramEnrollment $enrollment, User $actor, string $reason): ProgramEnrollment
    {
        $enrollment->forceFill(['status' => 'removed', 'removal_reason' => $reason])->save();
        $this->fire($enrollment, 'removed', $actor, $reason);

        return $enrollment;
    }

    private function fire(ProgramEnrollment $enrollment, string $action, User $actor, ?string $reason = null): void
    {
        event(new ParticipationChanged($enrollment, $action, (string) $enrollment->program?->type, $reason, $actor->id, $actor->getRoleNames()->first()));
    }
}
