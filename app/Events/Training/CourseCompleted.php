<?php

namespace App\Events\Training;

use App\Models\Training\Enrollment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** A learner completed a course (D-058 + analytics). */
class CourseCompleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Enrollment $enrollment,
        public ?int $actorId = null,
        public ?string $actorRole = null,
    ) {}
}
