<?php

namespace App\Events\Training;

use App\Models\Training\Enrollment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** A learner enrolled in a course (D-058 TRAINING_MANAGEMENT + D-025 analytics). */
class EnrollmentCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Enrollment $enrollment,
        public ?int $actorId = null,
        public ?string $actorRole = null,
    ) {}
}
