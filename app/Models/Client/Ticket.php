<?php

namespace App\Models\Client;

use App\Models\Concerns\BelongsToAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Support ticket (client_tickets). ORG-OWNED — account_id ownership key (D-050); isolated
 * by AccountScope + TicketPolicy. Internal replies are filtered from clients (W2-4).
 */
class Ticket extends Model
{
    use BelongsToAccount;
    use SoftDeletes;

    protected $table = 'client_tickets';

    public const STATUSES = ['open', 'in_progress', 'resolved', 'closed'];

    protected $fillable = [
        'tenant_id', 'project_id', 'account_id', 'user_id', 'title', 'description',
        'priority', 'status', 'assigned_to', 'resolved_at',
    ];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class, 'ticket_id');
    }

    /** Client-facing replies only — excludes internal staff notes (W2-4, query layer). */
    public function publicReplies(): HasMany
    {
        return $this->replies()->where('is_internal', false);
    }
}
