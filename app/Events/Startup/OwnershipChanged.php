<?php

namespace App\Events\Startup;

use App\Models\Startup\Startup;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Founder ownership_percent changed — HIGH-sensitivity (D-064). Amounts NOT carried in audit detail. */
class OwnershipChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Startup $startup,
        public ?int $actorId = null,
        public ?string $actorRole = null,
    ) {}
}
