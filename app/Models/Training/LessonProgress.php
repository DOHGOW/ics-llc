<?php

namespace App\Models\Training;

use Illuminate\Database\Eloquent\Model;

/** Per-enrollment lesson progress (training_lesson_progress). */
class LessonProgress extends Model
{
    protected $table = 'training_lesson_progress';

    protected $fillable = [
        'enrollment_id', 'lesson_id', 'status', 'started_at', 'completed_at', 'time_spent_sec',
    ];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'completed_at' => 'datetime'];
    }
}
