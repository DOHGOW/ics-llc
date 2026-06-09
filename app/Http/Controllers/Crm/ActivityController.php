<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\Account;
use App\Models\Crm\Activity;
use App\Models\Crm\Lead;
use App\Models\Crm\Opportunity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * CRM Activities (Wave 1d). Polymorphic timeline against Lead|Opportunity|Account.
 * NOTES are `type='note'` (W1d-2 — no crm_notes table). Internal-only; assignment-scoped
 * (D-053). Seeded permissions: crm.activities.create / crm.activities.read.all.
 */
class ActivityController extends Controller
{
    /** Whitelist of polymorphic subject types (no arbitrary class binding). */
    private const SUBJECTS = [
        'lead' => Lead::class,
        'opportunity' => Opportunity::class,
        'account' => Account::class,
    ];

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->canAny(['crm.activities.read.all', 'crm.activities.read.own']), 403);

        $data = $request->validate([
            'subject' => ['required', Rule::in(array_keys(self::SUBJECTS))],
            'subject_id' => ['required', 'integer'],
        ]);

        return response()->json(
            Activity::visibleTo($request->user())
                ->where('subject_type', self::SUBJECTS[$data['subject']])
                ->where('subject_id', $data['subject_id'])
                ->orderByDesc('created_at')
                ->paginate(25)
        );
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('crm.activities.create'), 403);

        $data = $request->validate([
            'subject' => ['required', Rule::in(array_keys(self::SUBJECTS))],
            'subject_id' => ['required', 'integer'],
            'type' => ['required', Rule::in(Activity::TYPES)], // 'note' supported (W1d-2)
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_at' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'integer', 'exists:core_users,id'],
        ]);

        $activity = Activity::create([
            'subject_type' => self::SUBJECTS[$data['subject']],
            'subject_id' => $data['subject_id'],
            'type' => $data['type'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'due_at' => $data['due_at'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? $request->user()->id,
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['id' => $activity->id], 201);
    }

    public function complete(Request $request, Activity $activity): JsonResponse
    {
        abort_unless($request->user()->can('crm.activities.create') && $activity->visibleToUser($request->user()), 403);
        $activity->forceFill(['completed_at' => now()])->save();

        return response()->json(['message' => __('Activity completed.')]);
    }

    public function destroy(Request $request, Activity $activity): JsonResponse
    {
        abort_unless($request->user()->can('crm.activities.create') && $activity->visibleToUser($request->user()), 403);
        $activity->delete();

        return response()->json(['message' => __('Activity deleted.')]);
    }
}
