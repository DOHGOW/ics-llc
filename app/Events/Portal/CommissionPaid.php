<?php

namespace App\Events\Portal;

use App\Models\Partner\PartnerReferral;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Commission marked paid — HIGH-sensitivity financial event (D-056 / D-031). */
class CommissionPaid
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public PartnerReferral $referral,
        public ?int $actorId = null,
        public ?string $actorRole = null,
    ) {}
}
