<?php

namespace App\Events\Community;

use App\Models\Community\CommunityProfile;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A consultant community profile was created → ONE-WAY CRM lead capture (W4b-3 / D-012 /
 * D-053). The CRM side consumes this; NO CRM data ever flows back to Community.
 */
class ConsultantProfileCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public CommunityProfile $profile) {}
}
