<?php

/*
| Migration: create_client_deliverables_table  (Wave 2 / W2-1 / W2-5)
| CHILD of client_projects — parent-isolated (W2-1). Files are served ONLY via a
| policy-gated/streamed path or signed URL (W2-5), never as a public file_path. Drafts
| are hidden from the client until submitted/approved.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_deliverables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('milestone_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path', 500);
            $table->string('version', 20)->default('1.0');
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('project_id', 'idx_client_deliverables_project');
            $table->index('status', 'idx_client_deliverables_status');

            $table->foreign('project_id', 'fk_client_deliverables_project')
                ->references('id')->on('client_projects')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_deliverables');
    }
};
