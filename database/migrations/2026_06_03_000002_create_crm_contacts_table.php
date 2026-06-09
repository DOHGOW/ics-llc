<?php

/*
| Migration: create_crm_contacts_table  (Wave 1d / D-012 / D-053)
| A contact belongs to a crm_account (subject pointer, NOT an ownership key).
| Visibility is assignment-scoped (D-053).
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('account_id')->nullable(); // SUBJECT: the account this contact belongs to
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('job_title', 150)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id', 'idx_crm_contacts_tenant');
            $table->index('account_id', 'idx_crm_contacts_account');
            $table->index('email', 'idx_crm_contacts_email');
            $table->index('assigned_to', 'idx_crm_contacts_assigned');

            $table->foreign('account_id', 'fk_crm_contacts_account')
                ->references('id')->on('crm_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_contacts');
    }
};
