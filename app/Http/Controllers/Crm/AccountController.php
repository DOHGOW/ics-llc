<?php

namespace App\Http\Controllers\Crm;

use App\Events\Crm\CrmAccountDeleted;
use App\Http\Controllers\Controller;
use App\Models\Crm\Account;
use App\Services\Crm\CrmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRM Accounts (Wave 1d). Internal-only; permission + assignment-scoped (D-053).
 * Reads pass through `visibleTo()` (NOT AccountScope). Deletes are audited (D-054).
 */
class AccountController extends Controller
{
    public function __construct(private readonly CrmService $crm) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->canAny(['crm.accounts.read.all', 'crm.accounts.read.own']), 403);

        return response()->json(
            Account::visibleTo($request->user())
                ->select(['id', 'name', 'type', 'status', 'assigned_to'])
                ->paginate(25)
        );
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('crm.accounts.create'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:client,prospect,partner,government,ngo,sme,startup'],
            'industry' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'url', 'max:255'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'status' => ['nullable', 'in:active,inactive,prospect'],
            'assigned_to' => ['nullable', 'integer', 'exists:core_users,id'],
        ]);
        $data['created_by'] = $request->user()->id;
        $data['assigned_to'] ??= $request->user()->id;

        $account = Account::create($data);

        return response()->json(['id' => $account->id], 201);
    }

    public function show(Request $request, Account $account): JsonResponse
    {
        abort_unless($account->visibleToUser($request->user()), 403);

        return response()->json($account);
    }

    public function update(Request $request, Account $account): JsonResponse
    {
        abort_unless($request->user()->can('crm.accounts.update') && $account->visibleToUser($request->user()), 403);

        $account->update($request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'in:client,prospect,partner,government,ngo,sme,startup'],
            'industry' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'url', 'max:255'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:active,inactive,prospect'],
        ]));

        return response()->json(['message' => __('Account updated.')]);
    }

    public function assign(Request $request, Account $account): JsonResponse
    {
        abort_unless($request->user()->can('crm.accounts.update') && $account->visibleToUser($request->user()), 403);
        $data = $request->validate(['assigned_to' => ['nullable', 'integer', 'exists:core_users,id']]);

        $this->crm->assign($account, $data['assigned_to'] ?? null, $request->user());

        return response()->json(['message' => __('Account reassigned.')]);
    }

    public function destroy(Request $request, Account $account): JsonResponse
    {
        abort_unless($request->user()->can('crm.accounts.delete') && $account->visibleToUser($request->user()), 403);

        event(new CrmAccountDeleted($account, $request->user()->id, $request->user()->getRoleNames()->first()));
        $account->delete();

        return response()->json(['message' => __('Account deleted.')]);
    }
}
