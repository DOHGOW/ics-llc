<?php

namespace App\Events\Training;

use App\Models\Training\Instructor;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** An instructor profile was approved by staff (D-058). */
class InstructorApproved
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Instructor $instructor,
        public ?int $actorId = null,
        public ?string $actorRole = null,
    ) {}
}
