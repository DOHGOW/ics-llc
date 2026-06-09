<?php

/*
| Migration: create_crm_accounts_table  (Wave 1d / D-012 / D-053)
| Internal Enterprise CRM. crm_accounts is the organisation entity itself — it is
| ICS master data, NOT org-owned (no AccountScope). `assigned_to` = the relationship
| owner (staff); visibility is assignment-scoped (D-053), never account-scoped.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();   // TenantScope-ready (D-037)
            $table->string('name');
            $table->enum('type', ['client', 'prospect', 'partner', 'government', 'ngo', 'sme', 'startup']);
            $table->string('industry', 100)->nullable();
            $table->string('website')->nullable();
            $table->char('country_code', 2)->nullable();
            $table->string('phone', 50)->nullable();
            $table->text('address')->nullable();
            $table->enum('status', ['active', 'inactive', 'prospect'])->default('prospect');
            $table->unsignedBigInteger('assigned_to')->nullable(); // staff relationship owner
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id', 'idx_crm_accounts_tenant');
            $table->index('type', 'idx_crm_accounts_type');
            $table->index('status', 'idx_crm_accounts_status');
            $table->index('assigned_to', 'idx_crm_accounts_assigned');

            $table->foreign('assigned_to', 'fk_crm_accounts_assigned')
                ->references('id')->on('core_users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_accounts');
    }
};
