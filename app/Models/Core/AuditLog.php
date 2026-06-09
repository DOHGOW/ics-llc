<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

/**
 * Immutable audit log (core_audit_logs) — D-006 / D-039 SEC-03 / D-046.
 *
 * Append-only: there is no updated_at, and update()/delete() throw. Records are
 * written exclusively through AuditRepository::append() (write-only). This model
 * is otherwise read-only (for platform.audit.read).
 */
class AuditLog extends Model
{
    protected $table = 'core_audit_logs';

    public $timestamps = false; // only created_at, set explicitly on append

    protected $fillable = [
        'tenant_id', 'actor_id', 'actor_role', 'action', 'module',
        'category', 'sensitivity', 'record_type', 'record_id',
        'before_hash', 'after_hash', 'ip_address', 'user_agent', 'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    /** Append-only: mutation is forbidden (D-039 SEC-03). */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new \LogicException('Audit logs are append-only and cannot be updated.');
    }

    public function delete(): bool
    {
        throw new \LogicException('Audit logs are append-only and cannot be deleted.');
    }
}
