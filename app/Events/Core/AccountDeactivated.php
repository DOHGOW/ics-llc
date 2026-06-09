<?php

namespace App\Events\Core;

use App\Models\Core\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** E-CORE-008 */
class AccountDeactivated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public User $user,
        public string $reason,
        public int $actorId,
        public ?string $actorRole = null,
        public ?string $ipAddress = null,
    ) {}
}
