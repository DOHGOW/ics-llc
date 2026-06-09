<?php

namespace App\Services\Audit;

use App\Audit\AuditCategory;
use App\Audit\AuditSensitivity;
use App\Authorization\Roles;
use App\Models\Core\AuditLog;
use App\Repositories\Audit\AuditRepository;

/**
 * Audit orchestration (D-006 / D-039 / D-046).
 *
 * Resolves sensitivity (ALL Super Admin actions = high; plus the high-sensitivity
 * categories), hashes before/after state (never stores raw sensitive data), and
 * appends via the write-only repository. There is no update/delete path.
 */
class AuditService
{
    public function __construct(private readonly AuditRepository $repository) {}

    /**
     * @param  array<string,mixed>|null  $before
     * @param  array<string,mixed>|null  $after
     */
    public function log(
        string $action,
        string $module,
        string $category = AuditCategory::GENERAL,
        ?int $actorId = null,
        ?string $actorRole = null,
        ?string $recordType = null,
        ?int $recordId = null,
        ?array $before = null,
        ?array $after = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?int $tenantId = null,
        ?string $forceSensitivity = null,
    ): AuditLog {
        return $this->repository->append([
            'tenant_id' => $tenantId,
            'actor_id' => $actorId,
            'actor_role' => $actorRole,
            'action' => $action,
            'module' => $module,
            'category' => $category,
            'sensitivity' => $this->resolveSensitivity($category, $actorRole, $forceSensitivity),
            'record_type' => $recordType,
            'record_id' => $recordId,
            'before_hash' => $before !== null ? $this->hash($before) : null,
            'after_hash' => $after !== null ? $this->hash($after) : null,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * High-sensitivity when the actor is a Super Admin (ALL their actions), OR the caller
     * explicitly forces it (D-056: specific high-value events within an otherwise-normal
     * category — agreements, commissions, suspensions), OR the category is inherently
     * high-sensitivity (D-046).
     */
    private function resolveSensitivity(string $category, ?string $actorRole, ?string $forceSensitivity = null): string
    {
        if ($actorRole === Roles::SUPER_ADMIN) {
            return AuditSensitivity::HIGH;
        }

        if ($forceSensitivity === AuditSensitivity::HIGH) {
            return AuditSensitivity::HIGH;
        }

        return in_array($category, AuditCategory::HIGH_SENSITIVITY, true)
            ? AuditSensitivity::HIGH
            : AuditSensitivity::NORMAL;
    }

    /** Hash of serialized state — the audit log stores hashes, never raw data. */
    private function hash(array $state): string
    {
        return hash('sha256', (string) json_encode($state));
    }
}
