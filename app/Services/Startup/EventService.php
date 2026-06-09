<?php

namespace App\Services\Startup;

use App\Events\Program\EventActivity;
use App\Models\Core\User;
use App\Models\Startup\ProgramCohort;
use App\Models\Startup\ProgramEvent;
use App\Models\Startup\ProgramEventJudge;
use App\Models\Startup\ProgramEventScore;
use App\Models\Startup\Startup;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Generic Program Events service (D-068 / M-1). ONE mechanism for demo_day/pitch_event/showcase/
 * readiness_review/graduation_showcase + judging/scoring. LIGHTWEIGHT — no orchestration/workflow
 * engine; the only state is a `finalized_at` lock. Scores are operational-maturity only (H-3).
 * Audited via EventActivity → PROGRAM_MANAGEMENT (reuse; overrides HIGH).
 */
class EventService
{
    public function createEvent(ProgramCohort $cohort, array $data, User $actor): ProgramEvent
    {
        $event = ProgramEvent::create([
            'tenant_id' => $cohort->tenant_id,
            'cohort_id' => $cohort->id,
            'type' => $data['type'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'created_by' => $actor->id,
        ]);

        $this->fire($event, 'event_created', $actor);

        return $event;
    }

    public function assignJudge(ProgramEvent $event, int $userId, User $actor): ProgramEventJudge
    {
        return ProgramEventJudge::firstOrCreate(
            ['event_id' => $event->id, 'user_id' => $userId],
            ['assigned_by' => $actor->id],
        );
    }

    /** A judge submits a maturity score (H-3). Override of an existing finalized score = HIGH audit. */
    public function submitScore(ProgramEvent $event, int $startupId, string $criterion, float $score, ?string $feedback, User $judge): ProgramEventScore
    {
        abort_unless($score >= 0, 422, 'Score cannot be negative.');
        abort_unless(ProgramEventJudge::where('event_id', $event->id)->where('user_id', $judge->id)->exists(), 403);

        $isOverride = $event->isFinalized();
        if ($isOverride) {
            // Post-finalization change — permitted only via this audited path.
            $this->fire($event, 'score_override', $judge);
        }

        return ProgramEventScore::updateOrCreate(
            ['event_id' => $event->id, 'judge_id' => $judge->id, 'startup_id' => $startupId, 'criterion' => $criterion],
            ['score' => $score, 'feedback' => $feedback],
        );
    }

    public function finalize(ProgramEvent $event, User $actor): ProgramEvent
    {
        if ($event->isFinalized()) {
            throw ValidationException::withMessages(['event' => __('Event is already finalized.')]);
        }

        $event->forceFill(['finalized_at' => now()])->save();
        $action = $event->type === 'readiness_review' ? 'readiness_determined' : 'scoring_finalized';
        $this->fire($event, $action, $actor);

        return $event;
    }

    /** Derived ranking (L-1) — computed from scores, not stored. */
    public function ranking(ProgramEvent $event): array
    {
        return ProgramEventScore::query()
            ->where('event_id', $event->id)
            ->groupBy('startup_id')
            ->select('startup_id', DB::raw('AVG(score) as avg_score'))
            ->orderByDesc('avg_score')
            ->get()
            ->map(fn ($r) => ['startup_id' => $r->startup_id, 'avg_score' => round((float) $r->avg_score, 2)])
            ->all();
    }

    /**
     * Investor Showcase exposure (H-1) — curated, public-projection list of cohort startups ONLY.
     * No cap-table/financials/data-room (D-069). Discovery/exposure, not a portal.
     */
    public function showcaseExposure(ProgramCohort $cohort): array
    {
        return Startup::query()
            ->whereIn('id', function ($q) use ($cohort) {
                $q->select('startup_id')->from('startup_program_enrollments')
                    ->where('cohort_id', $cohort->id)->whereIn('status', ['active', 'graduated']);
            })
            ->get(['id', 'name', 'slug', 'industry', 'lifecycle_stage', 'country_code', 'logo_path'])
            ->all(); // public fields only (W4b-1/C-1/M-1)
    }

    private function fire(ProgramEvent $event, string $action, User $actor): void
    {
        $type = (string) optional(optional($event->cohort)->program)->type;
        event(new EventActivity($event, $action, $type, $actor->id, $actor->getRoleNames()->first()));
    }
}
