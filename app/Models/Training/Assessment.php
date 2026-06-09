<?php

namespace App\Models\Training;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Assessment (training_assessments). Questions hold correct_answer (server-side only, W4-5). */
class Assessment extends Model
{
    protected $table = 'training_assessments';

    protected $fillable = [
        'course_id', 'lesson_id', 'title', 'type', 'pass_score', 'max_attempts', 'time_limit_min',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(AssessmentQuestion::class, 'assessment_id')->orderBy('sort_order');
    }
}
