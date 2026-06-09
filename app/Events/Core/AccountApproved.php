<?php

namespace App\Events\Core;

use App\Models\Core\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Lifecycle: a pending account was approved (pending → active) — R-6. */
class AccountApproved
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
