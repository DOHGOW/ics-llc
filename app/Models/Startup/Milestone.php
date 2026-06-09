<?php

namespace App\Models\Startup;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Startup milestone (startup_milestones). Internal — team/staff only (M-1). */
class Milestone extends Model
{
    protected $table = 'startup_milestones';

    public const STATUSES = ['pending', 'in_progress', 'completed', 'cancelled'];

    protected $fillable = [
        'startup_id', 'title', 'description', 'category', 'target_date', 'completed_at', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return ['target_date' => 'date', 'completed_at' => 'datetime'];
    }

    public function startup(): BelongsTo
    {
        return $this->belongsTo(Startup::class, 'startup_id');
    }
}
