<?php

namespace App\Models\Startup;

use Illuminate\Database\Eloquent\Model;

/**
 * Immutable founder-ownership transfer record (startup_ownership_transfers, H-2/D-064).
 * Append-only: updates/deletes throw — the transfer history is tamper-evident.
 */
class OwnershipTransfer extends Model
{
    protected $table = 'startup_ownership_transfers';

    public $timestamps = false;

    protected $fillable = ['startup_id', 'from_founder_id', 'to_founder_id', 'actor_id', 'reason', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \RuntimeException('Ownership transfers are immutable (H-2).'));
        static::deleting(fn () => throw new \RuntimeException('Ownership transfers are immutable (H-2).'));
    }
}
