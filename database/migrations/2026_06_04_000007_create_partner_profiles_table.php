<?php

/*
| Migration: create_partner_profiles_table  (Wave 2 / D-055)
| ORG-OWNED: account_id is now REQUIRED (D-055 — every partner has a crm_account, org or
| individual). Isolated by AccountScope. Approval/suspension are staff-only, audited
| (PORTAL_MANAGEMENT; suspension = HIGH, D-056).
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('account_id');                // REQUIRED (D-055)
            $table->unsignedBigInteger('tier_id')->nullable();
            $table->string('organisation_name');
            $table->enum('status', ['pending', 'active', 'suspended', 'terminated'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('agreement_signed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id', 'idx_partner_profiles_user');
            $table->index('account_id', 'idx_partner_profiles_account');
            $table->index('tier_id', 'idx_partner_profiles_tier');

            $table->foreign('user_id', 'fk_partner_profiles_user')
                ->references('id')->on('core_users');
            $table->foreign('account_id', 'fk_partner_profiles_account')
                ->references('id')->on('crm_accounts');
            $table->foreign('tier_id', 'fk_partner_profiles_tier')
                ->references('id')->on('partner_tiers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_profiles');
    }
};
