<?php

namespace App\Services\Training;

use App\Models\Training\AssessmentSubmission;
use App\Models\Training\Certificate;
use App\Models\Training\Enrollment;
use App\Models\Training\TrainingCourse;

/**
 * Training → Analytics aggregation hook (D-025). Per-module counters (NOT
 * content_engagement_events — Training is not ContentAccessible, W4-9). Scheduled job;
 * dashboards read persisted aggregates.
 */
class TrainingAnalyticsAggregator
{
    /** @return array<string,mixed> */
    public function snapshot(): array
    {
        $enrollments = Enrollment::query()->count();
        $completions = Enrollment::query()->where('status', 'completed')->count();
        $graded = AssessmentSubmission::query()->whereNotNull('passed')->count();
        $passed = AssessmentSubmission::query()->where('passed', true)->count();

        return [
            'generated_at' => now()->toIso8601String(),
            'enrollments' => $enrollments,
            'completions' => $completions,
            'completion_rate' => $enrollments > 0 ? round($completions / $enrollments * 100, 1) : 0.0,
            'assessment_pass_rate' => $graded > 0 ? round($passed / $graded * 100, 1) : 0.0,
            'certificates_issued' => Certificate::query()->whereIn('status', ['valid', 'expired'])->count(),
            'certificates_revoked' => Certificate::query()->where('status', 'revoked')->count(),
            'popular_courses' => TrainingCourse::query()->where('status', 'published')
                ->orderByDesc('enrollment_count')->limit(10)->pluck('enrollment_count', 'title')->all(),
        ];
    }
}
