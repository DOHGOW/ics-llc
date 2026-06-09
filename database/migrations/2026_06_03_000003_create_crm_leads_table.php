<?php

/*
| Migration: create_crm_leads_table  (Wave 1d / D-012 / D-053)
| Lead pipeline. `account_id`/`contact_id` are SUBJECT pointers. Visibility is
| assignment-scoped (D-053). AI columns (ai_qualification_*) present but unused in
| Wave 1d — populated by the AI sprint (D-029).
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->string('source', 100)->comment('website|referral|community|manual|event');
            $table->string('source_detail')->nullable();
            $table->string('title');
            $table->decimal('value', 14, 2)->nullable();
            $table->char('currency', 3)->nullable()->default('NGN');
            $table->enum('stage', ['new', 'contacted', 'qualified', 'proposal', 'negotiation', 'closed_won', 'closed_lost'])
                ->default('new');
            $table->unsignedTinyInteger('probability')->nullable()->default(20);
            $table->date('expected_close_date')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->decimal('ai_qualification_score', 5, 2)->nullable(); // D-029 (deferred)
            $table->timestamp('ai_qualification_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id', 'idx_crm_leads_tenant');
            $table->index('stage', 'idx_crm_leads_stage');
            $table->index('assigned_to', 'idx_crm_leads_assigned');
            $table->index('account_id', 'idx_crm_leads_account');

            $table->foreign('contact_id', 'fk_crm_leads_contact')
                ->references('id')->on('crm_contacts')->nullOnDelete();
            $table->foreign('account_id', 'fk_crm_leads_account')
                ->references('id')->on('crm_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_leads');
    }
};
