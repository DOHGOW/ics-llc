<?php

/*
| Migration: create_training_courses_table  (Wave 4a / D-057 / D-059)
| Published courses are PUBLIC to browse (FULLTEXT title, description). Content is
| ENROLLMENT-gated (D-057), NOT tier/org. `validity_months` (D-059) sets certificate expiry
| (NULL = no expiry). Paid courses integrate Billing (D-031) at enrollment.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_courses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('instructor_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->char('currency', 3)->default('NGN');
            $table->boolean('is_paid')->default(false);
            $table->enum('level', ['beginner', 'intermediate', 'advanced', 'all'])->default('all');
            $table->enum('delivery_mode', ['online', 'in_person', 'hybrid'])->default('online');
            $table->decimal('duration_hours', 6, 1)->nullable();
            $table->string('thumbnail_path', 500)->nullable();
            $table->string('certificate_template_path', 500)->nullable();
            $table->unsignedTinyInteger('validity_months')->nullable(); // D-059 (NULL = no expiry)
            $table->enum('status', ['draft', 'under_review', 'published', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('enrollment_count')->default(0);
            $table->unsignedInteger('completion_count')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status', 'idx_training_courses_status');
            $table->index('instructor_id', 'idx_training_courses_instructor');

            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->fullText(['title', 'description'], 'ft_training_courses');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_courses');
    }
};
