<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use App\Models\Training\Enrollment;
use App\Models\Training\Lesson;
use App\Models\Training\TrainingCourse;
use App\Services\Training\EnrollmentService;
use App\Services\Training\TrainingAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Enrollment + enrollment-gated lesson access (Wave 4a / D-057). Learner-owned. Paid courses
 * are blocked pending Billing integration (W4-6).
 */
class EnrollmentController extends Controller
{
    public function __construct(
        private readonly EnrollmentService $enrollments,
        private readonly TrainingAccessService $access,
    ) {}

    public function enrol(Request $request, TrainingCourse $course): JsonResponse
    {
        abort_unless($request->user()->can('training.enrollments.create'), 403);
        abort_unless($course->isPublished(), 404);

        // W4-6: paid enrolment requires payment (Billing seam, D-031) — not yet wired.
        if ($course->is_paid) {
            return response()->json(['message' => __('Payment required — paid enrolment pending Billing integration.')], 402);
        }

        $enrollment = $this->enrollments->enrol($course, $request->user());

        return response()->json(['enrollment_id' => $enrollment->id, 'status' => $enrollment->status], 201);
    }

    public function myCourses(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('training.enrollments.read.own'), 403);

        return response()->json(
            Enrollment::query()->where('user_id', $request->user()->id)
                ->with('course:id,title,slug')
                ->select(['id', 'course_id', 'status', 'progress_percent', 'completed_at'])->paginate(25)
        );
    }

    /** Enrollment-gated lesson content (D-057). */
    public function viewLesson(Request $request, TrainingCourse $course, Lesson $lesson): JsonResponse
    {
        abort_unless((int) $lesson->course_id === (int) $course->id, 404);
        abort_unless($this->access->canAccessLesson($request->user(), $lesson), 403);

        // Record progress for enrolled learners (not for preview-only guests/staff).
        $enrollment = $this->access->activeEnrollment($request->user(), $course->id);
        if ($enrollment !== null) {
            $this->enrollments->recordLessonProgress($enrollment, $lesson, 'in_progress');
        }

        return response()->json($lesson);
    }

    public function completeLesson(Request $request, TrainingCourse $course, Lesson $lesson): JsonResponse
    {
        abort_unless((int) $lesson->course_id === (int) $course->id, 404);
        $enrollment = $this->access->activeEnrollment($request->user(), $course->id);
        abort_if($enrollment === null, 403);

        $this->enrollments->recordLessonProgress($enrollment, $lesson, 'completed');

        return response()->json(['message' => __('Lesson completed.'), 'progress_percent' => $enrollment->fresh()->progress_percent]);
    }
}
