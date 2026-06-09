<?php

namespace App\Events\Portal;

use App\Models\Client\Deliverable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Deliverable submitted/approved/rejected (D-056 portal audit). */
class DeliverableStatusChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Deliverable $deliverable,
        public string $fromStatus,
        public string $toStatus,
        public ?int $actorId = null,
        public ?string $actorRole = null,
    ) {}
}
