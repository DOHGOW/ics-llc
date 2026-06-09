<?php

/*
| Migration: create_partner_agreements_table  (Wave 2 / D-055 / D-056 / W2-5)
| ORG-OWNED: account_id REQUIRED (D-055) → AccountScope. Agreement files are policy-gated/
| signed (W2-5). Agreement events are audited HIGH-sensitivity (D-056).
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_agreements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('account_id');                // REQUIRED (D-055)
            $table->unsignedBigInteger('partner_id');
            $table->string('title');
            $table->string('type', 100);
            $table->date('effective_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->string('file_path', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('account_id', 'idx_partner_agreements_account');
            $table->index('partner_id', 'idx_partner_agreements_partner');
            $table->index('expiry_date', 'idx_partner_agreements_expiry');

            $table->foreign('account_id', 'fk_partner_agreements_account')
                ->references('id')->on('crm_accounts');
            $table->foreign('partner_id', 'fk_partner_agreements_partner')
                ->references('id')->on('partner_profiles')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_agreements');
    }
};
