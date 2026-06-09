<?php

/*
| Migration: create_content_pages_table  (Wave 1c / D-010 / D-038 / D-052)
| FULLTEXT(title, body) on MySQL (W1c-1, mandatory). Traceability columns (D-052).
| Public/tier-1 content — NOT org-owned (no account_id / AccountScope).
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_pages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('body')->nullable();
            $table->string('template')->default('default');
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->enum('status', ['draft', 'under_review', 'published', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();   // D-052
            $table->unsignedBigInteger('updated_by')->nullable();   // D-052
            $table->unsignedBigInteger('published_by')->nullable(); // D-052
            $table->timestamps();
            $table->softDeletes();

            $table->index('status', 'idx_content_pages_status');

            // W1c-1: FULLTEXT is a MySQL feature (production + engine-parity CI).
            // The SQLite unit-test DB uses the HasFullTextSearch LIKE fallback.
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->fullText(['title', 'body'], 'ft_content_pages');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_pages');
    }
};
