<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Client\ClientProject;
use App\Models\Client\Deliverable;
use App\Services\Client\ClientPortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Deliverables (Wave 2). PARENT-ISOLATED (W2-1). Files are streamed through THIS
 * policy-gated path (W2-5) — never a public URL. Clients see only client-visible statuses
 * (drafts hidden); ICS staff see all. Status changes flow through the service (audited).
 */
class DeliverableController extends Controller
{
    public function __construct(private readonly ClientPortalService $portal) {}

    public function index(Request $request, ClientProject $project): JsonResponse
    {
        abort_unless($request->user()->can('view', $project), 403);

        $query = $project->deliverables();
        // Clients never see drafts; staff (manage) see everything.
        if (! $request->user()->can('manage', $project)) {
            $query->whereIn('status', Deliverable::CLIENT_VISIBLE_STATUSES);
        }

        return response()->json($query->paginate(25));
    }

    public function store(Request $request, ClientProject $project): JsonResponse
    {
        abort_unless($request->user()->can('manage', $project), 403); // staff upload deliverables

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'milestone_id' => ['nullable', 'integer'],
            'version' => ['nullable', 'string', 'max:20'],
            'file' => ['required', 'file', 'max:'.(int) config('ics.media.max_kb', 10240)],
        ]);

        $path = $request->file('file')->store(config('ics.media.path', 'media').'/deliverables', config('ics.media.disk', 'public'));

        $deliverable = $project->deliverables()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'milestone_id' => $validated['milestone_id'] ?? null,
            'version' => $validated['version'] ?? '1.0',
            'file_path' => $path,
            'status' => 'draft',
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['id' => $deliverable->id], 201);
    }

    public function changeStatus(Request $request, ClientProject $project, Deliverable $deliverable): JsonResponse
    {
        abort_unless($request->user()->can('manage', $project), 403);
        abort_unless((int) $deliverable->project_id === (int) $project->id, 404);
        $data = $request->validate(['status' => ['required', Rule::in(Deliverable::STATUSES)]]);

        $this->portal->changeDeliverableStatus($deliverable, $data['status'], $request->user());

        return response()->json(['message' => __('Deliverable status updated.')]);
    }

    /** W2-5: file delivery is policy-gated + status-checked; never a public URL. */
    public function download(Request $request, ClientProject $project, Deliverable $deliverable): StreamedResponse
    {
        abort_unless($request->user()->can('view', $project), 403);
        abort_unless((int) $deliverable->project_id === (int) $project->id, 404);

        // Clients may only download client-visible deliverables (drafts hidden).
        if (! $request->user()->can('manage', $project)
            && ! in_array($deliverable->status, Deliverable::CLIENT_VISIBLE_STATUSES, true)) {
            abort(404);
        }

        $disk = Storage::disk(config('ics.media.disk', 'public'));
        abort_unless($disk->exists($deliverable->file_path), 404);

        return $disk->download($deliverable->file_path, $deliverable->title);
    }
}
