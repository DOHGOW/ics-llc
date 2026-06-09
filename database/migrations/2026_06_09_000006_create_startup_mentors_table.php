<?php

/*
| Migration: create_startup_mentors_table  (Wave 5A / M-3)
| Mentors AND advisory board via a single table with `type` (mentor/advisor) — no parallel
| advisory table (D-038 no-duplication). Assignment is a staff action (audited). Notes private.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('startup_mentors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('startup_id');
            $table->unsignedBigInteger('mentor_id');
            $table->enum('type', ['mentor', 'advisor'])->default('mentor'); // M-3 advisory board
            $table->timestamp('assigned_at')->useCurrent();
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->enum('status', ['active', 'ended'])->default('active');
            $table->text('notes')->nullable(); // private (team/mentor/staff)
            $table->timestamps();

            $table->index('startup_id', 'idx_startup_mentors_startup');
            $table->index('mentor_id', 'idx_startup_mentors_mentor');
            $table->foreign('startup_id', 'fk_startup_mentors_startup')
                ->references('id')->on('startup_profiles')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('startup_mentors');
    }
};
