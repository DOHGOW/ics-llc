<?php

namespace App\Events\Portal;

use App\Models\Partner\PartnerReferral;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Commission recorded on a referral — HIGH-sensitivity financial event (D-056 / D-031). */
class CommissionRecorded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public PartnerReferral $referral,
        public string $amount,
        public string $currency,
        public ?int $actorId = null,
        public ?string $actorRole = null,
    ) {}
}
