<?php

namespace App\Services\Training;

use App\Authorization\Roles;
use App\Models\Core\User;
use App\Models\Training\Enrollment;
use App\Models\Training\Lesson;
use App\Models\Training\TrainingCourse;

/**
 * Training ENROLLMENT-gated access (D-057). This is a module-local authorization rule — NOT
 * AccountScope, NOT ContentAccessService, NOT HasAssignmentVisibility. A learner may read a
 * lesson iff it is a public preview OR they hold an active enrollment in its course. ICS
 * training staff and the owning instructor may always access (management).
 */
class TrainingAccessService
{
    public function activeEnrollment(?User $user, int $courseId): ?Enrollment
    {
        if ($user === null) {
            return null;
        }

        return Enrollment::query()
            ->where('course_id', $courseId)
            ->where('user_id', $user->id)
            ->whereIn('status', ['active', 'completed'])
            ->first();
    }

    public function isEnrolled(?User $user, int $courseId): bool
    {
        return $this->activeEnrollment($user, $courseId) !== null;
    }

    public function canAccessLesson(?User $user, Lesson $lesson): bool
    {
        if ($lesson->is_preview) {
            return true; // public preview
        }

        if ($user === null) {
            return false;
        }

        // ICS training staff manage; instructors manage their own courses.
        if ($user->hasAnyRole([Roles::SUPER_ADMIN, Roles::PLATFORM_ADMIN, Roles::ICS_TRAINING, Roles::TRAINER])) {
            return true;
        }

        return $this->isEnrolled($user, $lesson->course_id);
    }

    /** A learner may attempt assessments only inside an active enrollment. */
    public function canAttempt(?User $user, TrainingCourse $course): bool
    {
        return $this->isEnrolled($user, $course->id);
    }
}
