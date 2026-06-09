<?php

/*
| Migration: create_knowledge_articles_table  (Wave 3 / D-036 / D-038)
| Engine consumer: HasContentLifecycle + HasFullTextSearch + ContentAccessible (LATERAL).
| access_tier 1 public · 2 member · 3 CLIENT · 4 PARTNER · 5 internal (D-036). `excerpt` is
| ALWAYS public (teaser/SEO, W3-3); `body`/`file_path` are tier-gated. FULLTEXT(title,
| excerpt, body) on MySQL (W1c-1; SQLite test DB uses LIKE fallback).
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_articles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->enum('type', [
                'article', 'news', 'guide', 'white_paper', 'case_study', 'video', 'internal_kb', 'client_doc',
            ])->default('article');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();          // ALWAYS public (W3-3)
            $table->longText('body')->nullable();          // tier-gated
            $table->string('featured_image', 500)->nullable();
            $table->string('video_embed_url', 500)->nullable();
            $table->unsignedTinyInteger('access_tier')->default(1); // D-036 (LATERAL)
            $table->enum('status', ['draft', 'under_review', 'published', 'archived'])->default('draft');
            $table->unsignedTinyInteger('read_time_min')->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('bookmark_count')->default(0);
            $table->decimal('average_rating', 3, 2)->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->json('metadata')->nullable();          // D-029 loose coupling seam
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('category_id', 'idx_knowledge_articles_category');
            $table->index('type', 'idx_knowledge_articles_type');
            $table->index('access_tier', 'idx_knowledge_articles_tier');
            $table->index('status', 'idx_knowledge_articles_status');

            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->fullText(['title', 'excerpt', 'body'], 'ft_knowledge_articles'); // W1c-1
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_articles');
    }
};
