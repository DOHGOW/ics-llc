<?php

/*
| Migration: create_research_publications_table  (Wave 3 / D-034 / D-038)
| Engine consumer: HasContentLifecycle + HasFullTextSearch + ContentAccessible
| (HIERARCHICAL). access_tier 1 public · 2 member · 3 partner · 4 internal · 5 admin (D-034;
| user_tier >= tier). `abstract` is ALWAYS public (teaser/SEO, W3-3); `body`/`file_path`
| tier-gated. DOI for citation. FULLTEXT(title, abstract) on MySQL (W1c-1).
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('research_publications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->enum('content_group', [
                'summary', 'brief', 'public_report', 'insight', 'full_report', 'template', 'archive',
                'partner_research', 'collaborative', 'restricted', 'draft', 'working_paper', 'internal', 'pipeline',
            ]);
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('abstract');                       // ALWAYS public (W3-3)
            $table->longText('body')->nullable();            // tier-gated
            $table->string('file_path', 500)->nullable();    // tier-gated download
            $table->unsignedInteger('file_size_kb')->nullable();
            $table->string('doi', 100)->nullable();
            $table->date('publish_date')->nullable();
            $table->unsignedTinyInteger('access_tier')->default(1); // D-034 (HIERARCHICAL)
            $table->enum('status', ['draft', 'under_review', 'published', 'archived'])->default('draft');
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('download_count')->default(0);
            $table->unsignedInteger('citation_count')->default(0); // cached counter
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('category_id', 'idx_research_pubs_category');
            $table->index('access_tier', 'idx_research_pubs_tier');
            $table->index('status', 'idx_research_pubs_status');

            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->fullText(['title', 'abstract'], 'ft_research_pubs'); // W1c-1
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_publications');
    }
};
