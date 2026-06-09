<?php

namespace App\Models\Training;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Enrollment (training_enrollments) — THE access grant (D-057). Learner-owned (user_id);
 * unique per (user, course). Active enrollment unlocks lessons/assessments.
 */
class Enrollment extends Model
{
    protected $table = 'training_enrollments';

    public const STATUSES = ['active', 'completed', 'cancelled', 'suspended'];

    protected $fillable = [
        'tenant_id', 'course_id', 'user_id', 'status', 'enrolled_at', 'completed_at',
        'progress_percent', 'last_accessed_at', 'invoice_id',
    ];

    protected function casts(): array
    {
        return ['enrolled_at' => 'datetime', 'completed_at' => 'datetime', 'last_accessed_at' => 'datetime'];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(TrainingCourse::class, 'course_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class, 'enrollment_id');
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'completed'], true);
    }
}
