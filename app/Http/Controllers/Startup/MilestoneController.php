<?php

namespace App\Http\Controllers\Startup;

use App\Http\Controllers\Controller;
use App\Models\Startup\Milestone;
use App\Models\Startup\Startup;
use App\Services\Startup\StartupAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Startup milestones (Wave 5A / M-1). Internal — team members + ICS staff only; never public.
 */
class MilestoneController extends Controller
{
    public function __construct(private readonly StartupAccessService $access) {}

    public function index(Request $request, Startup $startup): JsonResponse
    {
        abort_unless($this->access->isTeamMember($request->user(), $startup) || $this->access->isStaff($request->user()), 403);

        return response()->json($startup->milestones()->orderBy('target_date')->paginate(50));
    }

    public function store(Request $request, Startup $startup): JsonResponse
    {
        abort_unless($this->access->canManage($request->user(), $startup), 403);

        $milestone = $startup->milestones()->create($request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'target_date' => ['nullable', 'date'],
        ]) + ['created_by' => $request->user()->id]);

        return response()->json(['id' => $milestone->id], 201);
    }

    public function update(Request $request, Startup $startup, Milestone $milestone): JsonResponse
    {
        abort_unless($this->access->canManage($request->user(), $startup), 403);
        abort_unless((int) $milestone->startup_id === (int) $startup->id, 404);

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'target_date' => ['nullable', 'date'],
            'status' => ['sometimes', Rule::in(Milestone::STATUSES)],
        ]);
        if (($data['status'] ?? null) === 'completed') {
            $data['completed_at'] = now();
        }
        $milestone->update($data);

        return response()->json(['message' => __('Milestone updated.')]);
    }
}
