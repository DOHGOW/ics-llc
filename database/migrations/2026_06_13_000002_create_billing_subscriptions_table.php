<?php

/*
| Migration: create_billing_subscriptions_table  (Wave Billing / D-084)
| THE entitlement source. status ∈ trial/active/past_due/cancelled/expired. Entitlement exists
| ONLY while {trial, active} (D-084 immediate revocation). Owner-scoped + tenant-scoped (D-086).
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('plan_id');
            $table->enum('status', ['trial', 'active', 'past_due', 'cancelled', 'expired'])->default('trial');
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('gateway_subscription_id', 100)->nullable();
            $table->string('gateway_customer_id', 100)->nullable();
            $table->string('gateway_email_token', 100)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('tenant_id', 'idx_billing_subs_tenant');
            $table->index('user_id', 'idx_billing_subs_user');
            $table->index('plan_id', 'idx_billing_subs_plan');
            $table->index('status', 'idx_billing_subs_status');
            $table->index('gateway_subscription_id', 'idx_billing_subs_gw');

            $table->foreign('plan_id', 'fk_billing_subs_plan')->references('id')->on('billing_plans');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_subscriptions');
    }
};
