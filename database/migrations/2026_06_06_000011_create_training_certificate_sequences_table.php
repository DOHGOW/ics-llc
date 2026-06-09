<?php

/*
| Migration: create_training_certificate_sequences_table  (Wave 4a / D-059)
| Per-year certificate number allocator (ICS-CERT-{YYYY}-{NNNNNN}). Same race-safe pattern
| as billing_invoice_sequences: increment under a row lock inside a transaction.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_certificate_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('last_sequence')->default(0);

            $table->unique(['tenant_id', 'year'], 'uk_training_cert_seq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_certificate_sequences');
    }
};
