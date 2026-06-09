<?php

namespace App\Events\Marketplace;

use App\Models\Marketplace\MarketplaceListing;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** A listing was approved/rejected/removed by a moderator (D-058 MARKETPLACE_MANAGEMENT). */
class ListingReviewed
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public MarketplaceListing $listing,
        public string $action, // approved | rejected | removed
        public ?string $notes = null,
        public ?int $actorId = null,
        public ?string $actorRole = null,
    ) {}
}
