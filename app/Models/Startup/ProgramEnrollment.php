<?php

namespace App\Models\Startup;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Program participation record (startup_program_enrollments) — the GENERIC, governed
 * participation entity (D-065). Carries the full intake flow (M-1): applied → under_review →
 * accepted → active → graduated → withdrawn / removed. The membership key for the
 * incubation/acceleration lifecycle stages.
 */
class ProgramEnrollment extends Model
{
    protected $table = 'startup_program_enrollments';

    /** M-1 governed intake flow. */
    public const STATUSES = ['applied', 'under_review', 'accepted', 'active', 'graduated', 'withdrawn', 'removed'];

    /** States that occupy a startup's single active program slot (D-067 conflict guard). */
    public const ACTIVE_STATES = ['applied', 'under_review', 'accepted', 'active'];

    protected $fillable = [
        'startup_id', 'program_id', 'cohort_id', 'status', 'applied_at', 'enrolled_at',
        'graduated_at', 'decided_at', 'decided_by', 'withdrawal_reason', 'removal_reason',
    ];

    protected function casts(): array
    {
        return [
            'applied_at' => 'datetime',
            'enrolled_at' => 'datetime',
            'graduated_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function startup(): BelongsTo
    {
        return $this->belongsTo(Startup::class, 'startup_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(StartupProgram::class, 'program_id');
    }

    public function cohort(): BelongsTo
    {
        return $this->belongsTo(ProgramCohort::class, 'cohort_id');
    }
}
