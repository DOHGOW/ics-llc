<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\Opportunity;
use App\Services\Crm\CrmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * CRM Opportunities (Wave 1d). Internal-only; assignment-scoped (D-053). Stage changes
 * and assignment flow through CrmService (audited under crm_management, D-054).
 */
class OpportunityController extends Controller
{
    public function __construct(private readonly CrmService $crm) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->canAny(['crm.opportunities.read.all', 'crm.opportunities.read.own']), 403);

        return response()->json(
            Opportunity::visibleTo($request->user())
                ->when($request->filled('stage'), fn ($q) => $q->where('stage', $request->string('stage')))
                ->select(['id', 'title', 'stage', 'value', 'currency', 'assigned_to', 'account_id'])
                ->paginate(25)
        );
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('crm.opportunities.create'), 403);

        $data = $request->validate([
            'account_id' => ['nullable', 'integer', 'exists:crm_accounts,id'],
            'lead_id' => ['nullable', 'integer', 'exists:crm_leads,id'],
            'title' => ['required', 'string', 'max:255'],
            'value' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'close_date' => ['nullable', 'date'],
            'probability' => ['nullable', 'integer', 'between:0,100'],
            'description' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'integer', 'exists:core_users,id'],
        ]);
        $data['created_by'] = $request->user()->id;
        $data['assigned_to'] ??= $request->user()->id;

        $opp = Opportunity::create($data);

        return response()->json(['id' => $opp->id], 201);
    }

    public function show(Request $request, Opportunity $opportunity): JsonResponse
    {
        abort_unless($opportunity->visibleToUser($request->user()), 403);

        return response()->json($opportunity);
    }

    public function update(Request $request, Opportunity $opportunity): JsonResponse
    {
        abort_unless($request->user()->can('crm.opportunities.update') && $opportunity->visibleToUser($request->user()), 403);

        $opportunity->update($request->validate([
            'account_id' => ['nullable', 'integer', 'exists:crm_accounts,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'value' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'close_date' => ['nullable', 'date'],
            'probability' => ['nullable', 'integer', 'between:0,100'],
            'description' => ['nullable', 'string'],
        ]));

        return response()->json(['message' => __('Opportunity updated.')]);
    }

    public function changeStage(Request $request, Opportunity $opportunity): JsonResponse
    {
        abort_unless($request->user()->can('crm.opportunities.update') && $opportunity->visibleToUser($request->user()), 403);
        $data = $request->validate(['stage' => ['required', Rule::in(Opportunity::STAGES)]]);

        $this->crm->changeOpportunityStage($opportunity, $data['stage'], $request->user());

        return response()->json(['message' => __('Opportunity stage updated.')]);
    }

    public function assign(Request $request, Opportunity $opportunity): JsonResponse
    {
        abort_unless($request->user()->can('crm.opportunities.update') && $opportunity->visibleToUser($request->user()), 403);
        $data = $request->validate(['assigned_to' => ['nullable', 'integer', 'exists:core_users,id']]);

        $this->crm->assign($opportunity, $data['assigned_to'] ?? null, $request->user());

        return response()->json(['message' => __('Opportunity reassigned.')]);
    }

    public function destroy(Request $request, Opportunity $opportunity): JsonResponse
    {
        abort_unless($request->user()->can('crm.opportunities.delete') && $opportunity->visibleToUser($request->user()), 403);
        $opportunity->delete();

        return response()->json(['message' => __('Opportunity deleted.')]);
    }
}
