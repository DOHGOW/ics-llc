<?php

namespace App\Events\Core;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * E-CORE-005 — dispatched when an account is locked after repeated failed
 * logins (D-039 / T-4.4). Listeners (alert + audit) are wired in Task 6.
 */
class AccountLocked
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $email,
        public string $ipAddress,
        public int $attempts,
        public ?string $userAgent = null,
    ) {}
}
