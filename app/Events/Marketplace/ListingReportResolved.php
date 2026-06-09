<?php

namespace App\Events\Marketplace;

use App\Models\Marketplace\ListingReport;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** An abuse report was resolved (reviewed/dismissed/actioned) (D-058/D-060). */
class ListingReportResolved
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ListingReport $report,
        public string $resolution,
        public ?int $actorId = null,
        public ?string $actorRole = null,
    ) {}
}
