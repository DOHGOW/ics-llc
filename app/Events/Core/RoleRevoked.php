<?php

namespace App\Events\Core;

use App\Models\Core\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** E-CORE-007 */
class RoleRevoked
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public User $target,
        public string $role,
        public int $actorId,
        public ?string $actorRole = null,
        public ?string $ipAddress = null,
    ) {}
}
