<?php

namespace App\Events\Portal;

use App\Models\Client\ClientProject;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Client project status changed (D-056 portal audit + D-025 analytics signal). */
class ProjectStatusChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ClientProject $project,
        public string $fromStatus,
        public string $toStatus,
        public ?int $actorId = null,
        public ?string $actorRole = null,
    ) {}
}
