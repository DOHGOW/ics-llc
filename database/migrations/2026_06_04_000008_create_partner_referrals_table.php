<?php

/*
| Migration: create_partner_referrals_table  (Wave 2 / D-055 / W2-3)
| ORG-OWNED: account_id REQUIRED (D-055) → AccountScope. `lead_id` links to the internal
| crm_lead created when ICS qualifies the referral — but it is ICS-ONLY and is NEVER
| exposed to the partner (W2-3). Commission tracked here (D-031); commission events audited
| HIGH (D-056).
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_referrals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('account_id');                // REQUIRED (D-055)
            $table->unsignedBigInteger('partner_id');                // → partner_profiles
            $table->string('referred_org_name');
            $table->string('referred_contact')->nullable();
            $table->string('referred_email')->nullable();
            $table->enum('stage', ['submitted', 'qualified', 'converted', 'lost'])->default('submitted');
            $table->unsignedBigInteger('lead_id')->nullable();       // ICS-ONLY (W2-3) — never serialised to partner
            $table->decimal('commission_amount', 12, 2)->nullable();
            $table->char('commission_currency', 3)->nullable()->default('NGN');
            $table->timestamp('commission_paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('account_id', 'idx_partner_referrals_account');
            $table->index('partner_id', 'idx_partner_referrals_partner');
            $table->index('lead_id', 'idx_partner_referrals_lead');
            $table->index('stage', 'idx_partner_referrals_stage');

            $table->foreign('account_id', 'fk_partner_referrals_account')
                ->references('id')->on('crm_accounts');
            $table->foreign('partner_id', 'fk_partner_referrals_partner')
                ->references('id')->on('partner_profiles')->cascadeOnDelete();
            $table->foreign('lead_id', 'fk_partner_referrals_lead')
                ->references('id')->on('crm_leads')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_referrals');
    }
};
