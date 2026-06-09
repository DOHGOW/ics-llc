<?php

namespace App\Models\Client;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ticket reply (client_ticket_replies). CHILD of client_tickets — parent-isolated (W2-1).
 * `is_internal=1` replies are STAFF-ONLY and filtered from clients at the QUERY layer
 * (scopePublic), the POLICY layer, and the RESOURCE layer (W2-4).
 */
class TicketReply extends Model
{
    protected $table = 'client_ticket_replies';

    protected $fillable = [
        'ticket_id', 'author_id', 'body', 'is_internal', 'attachments',
    ];

    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
            'attachments' => 'array',
        ];
    }

    /** W2-4 query layer: client-visible replies only. */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_internal', false);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }
}
