<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Four-eyes Super Admin role-escalation request (D-044 / D-045).
 * Single-purpose record; decided once. The immutable trail lives in
 * core_audit_logs (every transition is mirrored there).
 */
class RoleEscalationApproval extends Model
{
    protected $table = 'core_role_escalation_approvals';

    protected $fillable = [
        'requester_id',
        'target_user_id',
        'approver_id',
        'requested_role',
        'previous_role',
        'reason_code',
        'status',
        'requester_ip',
        'approver_ip',
        'decided_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'decided_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
