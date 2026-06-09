<?php

namespace App\Services\Training;

use App\Events\Training\CourseCompleted;
use App\Events\Training\EnrollmentCreated;
use App\Models\Core\User;
use App\Models\Training\Enrollment;
use App\Models\Training\Lesson;
use App\Models\Training\LessonProgress;
use App\Models\Training\TrainingCourse;
use Illuminate\Support\Facades\DB;

/**
 * Enrollment lifecycle (Wave 4a). Free courses enrol immediately (active). Paid courses are
 * a Billing seam (D-031/W4-6) — the controller blocks paid enrolment until payment is wired.
 * Completion issues a certificate (CertificateService).
 */
class EnrollmentService
{
    public function __construct(private readonly CertificateService $certificates) {}

    public function enrol(TrainingCourse $course, User $user): Enrollment
    {
        $enrollment = Enrollment::firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            ['tenant_id' => $course->tenant_id, 'status' => 'active', 'enrolled_at' => now()],
        );

        if ($enrollment->wasRecentlyCreated) {
            $course->increment('enrollment_count');
            event(new EnrollmentCreated($enrollment, $user->id, $user->getRoleNames()->first()));
        }

        return $enrollment;
    }

    public function recordLessonProgress(Enrollment $enrollment, Lesson $lesson, string $status): void
    {
        LessonProgress::updateOrCreate(
            ['enrollment_id' => $enrollment->id, 'lesson_id' => $lesson->id],
            [
                'status' => $status,
                'started_at' => DB::raw('COALESCE(started_at, NOW())'),
                'completed_at' => $status === 'completed' ? now() : null,
            ],
        );

        $this->recomputeProgress($enrollment);
    }

    private function recomputeProgress(Enrollment $enrollment): void
    {
        $total = Lesson::where('course_id', $enrollment->course_id)->count();
        if ($total === 0) {
            return;
        }

        $done = LessonProgress::where('enrollment_id', $enrollment->id)->where('status', 'completed')->count();
        $percent = (int) floor(($done / $total) * 100);
        $enrollment->forceFill(['progress_percent' => $percent, 'last_accessed_at' => now()])->save();

        if ($percent >= 100 && $enrollment->status !== 'completed') {
            $this->complete($enrollment);
        }
    }

    public function complete(Enrollment $enrollment): void
    {
        $enrollment->forceFill(['status' => 'completed', 'completed_at' => now(), 'progress_percent' => 100])->save();
        $enrollment->course?->increment('completion_count');

        event(new CourseCompleted($enrollment, $enrollment->user_id, optional($enrollment->user)->getRoleNames()->first()));

        // Issue the credential on completion (idempotent).
        $this->certificates->issueForEnrollment($enrollment);
    }
}
