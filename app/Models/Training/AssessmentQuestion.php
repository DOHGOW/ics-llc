<?php

namespace App\Models\Training;

use Illuminate\Database\Eloquent\Model;

/**
 * Assessment question (training_assessment_questions). W4-5: `correct_answer` is in
 * $hidden and is NEVER serialised — learner-facing resources exclude it; it is read
 * server-side only for grading.
 */
class AssessmentQuestion extends Model
{
    protected $table = 'training_assessment_questions';

    protected $fillable = [
        'assessment_id', 'question_text', 'type', 'options', 'correct_answer', 'marks', 'sort_order',
    ];

    /** W4-5 defence in depth: never leaves the server in any serialisation. */
    protected $hidden = ['correct_answer'];

    protected function casts(): array
    {
        return ['options' => 'array'];
    }
}
