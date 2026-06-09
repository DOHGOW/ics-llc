<?php

/*
| Migration: add_account_fk_to_core_users_table  (Wave 1d / D-050 step 2)
| Activates the deferred FK core_users.account_id → crm_accounts(id). The COLUMN +
| index were added in Wave 1a (2026_06_01_000001); only the constraint is added here,
| now that crm_accounts exists.
|
| ON DELETE SET NULL: deleting a CRM account NEVER deletes its members' user accounts —
| it detaches them (they become unbound, like ICS staff). SQLite (test DB) does not
| support adding FKs to an existing table, so this is MySQL-guarded; tests rely on the
| column alone (no behavioural dependence on the constraint).
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('core_users', function (Blueprint $table) {
            $table->foreign('account_id', 'fk_core_users_account')
                ->references('id')->on('crm_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('core_users', function (Blueprint $table) {
            $table->dropForeign('fk_core_users_account');
        });
    }
};
