<?php

namespace App\Models\Startup;

use Illuminate\Database\Eloquent\Model;

/**
 * Program coordinator (program_coordinators, M-2). A PROGRAM concern — NOT CRM assignment /
 * HasAssignmentVisibility. Coordinators manage cohorts.
 */
class ProgramCoordinator extends Model
{
    protected $table = 'program_coordinators';

    protected $fillable = ['cohort_id', 'user_id', 'assigned_by', 'assigned_at'];

    protected function casts(): array
    {
        return ['assigned_at' => 'datetime'];
    }
}
