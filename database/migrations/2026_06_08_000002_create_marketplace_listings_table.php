<?php

/*
| Migration: create_marketplace_listings_table  (Wave 4c / D-011 / D-057 / D-060)
| Workflow: draft → pending_review → published → expired/rejected/removed. PUBLISHED = public
| (D-060 #4). `organisation_id` is PROVENANCE, NOT an isolation key (D-060 #1) — no AccountScope.
| Mandatory pre-publication review (no auto-publish). FULLTEXT for public search.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_listings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('posted_by_id');                 // owner
            $table->unsignedBigInteger('organisation_id')->nullable();  // PROVENANCE only (D-060 #1)
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('title');
            $table->longText('description');
            $table->enum('type', ['grant', 'tender', 'job', 'internship', 'scholarship', 'fellowship', 'accelerator']);
            $table->date('deadline')->nullable();
            $table->decimal('value', 14, 2)->nullable();
            $table->char('currency', 3)->nullable();
            $table->text('requirements')->nullable();
            $table->string('location', 150)->nullable();
            $table->boolean('is_remote')->default(false);
            $table->enum('status', ['draft', 'pending_review', 'published', 'expired', 'rejected', 'removed'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('application_count')->default(0);
            $table->unsignedBigInteger('shared_by_profile_id')->nullable(); // community cross-post (D-035)
            $table->timestamps();
            $table->softDeletes();

            $table->index('status', 'idx_mkt_listings_status');
            $table->index('type', 'idx_mkt_listings_type');
            $table->index('deadline', 'idx_mkt_listings_deadline');
            $table->index('posted_by_id', 'idx_mkt_listings_poster');

            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->fullText(['title', 'description'], 'ft_mkt_listings');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_listings');
    }
};
