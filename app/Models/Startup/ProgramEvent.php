<?php

namespace App\Models\Startup;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Program event (program_events, D-068 / M-1) — ONE generic, lightweight event type for the
 * accelerator surface (demo_day/pitch_event/showcase/readiness_review/graduation_showcase).
 * NO workflow states / orchestration — only a `finalized_at` lock.
 */
class ProgramEvent extends Model
{
    protected $table = 'program_events';

    public const TYPES = ['demo_day', 'pitch_event', 'showcase', 'readiness_review', 'graduation_showcase'];

    protected $fillable = [
        'tenant_id', 'cohort_id', 'type', 'title', 'description', 'scheduled_at', 'finalized_at', 'created_by',
    ];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime', 'finalized_at' => 'datetime'];
    }

    public function cohort(): BelongsTo
    {
        return $this->belongsTo(ProgramCohort::class, 'cohort_id');
    }

    public function judges(): HasMany
    {
        return $this->hasMany(ProgramEventJudge::class, 'event_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(ProgramEventScore::class, 'event_id');
    }

    public function isFinalized(): bool
    {
        return $this->finalized_at !== null;
    }
}
