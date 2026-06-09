<?php

namespace App\Repositories\Audit;

use App\Models\Core\AuditLog;
use Illuminate\Support\Collection;

/**
 * Write-only / append-only audit persistence (D-039 SEC-03).
 *
 * Exposes ONLY append + read queries. There is intentionally no update or delete
 * method; the AuditLog model also throws on mutation. This is the single,
 * narrow seam through which audit records are written.
 */
class AuditRepository
{
    /** @param array<string,mixed> $attributes */
    public function append(array $attributes): AuditLog
    {
        $attributes['created_at'] ??= now();

        // create() performs an INSERT only — no update path exists here.
        return AuditLog::query()->create($attributes);
    }

    public function forRecord(string $recordType, int $recordId): Collection
    {
        return AuditLog::query()
            ->where('record_type', $recordType)
            ->where('record_id', $recordId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function highSensitivity(int $limit = 100): Collection
    {
        return AuditLog::query()
            ->where('sensitivity', 'high')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
