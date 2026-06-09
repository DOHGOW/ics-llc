<?php

namespace App\Events\Portal;

use App\Models\Client\Ticket;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Support ticket resolved/closed (D-056 portal audit + SLA analytics). */
class TicketResolved
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public ?int $actorId = null,
        public ?string $actorRole = null,
    ) {}
}
