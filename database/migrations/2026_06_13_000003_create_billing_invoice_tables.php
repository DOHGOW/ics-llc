<?php

/*
| Migration: billing invoices + items + sequences  (Wave Billing / D-084 / D-086)
| Tenant-safe invoice numbering INV-{TENANT}-{YYYY}-{NNNNNN} via a per (tenant, year) sequence
| (same race-safe row-lock pattern as training certs). Items are polymorphic (any module bills).
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_invoice_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('last_sequence')->default(0);

            $table->unique(['tenant_id', 'year'], 'uk_billing_seq'); // D-086 per tenant+year
        });

        Schema::create('billing_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('invoice_number', 40)->unique(); // INV-{TENANT}-{YYYY}-{NNNNNN}
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->enum('status', ['draft', 'issued', 'paid', 'overdue', 'cancelled', 'refunded'])->default('draft');
            $table->date('issue_date');
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->char('currency', 3)->default('NGN');
            $table->text('notes')->nullable();
            $table->string('pdf_path', 500)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id', 'idx_billing_invoices_tenant');
            $table->index('user_id', 'idx_billing_invoices_user');
            $table->index('status', 'idx_billing_invoices_status');
            $table->foreign('subscription_id', 'fk_billing_invoices_sub')
                ->references('id')->on('billing_subscriptions')->nullOnDelete();
        });

        Schema::create('billing_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->string('description', 500);
            $table->decimal('quantity', 8, 2)->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_pct', 5, 2)->default(0);
            $table->string('module', 50)->nullable();
            $table->string('billable_type', 100)->nullable();
            $table->unsignedBigInteger('billable_id')->nullable();
            $table->timestamps();

            $table->index('invoice_id', 'idx_billing_items_invoice');
            $table->foreign('invoice_id', 'fk_billing_items_invoice')
                ->references('id')->on('billing_invoices')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_invoice_items');
        Schema::dropIfExists('billing_invoices');
        Schema::dropIfExists('billing_invoice_sequences');
    }
};
