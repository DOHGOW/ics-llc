<?php

/*
|--------------------------------------------------------------------------
| Migration: create_core_tenants_table        (Task T-3.1)
|--------------------------------------------------------------------------
| Purpose:       Root tenant registry. Makes the platform tenant-aware from
|                the first migration (D-004). Phase 1 operates single-tenant
|                (ICS data uses NULL tenant_id elsewhere); multi-tenant and
|                Franchise Operations activate in Phase 3 with no schema change.
| Decision IDs:  D-004 (multi-tenancy), D-019 (franchise reserved), D-037.
| Security:      `settings` JSON must never store secrets. Tenant isolation is
|                enforced at the application layer (TenantScope, deferred to P3).
|                `slug` unique prevents tenant collision.
| Dependencies:  None — this is the first/root table.
| Extension pts: `status` enum extensible; `settings` JSON for per-tenant
|                branding/config; `domain` for custom-domain tenants (P3);
|                global TenantScope activation (P3) needs no column change.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 100)->unique();
            $table->string('domain')->nullable();
            $table->enum('status', ['active', 'suspended', 'trial'])->default('active');
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status', 'idx_core_tenants_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_tenants');
    }
};
