<?php

namespace App\Models\Startup;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Startup mentor / advisor (startup_mentors, M-3). Notes private (team/mentor/staff). */
class Mentor extends Model
{
    protected $table = 'startup_mentors';

    public const TYPES = ['mentor', 'advisor'];

    protected $fillable = [
        'startup_id', 'mentor_id', 'type', 'assigned_at', 'assigned_by', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return ['assigned_at' => 'datetime'];
    }

    public function startup(): BelongsTo
    {
        return $this->belongsTo(Startup::class, 'startup_id');
    }
}
