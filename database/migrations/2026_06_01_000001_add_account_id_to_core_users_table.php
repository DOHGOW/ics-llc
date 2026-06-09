<?php

/*
|--------------------------------------------------------------------------
| Migration: add_account_id_to_core_users_table   (Wave 1a / D-050 / S2-2)
|--------------------------------------------------------------------------
| Purpose:       Organisation linkage for Phase 1 isolation. Adds the nullable
|                `account_id` column + index. NO foreign key yet — crm_accounts is
|                created in Wave 1d (CRM); the FK constraint is added then.
| Decision IDs:  D-050, D-004 (tenant-ready), D-037.
| Security:      Basis for AccountScope + BasePolicy::sameAccount (the sole Phase 1
|                cross-organisation isolation control). NULL = not org-bound (ICS
|                staff / Super Admin / individuals).
| Backward compat: nullable; existing rows unaffected; inert until consumed.
| Extension pts: FK → crm_accounts in Wave 1d; nests under tenant_id for Phase 3
|                TenantScope (tenant > account > user) — no rework.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('core_users', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id')->nullable()->after('tenant_id');
            $table->index('account_id', 'idx_core_users_account');
        });
    }

    public function down(): void
    {
        Schema::table('core_users', function (Blueprint $table) {
            $table->dropIndex('idx_core_users_account');
            $table->dropColumn('account_id');
        });
    }
};
