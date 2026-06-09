<?php

namespace App\Events\Community;

use App\Models\Community\CommunityProfile;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** ICS verified a community profile — governance event (D-058 COMMUNITY_MANAGEMENT). */
class ProfileVerified
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public CommunityProfile $profile,
        public ?int $actorId = null,
        public ?string $actorRole = null,
    ) {}
}
