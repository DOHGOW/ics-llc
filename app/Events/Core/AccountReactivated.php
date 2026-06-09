<?php

namespace App\Events\Core;

use App\Models\Core\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Lifecycle: a suspended/deactivated account was reactivated — R-6. */
class AccountReactivated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public User $user,
        public int $actorId,
        public ?string $actorRole = null,
        public ?string $ipAddress = null,
    ) {}
}
