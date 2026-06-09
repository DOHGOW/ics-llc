<?php

namespace App\Services\Training;

use App\Events\Training\AssessmentGraded;
use App\Models\Core\User;
use App\Models\Training\Assessment;
use App\Models\Training\AssessmentSubmission;
use App\Models\Training\Enrollment;

/**
 * Assessment submission + grading (Wave 4a / W4-5). Grading is SERVER-SIDE: objective
 * questions (mcq/true_false/short_answer) are auto-graded against correct_answer (which is
 * never sent to learners); subjective items await instructor grading. Attempts are capped.
 */
class AssessmentService
{
    /** @param array<int,mixed> $answers keyed by question id */
    public function submit(Enrollment $enrollment, Assessment $assessment, array $answers): AssessmentSubmission
    {
        $priorAttempts = AssessmentSubmission::where('enrollment_id', $enrollment->id)
            ->where('assessment_id', $assessment->id)->count();
        abort_if($priorAttempts >= $assessment->max_attempts, 422, 'Maximum attempts reached.');

        $submission = AssessmentSubmission::create([
            'enrollment_id' => $enrollment->id,
            'assessment_id' => $assessment->id,
            'attempt_number' => $priorAttempts + 1,
            'answers' => $answers,
            'submitted_at' => now(),
        ]);

        $this->autoGrade($assessment, $submission, $answers);

        return $submission;
    }

    /** @param array<int,mixed> $answers */
    private function autoGrade(Assessment $assessment, AssessmentSubmission $submission, array $answers): void
    {
        $questions = $assessment->questions; // includes correct_answer (server-side)
        $hasSubjective = $questions->contains(fn ($q) => $q->type === 'short_answer' && $q->correct_answer === '');

        // If any item needs human grading (assignment/exam essays), leave ungraded.
        if ($assessment->type === 'assignment' || $hasSubjective) {
            return;
        }

        $earned = 0;
        $total = 0;
        foreach ($questions as $question) {
            $total += $question->marks;
            $given = $answers[$question->id] ?? null;
            if ($this->isCorrect($question->type, $given, $question->correct_answer)) {
                $earned += $question->marks;
            }
        }

        $score = $total > 0 ? round(($earned / $total) * 100, 2) : 0.0;
        $passed = $score >= $assessment->pass_score;

        $submission->forceFill([
            'score' => $score,
            'passed' => $passed,
            'graded_at' => now(),
            'graded_by' => null, // auto-graded
        ])->save();

        event(new AssessmentGraded($submission));
    }

    private function isCorrect(string $type, mixed $given, string $correct): bool
    {
        if ($given === null) {
            return false;
        }

        return match ($type) {
            'mcq', 'true_false' => (string) $given === $correct,
            'short_answer' => mb_strtolower(trim((string) $given)) === mb_strtolower(trim($correct)),
            default => false,
        };
    }

    /** Instructor grading for subjective submissions. */
    public function grade(AssessmentSubmission $submission, float $score, bool $passed, ?string $feedback, User $grader): AssessmentSubmission
    {
        $submission->forceFill([
            'score' => $score,
            'passed' => $passed,
            'feedback' => $feedback,
            'graded_at' => now(),
            'graded_by' => $grader->id,
        ])->save();

        event(new AssessmentGraded($submission, $grader->id, $grader->getRoleNames()->first()));

        return $submission;
    }
}
