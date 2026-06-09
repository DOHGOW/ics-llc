<?php

namespace App\Models\Startup;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Startup program (startup_programs). The GENERIC program definition (D-065). `type` =
 * general/incubator/accelerator — Incubator & Accelerator are SPECIALIZATIONS, not separate
 * architectures. Governance states (suspended/terminated/archived) are HIGH-audited (D-066).
 */
class StartupProgram extends Model
{
    protected $table = 'startup_programs';

    public const TYPES = ['general', 'incubator', 'accelerator'];

    public const STATUSES = ['planned', 'active', 'suspended', 'completed', 'terminated', 'archived', 'cancelled'];

    protected $fillable = [
        'tenant_id', 'name', 'type', 'cohort_name', 'start_date', 'end_date', 'max_startups', 'description', 'status',
    ];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date'];
    }

    public function cohorts(): HasMany
    {
        return $this->hasMany(ProgramCohort::class, 'program_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(ProgramEnrollment::class, 'program_id');
    }
}
