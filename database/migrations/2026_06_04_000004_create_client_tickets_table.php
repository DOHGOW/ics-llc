<?php

/*
| Migration: create_client_tickets_table  (Wave 2 / D-050 / Wave 1a ownership)
| ORG-OWNED: account_id (NOT NULL → crm_accounts). Isolated by AccountScope. A client user
| raises a ticket; ICS staff respond. `assigned_to` = the staff handler.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('account_id');                // ownership key (D-050)
            $table->unsignedBigInteger('user_id');                   // raising client user
            $table->string('title');
            $table->text('description');
            $table->enum('priority', ['low', 'normal', 'high', 'critical'])->default('normal');
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('account_id', 'idx_client_tickets_account');
            $table->index('status', 'idx_client_tickets_status');
            $table->index('priority', 'idx_client_tickets_priority');

            $table->foreign('account_id', 'fk_client_tickets_account')
                ->references('id')->on('crm_accounts');
            $table->foreign('project_id', 'fk_client_tickets_project')
                ->references('id')->on('client_projects')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_tickets');
    }
};
