<?php

namespace App\Events\Portal;

use App\Models\Partner\PartnerAgreement;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Partner agreement signed — HIGH-sensitivity governance event (D-056). */
class AgreementSigned
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public PartnerAgreement $agreement,
        public ?int $actorId = null,
        public ?string $actorRole = null,
    ) {}
}
