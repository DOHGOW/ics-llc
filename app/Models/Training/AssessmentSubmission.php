<?php

namespace App\Models\Training;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Assessment submission (training_assessment_submissions). Scored server-side. */
class AssessmentSubmission extends Model
{
    protected $table = 'training_assessment_submissions';

    protected $fillable = [
        'enrollment_id', 'assessment_id', 'attempt_number', 'answers', 'score', 'passed',
        'submitted_at', 'graded_at', 'graded_by', 'feedback',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'passed' => 'boolean',
            'score' => 'decimal:2',
            'submitted_at' => 'datetime',
            'graded_at' => 'datetime',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_id');
    }
}
