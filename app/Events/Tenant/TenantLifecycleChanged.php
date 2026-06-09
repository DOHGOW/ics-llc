<?php

namespace App\Events\Tenant;

use App\Models\Core\Tenant;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Tenant governance event (TENANT_MANAGEMENT, FT). action ∈ created|suspended|activated|
 * ownership_transferred|admin_elevated|residency_changed. ALL are HIGH-sensitivity (approved).
 */
class TenantLifecycleChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public string $action,
        public ?int $actorId = null,
        public ?string $actorRole = null,
    ) {}
}
