<?php

/*
| Migration: create_client_projects_table  (Wave 2 / D-050 / Wave 1a ownership)
| ORG-OWNED: account_id (NOT NULL → crm_accounts) is the ownership key. Isolated by
| AccountScope (BelongsToAccount) + OrgOwnedPolicy. FK has no ON DELETE action (RESTRICT,
| W2-8) — a crm_account with projects cannot be deleted out from under them.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_projects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();    // TenantScope-ready (D-037)
            $table->unsignedBigInteger('account_id');                // ownership key (D-050)
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['planning', 'active', 'on_hold', 'completed', 'cancelled'])->default('planning');
            $table->date('start_date')->nullable();
            $table->date('target_end_date')->nullable();
            $table->date('actual_end_date')->nullable();
            $table->unsignedBigInteger('project_manager_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('account_id', 'idx_client_projects_account');
            $table->index('status', 'idx_client_projects_status');

            $table->foreign('account_id', 'fk_client_projects_account')
                ->references('id')->on('crm_accounts'); // RESTRICT (W2-8)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_projects');
    }
};
