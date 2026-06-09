<?php

namespace App\Models\Startup;

use Illuminate\Database\Eloquent\Model;

/**
 * Event score (program_event_scores, M-4 / H-3). One per judge × startup × criterion. Scores are
 * OPERATIONAL-MATURITY ratings only — NEVER valuation/equity/financial data (H-3).
 */
class ProgramEventScore extends Model
{
    protected $table = 'program_event_scores';

    protected $fillable = ['event_id', 'judge_id', 'startup_id', 'criterion', 'score', 'feedback'];

    protected function casts(): array
    {
        return ['score' => 'decimal:2'];
    }
}
