<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use App\Models\Training\Assessment;
use App\Models\Training\AssessmentSubmission;
use App\Models\Training\TrainingCourse;
use App\Services\Training\AssessmentService;
use App\Services\Training\TrainingAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Assessment take/submit/grade (Wave 4a / W4-5). Learners must be enrolled. `correct_answer`
 * is NEVER returned (model $hidden + explicit column selection). Grading is server-side.
 */
class AssessmentController extends Controller
{
    public function __construct(
        private readonly AssessmentService $assessments,
        private readonly TrainingAccessService $access,
    ) {}

    /** Take view — questions WITHOUT correct answers (W4-5). */
    public function show(Request $request, Assessment $assessment): JsonResponse
    {
        $course = TrainingCourse::findOrFail($assessment->course_id);
        abort_unless($this->access->canAttempt($request->user(), $course), 403);

        $questions = $assessment->questions()
            ->get(['id', 'assessment_id', 'question_text', 'type', 'options', 'marks', 'sort_order']);

        return response()->json([
            'assessment' => $assessment->only(['id', 'title', 'type', 'pass_score', 'max_attempts', 'time_limit_min']),
            'questions' => $questions, // correct_answer not selected + $hidden
        ]);
    }

    public function submit(Request $request, Assessment $assessment): JsonResponse
    {
        abort_unless($request->user()->can('training.assessments.submit'), 403);
        $course = TrainingCourse::findOrFail($assessment->course_id);
        $enrollment = $this->access->activeEnrollment($request->user(), $course->id);
        abort_if($enrollment === null, 403);

        $data = $request->validate(['answers' => ['required', 'array']]);
        $submission = $this->assessments->submit($enrollment, $assessment, $data['answers']);

        return response()->json([
            'submission_id' => $submission->id,
            'attempt_number' => $submission->attempt_number,
            'score' => $submission->score,       // null until graded
            'passed' => $submission->passed,
        ], 201);
    }

    /** Instructor grading for subjective submissions. */
    public function grade(Request $request, AssessmentSubmission $submission): JsonResponse
    {
        abort_unless($request->user()->can('training.assessments.grade'), 403);

        $data = $request->validate([
            'score' => ['required', 'numeric', 'between:0,100'],
            'passed' => ['required', 'boolean'],
            'feedback' => ['nullable', 'string'],
        ]);

        $this->assessments->grade($submission, (float) $data['score'], (bool) $data['passed'], $data['feedback'] ?? null, $request->user());

        return response()->json(['message' => __('Submission graded.')]);
    }
}
