<?php

namespace App\Events\Startup;

use App\Models\Startup\Startup;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Founder ownership transferred — HIGH-sensitivity governance event (H-2 / D-062 / D-064). */
class OwnershipTransferred
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Startup $startup,
        public ?int $fromFounderId,
        public int $toFounderId,
        public ?int $actorId = null,
        public ?string $actorRole = null,
    ) {}
}
