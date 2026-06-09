<?php

namespace App\Events\Marketplace;

use App\Models\Marketplace\MarketplaceApplication;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** An application's status changed (accepted/rejected/shortlisted) (D-058). */
class ApplicationStatusChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public MarketplaceApplication $application,
        public string $fromStatus,
        public string $toStatus,
        public ?int $actorId = null,
        public ?string $actorRole = null,
    ) {}
}
