<?php

namespace App\Models\Startup;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Program cohort / intake cycle (program_cohorts, D-065). Shared by Incubator + Accelerator.
 * Belongs to a startup_program (type carries incubator/accelerator).
 */
class ProgramCohort extends Model
{
    protected $table = 'program_cohorts';

    public const STATUSES = ['planned', 'intake_open', 'active', 'closed', 'archived'];

    protected $fillable = [
        'tenant_id', 'program_id', 'name', 'intake_opens_at', 'intake_closes_at',
        'start_date', 'end_date', 'max_startups', 'status',
    ];

    protected function casts(): array
    {
        return [
            'intake_opens_at' => 'datetime',
            'intake_closes_at' => 'datetime',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(StartupProgram::class, 'program_id');
    }

    public function coordinators(): HasMany
    {
        return $this->hasMany(ProgramCoordinator::class, 'cohort_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(ProgramEnrollment::class, 'cohort_id');
    }

    public function intakeIsOpen(): bool
    {
        return $this->status === 'intake_open';
    }
}
