<?php

/*
| Migration: extend_core_tenants_for_franchise  (Wave FT-1 / D-079 / D-077)
| Adds the Franchise governance columns (parent_tenant_id regional hierarchy, country, residency,
| owner) and seeds the ROOT default tenant (config ics.tenancy.default_tenant_id) so existing
| single-tenant data has a home. Additive + reversible (D-077) — no destructive change.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('core_tenants', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_tenant_id')->nullable()->after('id'); // D-079 regional hierarchy
            $table->char('country_code', 2)->nullable();
            $table->string('residency_region', 50)->nullable();
            $table->unsignedBigInteger('owner_user_id')->nullable();

            $table->index('parent_tenant_id', 'idx_core_tenants_parent');
        });

        // Seed the ROOT default tenant (idempotent) so existing rows backfill to a real tenant.
        $defaultId = (int) config('ics.tenancy.default_tenant_id', 1);
        if (DB::table('core_tenants')->where('id', $defaultId)->doesntExist()) {
            DB::table('core_tenants')->insert([
                'id' => $defaultId,
                'name' => 'ICS (Root Tenant)',
                'slug' => 'ics-root',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('core_tenants', function (Blueprint $table) {
            $table->dropIndex('idx_core_tenants_parent');
            $table->dropColumn(['parent_tenant_id', 'country_code', 'residency_region', 'owner_user_id']);
        });
        // The root tenant row is intentionally NOT deleted on rollback (data safety, D-077).
    }
};
