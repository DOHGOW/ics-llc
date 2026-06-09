<?php

namespace App\Events\Community;

use App\Models\Community\CommunityProfile;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Community profile moderated (suspended/hidden/reactivated) — governance (D-058). */
class ProfileStatusChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public CommunityProfile $profile,
        public string $fromStatus,
        public string $toStatus,
        public ?int $actorId = null,
        public ?string $actorRole = null,
    ) {}
}
