<?php

namespace App\Events\Core;

use App\Models\Core\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** E-CORE-004 */
class PasswordChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public User $user,
        public ?string $ipAddress = null,
    ) {}
}
