<?php

namespace App\Events\Startup;

use App\Models\Startup\Startup;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Startup governance state change (D-062/D-064): verification, suspension/reactivation,
 * graduation to alumni. Suspension/reactivation + verification = HIGH (resolved in handler).
 */
class StartupStatusChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Startup $startup,
        public string $action, // verified | suspended | reactivated | graduated
        public ?int $actorId = null,
        public ?string $actorRole = null,
    ) {}
}
