<?php

/*
| Migration: create_knowledge_resources_table  (Wave 3 / D-036 / D-038)
| Downloadable asset library (templates, toolkits, SOPs, checklists, datasets). Split out
| from the knowledge_articles `type` enum into a dedicated FILE-CENTRIC table per the Wave 3
| scope — it REUSES the content engine (HasContentLifecycle + HasFullTextSearch +
| ContentAccessible LATERAL), so no access/lifecycle logic is duplicated (D-038). `description`
| is the public teaser (W3-3); `file_path` is tier-gated download. Blueprint reconciled.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_resources', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->enum('type', ['template', 'toolkit', 'sop', 'checklist', 'dataset', 'download', 'other'])->default('template');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();        // ALWAYS public teaser (W3-3)
            $table->string('file_path', 500)->nullable();    // tier-gated download
            $table->unsignedInteger('file_size_kb')->nullable();
            $table->unsignedTinyInteger('access_tier')->default(1); // D-036 (LATERAL)
            $table->enum('status', ['draft', 'under_review', 'published', 'archived'])->default('draft');
            $table->unsignedInteger('download_count')->default(0);
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('category_id', 'idx_knowledge_resources_category');
            $table->index('type', 'idx_knowledge_resources_type');
            $table->index('access_tier', 'idx_knowledge_resources_tier');
            $table->index('status', 'idx_knowledge_resources_status');

            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->fullText(['title', 'description'], 'ft_knowledge_resources'); // W1c-1
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_resources');
    }
};
