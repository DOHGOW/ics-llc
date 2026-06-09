<?php

namespace App\Models\Training;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lesson (training_lessons). ENROLLMENT-gated (D-057) unless is_preview. `content`/file are
 * served only to enrolled learners (TrainingAccessService).
 */
class Lesson extends Model
{
    protected $table = 'training_lessons';

    protected $fillable = [
        'tenant_id', 'course_id', 'section_id', 'title', 'type', 'content', 'video_embed_url',
        'file_path', 'sort_order', 'duration_minutes', 'is_preview',
    ];

    protected function casts(): array
    {
        return ['is_preview' => 'boolean'];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(TrainingCourse::class, 'course_id');
    }
}
