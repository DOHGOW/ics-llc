<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Client\ClientProject;
use App\Services\Client\ClientPortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Client projects (Wave 2). ORG-OWNED: AccountScope (Layer 1) auto-filters the index to the
 * caller's account; ClientProjectPolicy (Layer 2) gates each action. ICS staff manage;
 * clients view their own. Status changes flow through ClientPortalService (audited).
 */
class ProjectController extends Controller
{
    public function __construct(private readonly ClientPortalService $portal) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->canAny(['client.projects.read.own', 'client.projects.manage']), 403);

        // AccountScope restricts org users to their account automatically.
        return response()->json(
            ClientProject::query()->select(['id', 'account_id', 'title', 'status', 'target_end_date'])->paginate(25)
        );
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('create', ClientProject::class), 403); // staff only

        $data = $request->validate([
            'account_id' => ['required', 'integer', 'exists:crm_accounts,id'], // staff specify the owner org
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'contract_id' => ['nullable', 'integer'],
            'start_date' => ['nullable', 'date'],
            'target_end_date' => ['nullable', 'date'],
            'project_manager_id' => ['nullable', 'integer', 'exists:core_users,id'],
        ]);
        $data['created_by'] = $request->user()->id;

        $project = ClientProject::create($data);

        return response()->json(['id' => $project->id], 201);
    }

    public function show(Request $request, ClientProject $project): JsonResponse
    {
        abort_unless($request->user()->can('view', $project), 403);

        return response()->json($project->load('milestones'));
    }

    public function update(Request $request, ClientProject $project): JsonResponse
    {
        abort_unless($request->user()->can('manage', $project), 403);

        $project->update($request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'target_end_date' => ['nullable', 'date'],
            'project_manager_id' => ['nullable', 'integer', 'exists:core_users,id'],
        ]));

        return response()->json(['message' => __('Project updated.')]);
    }

    public function changeStatus(Request $request, ClientProject $project): JsonResponse
    {
        abort_unless($request->user()->can('manage', $project), 403);
        $data = $request->validate(['status' => ['required', Rule::in(ClientProject::STATUSES)]]);

        $this->portal->changeProjectStatus($project, $data['status'], $request->user());

        return response()->json(['message' => __('Project status updated.')]);
    }
}
