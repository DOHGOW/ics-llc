<?php

/*
| Migration: create_content_articles_table  (Wave 1c / D-010 / D-038 / D-052)
| FULLTEXT(title, body) on MySQL (W1c-1). Traceability (D-052). Public/tier-1.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_articles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->string('featured_image')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->enum('status', ['draft', 'under_review', 'published', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('view_count')->default(0);   // cached counter
            $table->unsignedBigInteger('created_by')->nullable();   // D-052
            $table->unsignedBigInteger('updated_by')->nullable();   // D-052
            $table->unsignedBigInteger('published_by')->nullable(); // D-052
            $table->timestamps();
            $table->softDeletes();

            $table->index('status', 'idx_content_articles_status');

            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->fullText(['title', 'body'], 'ft_content_articles'); // W1c-1
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_articles');
    }
};
