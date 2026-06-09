<?php

/*
| Migration: create_billing_plans_table  (Wave Billing / D-031 / D-084)
| Plan definitions. `module='membership'` plans carry the tier-elevation grants
| (knowledge_tier_grant/research_tier_grant) consumed by Membership via MembershipTierResolver
| (D-080, separate gate). tenant_id → per-tenant plans (D-086). Gateway driver config-only (D-037).
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('name');
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['subscription', 'one_time']);
            $table->string('module', 50)->comment('training|membership|marketplace|consulting|event|research|knowledge');
            $table->enum('billing_period', ['monthly', 'quarterly', 'annual', 'one_time']);
            $table->decimal('price', 12, 2)->default(0);
            $table->char('currency', 3)->default('NGN');
            $table->unsignedTinyInteger('trial_days')->default(0);
            $table->unsignedTinyInteger('research_tier_grant')->nullable();   // D-080 hook
            $table->unsignedTinyInteger('knowledge_tier_grant')->nullable();  // D-080 hook
            $table->json('features')->nullable();
            $table->string('gateway_plan_id', 100)->nullable();               // Paystack plan code
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id', 'idx_billing_plans_tenant');
            $table->index('module', 'idx_billing_plans_module');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_plans');
    }
};
