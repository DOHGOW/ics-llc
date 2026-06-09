<?php

namespace App\Events\Crm;

use App\Models\Crm\Account;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** A CRM account was (soft) deleted — high-value governance event (D-054). */
class CrmAccountDeleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Account $account,
        public ?int $actorId = null,
        public ?string $actorRole = null,
    ) {}
}
