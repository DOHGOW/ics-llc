<?php

namespace App\Events\Crm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** A CRM record's owner (`assigned_to`) changed — accountability event (D-054). */
class CrmRecordAssigned
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Model $record,
        public ?int $previousAssignee,
        public ?int $newAssignee,
        public ?int $actorId = null,
        public ?string $actorRole = null,
    ) {}
}
