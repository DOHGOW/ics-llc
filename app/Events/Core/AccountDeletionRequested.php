<?php

namespace App\Events\Core;

use App\Models\Core\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** E-CORE-010 */
class AccountDeletionRequested
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public User $user,
        public ?string $ipAddress = null,
    ) {}
}
