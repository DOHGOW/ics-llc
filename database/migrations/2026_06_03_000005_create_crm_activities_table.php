<?php

/*
| Migration: create_crm_activities_table  (Wave 1d / D-012 / W1d-2)
| Polymorphic engagement timeline against a lead/opportunity/account. NOTES are an
| activity type ('note') — crm_notes is NOT a separate table (W1d-2). Visibility is
| assignment-scoped (D-053).
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('subject_type', 100)->comment('polymorphic: Lead|Opportunity|Account');
            $table->unsignedBigInteger('subject_id');
            $table->enum('type', ['call', 'email', 'meeting', 'note', 'task', 'demo']); // 'note' = W1d-2
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['subject_type', 'subject_id'], 'idx_crm_activities_subject');
            $table->index('due_at', 'idx_crm_activities_due');
            $table->index('assigned_to', 'idx_crm_activities_assigned');
            $table->index('type', 'idx_crm_activities_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_activities');
    }
};
