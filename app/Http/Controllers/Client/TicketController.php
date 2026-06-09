<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Client\Ticket;
use App\Services\Client\ClientPortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Support tickets (Wave 2). ORG-OWNED: AccountScope auto-filters; TicketPolicy gates.
 * A client raises tickets for their own org; ICS staff manage. Internal replies are
 * filtered elsewhere (W2-4).
 */
class TicketController extends Controller
{
    public function __construct(private readonly ClientPortalService $portal) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->canAny(['client.tickets.read.own', 'client.tickets.manage']), 403);

        return response()->json(
            Ticket::query()->select(['id', 'account_id', 'title', 'status', 'priority', 'created_at'])->paginate(25)
        );
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('create', Ticket::class), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['nullable', 'in:low,normal,high,critical'],
            'project_id' => ['nullable', 'integer'],
        ]);

        // account_id is stamped by BelongsToAccount from the client user; user_id is the raiser.
        $ticket = Ticket::create($data + [
            'user_id' => $request->user()->id,
            'status' => 'open',
        ]);

        return response()->json(['id' => $ticket->id], 201);
    }

    public function show(Request $request, Ticket $ticket): JsonResponse
    {
        abort_unless($request->user()->can('view', $ticket), 403);

        // W2-4 query layer: staff see all replies; clients see public replies only.
        $replies = $request->user()->can('manage', $ticket)
            ? $ticket->replies()->orderBy('created_at')->get()
            : $ticket->publicReplies()->orderBy('created_at')->get();

        return response()->json(['ticket' => $ticket, 'replies' => $replies]);
    }

    public function resolve(Request $request, Ticket $ticket): JsonResponse
    {
        abort_unless($request->user()->can('manage', $ticket), 403);
        $this->portal->resolveTicket($ticket, $request->user());

        return response()->json(['message' => __('Ticket resolved.')]);
    }
}
