<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\Lead;
use App\Services\Crm\CrmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * CRM Leads (Wave 1d). Internal-only; assignment-scoped (D-053). Stage changes,
 * assignment, and conversion go through CrmService (audited under crm_management, D-054).
 */
class LeadController extends Controller
{
    public function __construct(private readonly CrmService $crm) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->canAny(['crm.leads.read.all', 'crm.leads.read.own']), 403);

        return response()->json(
            Lead::visibleTo($request->user())
                ->when($request->filled('stage'), fn ($q) => $q->where('stage', $request->string('stage')))
                ->select(['id', 'title', 'stage', 'value', 'currency', 'assigned_to', 'account_id'])
                ->paginate(25)
        );
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('crm.leads.create'), 403);

        $data = $request->validate([
            'account_id' => ['nullable', 'integer', 'exists:crm_accounts,id'],
            'contact_id' => ['nullable', 'integer', 'exists:crm_contacts,id'],
            'source' => ['required', 'string', 'max:100'],
            'source_detail' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'value' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'probability' => ['nullable', 'integer', 'between:0,100'],
            'expected_close_date' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'integer', 'exists:core_users,id'],
            'notes' => ['nullable', 'string'],
        ]);
        $data['created_by'] = $request->user()->id;
        $data['assigned_to'] ??= $request->user()->id;

        $lead = Lead::create($data);

        return response()->json(['id' => $lead->id], 201);
    }

    public function show(Request $request, Lead $lead): JsonResponse
    {
        abort_unless($lead->visibleToUser($request->user()), 403);

        return response()->json($lead);
    }

    public function update(Request $request, Lead $lead): JsonResponse
    {
        abort_unless($request->user()->can('crm.leads.update') && $lead->visibleToUser($request->user()), 403);

        // Stage is NOT updated here — it flows through changeStage() so it is audited.
        $lead->update($request->validate([
            'account_id' => ['nullable', 'integer', 'exists:crm_accounts,id'],
            'contact_id' => ['nullable', 'integer', 'exists:crm_contacts,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'value' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'probability' => ['nullable', 'integer', 'between:0,100'],
            'expected_close_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]));

        return response()->json(['message' => __('Lead updated.')]);
    }

    public function changeStage(Request $request, Lead $lead): JsonResponse
    {
        abort_unless($request->user()->can('crm.leads.update') && $lead->visibleToUser($request->user()), 403);
        $data = $request->validate(['stage' => ['required', Rule::in(Lead::STAGES)]]);

        $this->crm->changeLeadStage($lead, $data['stage'], $request->user());

        return response()->json(['message' => __('Lead stage updated.')]);
    }

    public function assign(Request $request, Lead $lead): JsonResponse
    {
        abort_unless($request->user()->can('crm.leads.update') && $lead->visibleToUser($request->user()), 403);
        $data = $request->validate(['assigned_to' => ['nullable', 'integer', 'exists:core_users,id']]);

        $this->crm->assign($lead, $data['assigned_to'] ?? null, $request->user());

        return response()->json(['message' => __('Lead reassigned.')]);
    }

    public function convert(Request $request, Lead $lead): JsonResponse
    {
        abort_unless($request->user()->can('crm.opportunities.create') && $lead->visibleToUser($request->user()), 403);

        $opportunity = $this->crm->convertLead($lead, $request->user());

        return response()->json(['opportunity_id' => $opportunity->id], 201);
    }

    public function destroy(Request $request, Lead $lead): JsonResponse
    {
        abort_unless($request->user()->can('crm.leads.delete') && $lead->visibleToUser($request->user()), 403);
        $lead->delete();

        return response()->json(['message' => __('Lead deleted.')]);
    }
}
