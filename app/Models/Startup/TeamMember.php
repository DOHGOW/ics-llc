<?php

namespace App\Models\Startup;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Startup team member (startup_team_members). Participation key (D-061). `ownership_percent`
 * is gated governance data (C-1) — NEVER serialised in public/community/marketplace/analytics.
 */
class TeamMember extends Model
{
    protected $table = 'startup_team_members';

    public const ROLES = ['founder', 'co_founder', 'admin', 'member'];

    protected $fillable = [
        'startup_id', 'user_id', 'name', 'role', 'email', 'is_founder', 'ownership_percent', 'status',
    ];

    /** C-1: ownership data is hidden by default; surfaced only by gated, explicit reads. */
    protected $hidden = ['ownership_percent'];

    protected function casts(): array
    {
        return ['is_founder' => 'boolean', 'ownership_percent' => 'decimal:2'];
    }

    public function startup(): BelongsTo
    {
        return $this->belongsTo(Startup::class, 'startup_id');
    }
}
