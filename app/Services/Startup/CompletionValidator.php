<?php

namespace App\Services\Startup;

use App\Models\Startup\ProgramEnrollment;

/**
 * Program completion validation (D-067) — the SINGLE graduation authority (M-2). No parallel
 * graduation engine.
 *
 * - Incubator / general programs: complete when no required curriculum is defined (the Training
 *   threshold hook — required courses / D-059 certs / % completion — plugs in later).
 * - Accelerator (D-068/M-2): graduation is gated by the INVESTMENT-READINESS signal — a finalized
 *   readiness_review meeting the maturity threshold (operational maturity only, H-3), computed via
 *   ReadinessCalculator (reuses the ONE event/scoring mechanism, M-1).
 */
class CompletionValidator
{
    public function __construct(private readonly ReadinessCalculator $readiness) {}

    public function isComplete(ProgramEnrollment $enrollment): bool
    {
        if ($enrollment->program?->type === 'accelerator') {
            return $enrollment->cohort_id !== null
                && $this->readiness->meetsThreshold((int) $enrollment->startup_id, (int) $enrollment->cohort_id);
        }

        // Incubator / general: no required curriculum yet → complete (Training hook later).
        return true;
    }
}
