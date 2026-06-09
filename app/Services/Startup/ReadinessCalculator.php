<?php

namespace App\Services\Startup;

use App\Models\Startup\ProgramEvent;
use App\Models\Startup\ProgramEventScore;

/**
 * Investment-readiness signal (H-3) — OPERATIONAL MATURITY only; never valuation/equity/financial.
 * Computed purely from finalized `readiness_review` event scores (reuses the ONE scoring
 * mechanism, M-1). Used by CompletionValidator (graduation gate, M-2) and for display. No
 * separate readiness engine.
 */
class ReadinessCalculator
{
    public const GRADUATION_THRESHOLD = 70.0; // maturity threshold (config-tunable later)

    /** Average readiness score for a startup in a cohort from FINALIZED readiness_review events. */
    public function score(int $startupId, int $cohortId): ?float
    {
        $eventIds = ProgramEvent::query()
            ->where('cohort_id', $cohortId)
            ->where('type', 'readiness_review')
            ->whereNotNull('finalized_at')
            ->pluck('id');

        if ($eventIds->isEmpty()) {
            return null;
        }

        $avg = ProgramEventScore::query()
            ->whereIn('event_id', $eventIds)
            ->where('startup_id', $startupId)
            ->avg('score');

        return $avg !== null ? round((float) $avg, 2) : null;
    }

    public function meetsThreshold(int $startupId, int $cohortId): bool
    {
        $score = $this->score($startupId, $cohortId);

        return $score !== null && $score >= self::GRADUATION_THRESHOLD;
    }
}
