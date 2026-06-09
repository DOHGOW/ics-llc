<?php

namespace App\Models\Startup;

use Illuminate\Database\Eloquent\Model;

/** Event judge (program_event_judges, H-2). References an existing user — not a new registry. */
class ProgramEventJudge extends Model
{
    protected $table = 'program_event_judges';

    protected $fillable = ['event_id', 'user_id', 'assigned_by'];
}
