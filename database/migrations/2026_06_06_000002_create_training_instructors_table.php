<?php

/*
| Migration: create_training_instructors_table  (Wave 4a)
| Instructor profile (one per user). Approval is a staff governance action (audited,
| TRAINING_MANAGEMENT). Owns the courses they teach.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_instructors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('user_id')->unique();
            $table->text('bio')->nullable();
            $table->json('specializations')->nullable();
            $table->enum('status', ['pending', 'active', 'inactive'])->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id', 'fk_training_instructors_user')
                ->references('id')->on('core_users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_instructors');
    }
};
