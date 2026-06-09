<?php

namespace App\Events\Training;

use App\Models\Training\AssessmentSubmission;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** An assessment submission was graded (auto or instructor) (D-058). */
class AssessmentGraded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public AssessmentSubmission $submission,
        public ?int $actorId = null,
        public ?string $actorRole = null,
    ) {}
}
