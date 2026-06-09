<?php

/*
| Migration: create_client_project_milestones_table  (Wave 2 / W2-1)
| CHILD of client_projects — NOT org-owned (no account_id, no BelongsToAccount). Isolated
| via its parent project (parent-based isolation, W2-1). Never queried independently.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_project_milestones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'missed'])->default('pending');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('project_id', 'idx_client_milestones_project');

            $table->foreign('project_id', 'fk_client_milestones_project')
                ->references('id')->on('client_projects')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_project_milestones');
    }
};
