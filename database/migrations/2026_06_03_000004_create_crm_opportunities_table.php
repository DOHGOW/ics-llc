<?php

/*
| Migration: create_crm_opportunities_table  (Wave 1d / D-012 / D-053)
| Opportunity pipeline; a qualified lead converts into an opportunity (`lead_id`).
| Visibility is assignment-scoped (D-053). crm_proposals/crm_contracts DEFERRED (W1d-6).
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_opportunities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->string('title');
            $table->decimal('value', 14, 2)->default(0);
            $table->char('currency', 3)->default('NGN');
            $table->enum('stage', ['qualification', 'proposal', 'negotiation', 'closed_won', 'closed_lost'])
                ->default('qualification');
            $table->date('close_date')->nullable();
            $table->unsignedTinyInteger('probability')->nullable()->default(20);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id', 'idx_crm_opp_tenant');
            $table->index('account_id', 'idx_crm_opp_account');
            $table->index('stage', 'idx_crm_opp_stage');
            $table->index('assigned_to', 'idx_crm_opp_assigned');

            $table->foreign('account_id', 'fk_crm_opp_account')
                ->references('id')->on('crm_accounts')->nullOnDelete();
            $table->foreign('lead_id', 'fk_crm_opp_lead')
                ->references('id')->on('crm_leads')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_opportunities');
    }
};
