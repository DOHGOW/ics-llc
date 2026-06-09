<?php

namespace App\Events\Portal;

use App\Models\Partner\PartnerReferral;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Partner referral stage changed (D-056 portal audit + D-025 funnel analytics). */
class ReferralStageChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public PartnerReferral $referral,
        public string $fromStage,
        public string $toStage,
        public ?int $actorId = null,
        public ?string $actorRole = null,
    ) {}
}
