<?php

namespace App\Models\Training;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Training course (training_courses). Published courses browse publicly; content is
 * ENROLLMENT-gated (D-057). NOT ContentAccessible, NOT org-owned. `validity_months` sets
 * certificate expiry (D-059).
 */
class TrainingCourse extends Model
{
    use SoftDeletes;

    protected $table = 'training_courses';

    public const STATUSES = ['draft', 'under_review', 'published', 'archived'];

    protected $fillable = [
        'tenant_id', 'instructor_id', 'category_id', 'title', 'slug', 'description', 'price',
        'currency', 'is_paid', 'level', 'delivery_mode', 'duration_hours', 'thumbnail_path',
        'certificate_template_path', 'validity_months', 'status', 'published_at', 'created_by',
    ];

    protected function casts(): array
    {
        return ['is_paid' => 'boolean', 'price' => 'decimal:2', 'published_at' => 'datetime'];
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class, 'instructor_id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class, 'course_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'course_id');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}
