<?php

/*
| Migration: billing payments + webhooks  (Wave Billing / D-084)
| Payments: gateway_transaction_id UNIQUE = the duplicate-payment idempotency key (Test F).
| Webhooks: APPEND-ONLY inbound log; gateway_event_id idempotency (Test A) + signature_valid
| (Test B, verified BEFORE processing) + processed flag (replay safety). Reconciliation reads both.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->enum('gateway', ['paystack', 'flutterwave', 'stripe'])->default('paystack');
            $table->string('gateway_transaction_id', 100)->unique(); // idempotency (Test F)
            $table->string('gateway_transaction_ref', 100)->nullable();
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('NGN');
            $table->enum('status', ['pending', 'success', 'failed', 'refunded', 'chargeback'])->default('pending');
            $table->string('payment_method', 50)->nullable();
            $table->string('channel', 50)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('gateway_response')->nullable();
            $table->timestamps();

            $table->index('tenant_id', 'idx_billing_payments_tenant');
            $table->index('invoice_id', 'idx_billing_payments_invoice');
            $table->index('user_id', 'idx_billing_payments_user');
        });

        Schema::create('billing_webhooks', function (Blueprint $table) {
            $table->id();
            $table->enum('gateway', ['paystack', 'flutterwave', 'stripe']);
            $table->string('event_type', 100);
            $table->string('gateway_event_id', 100)->nullable(); // idempotency (Test A)
            $table->json('payload');
            $table->boolean('signature_valid')->default(false);   // verified first (Test B)
            $table->boolean('processed')->default(false);          // replay safety
            $table->timestamp('processed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['gateway', 'gateway_event_id'], 'uk_billing_webhook_event'); // dedupe
            $table->index('processed', 'idx_billing_webhooks_processed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_webhooks');
        Schema::dropIfExists('billing_payments');
    }
};
