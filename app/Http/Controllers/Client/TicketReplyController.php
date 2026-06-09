<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Client\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ticket replies (Wave 2). PARENT-ISOLATED (W2-1) under a ticket. Internal replies
 * (is_internal=1) are STAFF-ONLY — enforced at the POLICY layer here (replyInternal) and
 * filtered from clients at the query + resource layers (TicketController/model, W2-4).
 */
class TicketReplyController extends Controller
{
    public function store(Request $request, Ticket $ticket): JsonResponse
    {
        abort_unless($request->user()->can('reply', $ticket), 403); // parent gate (W2-1)

        $data = $request->validate([
            'body' => ['required', 'string'],
            'is_internal' => ['nullable', 'boolean'],
            'attachments' => ['nullable', 'array'],
        ]);

        $isInternal = (bool) ($data['is_internal'] ?? false);
        // W2-4 policy layer: only ICS staff may post an internal reply.
        if ($isInternal) {
            abort_unless($request->user()->can('replyInternal', $ticket), 403);
        }

        $reply = $ticket->replies()->create([
            'author_id' => $request->user()->id,
            'body' => $data['body'],
            'is_internal' => $isInternal,
            'attachments' => $data['attachments'] ?? null,
        ]);

        return response()->json(['id' => $reply->id], 201);
    }
}
