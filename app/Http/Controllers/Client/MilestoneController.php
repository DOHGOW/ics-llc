<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Client\ClientProject;
use App\Models\Client\ProjectMilestone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Project milestones (Wave 2). PARENT-ISOLATED (W2-1): always nested under a project; the
 * project binds through AccountScope (an org user cannot resolve another org's project →
 * 404), and the milestone is reached ONLY via that project. Never queried independently.
 */
class MilestoneController extends Controller
{
    public function index(Request $request, ClientProject $project): JsonResponse
    {
        abort_unless($request->user()->can('view', $project), 403); // parent gate (W2-1)

        return response()->json($project->milestones()->paginate(50));
    }

    public function store(Request $request, ClientProject $project): JsonResponse
    {
        abort_unless($request->user()->can('manage', $project), 403);

        $milestone = $project->milestones()->create($request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
        ]) + ['created_by' => $request->user()->id]);

        return response()->json(['id' => $milestone->id], 201);
    }

    public function update(Request $request, ClientProject $project, ProjectMilestone $milestone): JsonResponse
    {
        abort_unless($request->user()->can('manage', $project), 403);
        abort_unless((int) $milestone->project_id === (int) $project->id, 404); // parent integrity

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'status' => ['sometimes', Rule::in(ProjectMilestone::STATUSES)],
        ]);
        if (($data['status'] ?? null) === 'completed') {
            $data['completed_at'] = now();
        }
        $milestone->update($data);

        return response()->json(['message' => __('Milestone updated.')]);
    }
}
