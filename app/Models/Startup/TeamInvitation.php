<?php

namespace App\Models\Startup;

use Illuminate\Database\Eloquent\Model;

/** Startup team invitation (startup_team_invitations, M-2). Token + accept flow. */
class TeamInvitation extends Model
{
    protected $table = 'startup_team_invitations';

    protected $fillable = [
        'startup_id', 'email', 'role', 'token', 'status', 'invited_by', 'expires_at',
    ];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }
}
