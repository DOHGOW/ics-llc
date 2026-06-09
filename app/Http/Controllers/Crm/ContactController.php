<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRM Contacts (Wave 1d). Internal-only; assignment-scoped (D-053). `account_id` is a
 * subject pointer to crm_accounts, not an ownership key.
 */
class ContactController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->canAny(['crm.contacts.read.all', 'crm.contacts.read.own']), 403);

        return response()->json(
            Contact::visibleTo($request->user())
                ->when($request->filled('account_id'), fn ($q) => $q->where('account_id', $request->integer('account_id')))
                ->select(['id', 'account_id', 'first_name', 'last_name', 'email', 'status', 'assigned_to'])
                ->paginate(25)
        );
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('crm.contacts.create'), 403);

        $data = $request->validate([
            'account_id' => ['nullable', 'integer', 'exists:crm_accounts,id'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'job_title' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'in:active,inactive'],
            'assigned_to' => ['nullable', 'integer', 'exists:core_users,id'],
        ]);
        $data['created_by'] = $request->user()->id;
        $data['assigned_to'] ??= $request->user()->id;

        $contact = Contact::create($data);

        return response()->json(['id' => $contact->id], 201);
    }

    public function show(Request $request, Contact $contact): JsonResponse
    {
        abort_unless($contact->visibleToUser($request->user()), 403);

        return response()->json($contact);
    }

    public function update(Request $request, Contact $contact): JsonResponse
    {
        abort_unless($request->user()->can('crm.contacts.update') && $contact->visibleToUser($request->user()), 403);

        $contact->update($request->validate([
            'account_id' => ['nullable', 'integer', 'exists:crm_accounts,id'],
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'job_title' => ['nullable', 'string', 'max:150'],
            'status' => ['sometimes', 'in:active,inactive'],
        ]));

        return response()->json(['message' => __('Contact updated.')]);
    }

    public function destroy(Request $request, Contact $contact): JsonResponse
    {
        abort_unless($request->user()->can('crm.contacts.delete') && $contact->visibleToUser($request->user()), 403);
        $contact->delete();

        return response()->json(['message' => __('Contact deleted.')]);
    }
}
