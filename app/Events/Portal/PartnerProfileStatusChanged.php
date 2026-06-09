<?php

namespace App\Events\Portal;

use App\Models\Partner\PartnerProfile;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Partner profile status changed (D-056). Suspension/termination are HIGH-sensitivity
 * (resolved in the audit handler from the target status).
 */
class PartnerProfileStatusChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public PartnerProfile $profile,
        public string $fromStatus,
        public string $toStatus,
        public ?int $actorId = null,
        public ?string $actorRole = null,
    ) {}
}
