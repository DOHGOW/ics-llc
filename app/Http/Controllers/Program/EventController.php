<?php

namespace App\Http\Controllers\Program;

use App\Http\Controllers\Controller;
use App\Models\Startup\ProgramCohort;
use App\Models\Startup\ProgramEvent;
use App\Services\Startup\EventService;
use App\Services\Startup\ProgramParticipationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Program Events (Wave 5C / D-068 / M-1) — ONE generic mechanism for demo_day/pitch_event/
 * showcase/readiness_review/graduation_showcase + judging/scoring. Lightweight; finalize locks.
 * Reuses ProgramParticipationService (canManageCohort). Audited via PROGRAM_MANAGEMENT.
 */
class EventController extends Controller
{
    public function __construct(
        private readonly EventService $events,
        private readonly ProgramParticipationService $participation,
    ) {}

    public function index(Request $request, ProgramCohort $cohort): JsonResponse
    {
        abort_unless($this->participation->canManageCohort($request->user(), $cohort), 403);

        return response()->json(
            ProgramEvent::where('cohort_id', $cohort->id)
                ->select(['id', 'type', 'title', 'scheduled_at', 'finalized_at'])->paginate(25)
        );
    }

    public function store(Request $request, ProgramCohort $cohort): JsonResponse
    {
        abort_unless($this->participation->canManageCohort($request->user(), $cohort), 403);

        $data = $request->validate([
            'type' => ['required', Rule::in(ProgramEvent::TYPES)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        $event = $this->events->createEvent($cohort, $data, $request->user());

        return response()->json(['id' => $event->id], 201);
    }

    public function assignJudge(Request $request, ProgramEvent $event): JsonResponse
    {
        abort_unless($this->participation->canManageCohort($request->user(), $event->cohort), 403);
        $data = $request->validate(['user_id' => ['required', 'integer', 'exists:core_users,id']]);

        $this->events->assignJudge($event, (int) $data['user_id'], $request->user());

        return response()->json(['message' => __('Judge assigned.')], 201);
    }

    /** A judge submits an operational-maturity score (H-3) — never financial. */
    public function submitScore(Request $request, ProgramEvent $event): JsonResponse
    {
        $data = $request->validate([
            'startup_id' => ['required', 'integer', 'exists:startup_profiles,id'],
            'criterion' => ['required', 'string', 'max:100'],
            'score' => ['required', 'numeric', 'min:0', 'max:100'],
            'feedback' => ['nullable', 'string'],
        ]);

        // The EventService verifies the caller is an assigned judge for this event.
        $this->events->submitScore($event, (int) $data['startup_id'], $data['criterion'], (float) $data['score'], $data['feedback'] ?? null, $request->user());

        return response()->json(['message' => __('Score submitted.')], 201);
    }

    public function finalize(Request $request, ProgramEvent $event): JsonResponse
    {
        abort_unless($this->participation->canManageCohort($request->user(), $event->cohort), 403);
        $this->events->finalize($event, $request->user());

        return response()->json(['message' => __('Event finalized.')]);
    }

    public function ranking(Request $request, ProgramEvent $event): JsonResponse
    {
        abort_unless($this->participation->canManageCohort($request->user(), $event->cohort), 403);

        return response()->json(['ranking' => $this->events->ranking($event)]);
    }
}
