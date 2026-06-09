<?php

namespace App\Events\Program;

use App\Models\Startup\ProgramEnrollment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A program participation transition (D-066). action ∈ accepted|rejected|enrolled|graduated|
 * withdrawn|removed|graduation_reversed. Program TYPE is carried as context (H-2). `removed`
 * and `graduation_reversed` are HIGH (resolved in the audit handler).
 */
class ParticipationChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ProgramEnrollment $enrollment,
        public string $action,
        public string $programType,
        public ?string $reason = null,
        public ?int $actorId = null,
        public ?string $actorRole = null,
    ) {}
}
