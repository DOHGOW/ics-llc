<?php

/*
| Migration: create_training_enrollments_table  (Wave 4a / D-057)
| THE access mechanism: an active enrollment grants lesson/assessment access. Unique per
| (user, course). Paid courses link an invoice (D-031); free courses are active immediately.
| Learner-owned (user_id).
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_enrollments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('status', ['active', 'completed', 'cancelled', 'suspended'])->default('active');
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->timestamp('last_accessed_at')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable(); // D-031 (paid)
            $table->timestamps();

            $table->unique(['user_id', 'course_id'], 'uk_enrollment_user_course');
            $table->index('course_id', 'idx_training_enroll_course');
            $table->index('user_id', 'idx_training_enroll_user');

            $table->foreign('course_id', 'fk_training_enroll_course')
                ->references('id')->on('training_courses')->cascadeOnDelete();
            $table->foreign('user_id', 'fk_training_enroll_user')
                ->references('id')->on('core_users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_enrollments');
    }
};
