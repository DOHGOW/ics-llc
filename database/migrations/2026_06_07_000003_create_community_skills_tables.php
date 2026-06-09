<?php

/*
| Migration: community skills graph  (Wave 4b / D-035)
| Skills (reference) + profile_skills (M2M, endorsement_count cached) + endorsements (peer,
| unique per profile+skill+endorser). Endorsements are ANALYTICS events (W4b-6) — not audited.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_skills', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('slug', 100)->unique();
            $table->string('category', 100); // Technology|Consulting|Training|Research|Business
            $table->timestamps();
        });

        Schema::create('community_profile_skills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('profile_id');
            $table->unsignedBigInteger('skill_id');
            $table->unsignedInteger('endorsement_count')->default(0);
            $table->timestamps();

            $table->unique(['profile_id', 'skill_id'], 'uk_profile_skill');
            $table->index('skill_id', 'idx_community_ps_skill');
            $table->foreign('profile_id', 'fk_community_ps_profile')
                ->references('id')->on('community_profiles')->cascadeOnDelete();
            $table->foreign('skill_id', 'fk_community_ps_skill')
                ->references('id')->on('community_skills')->cascadeOnDelete();
        });

        Schema::create('community_endorsements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('profile_id');
            $table->unsignedBigInteger('skill_id');
            $table->unsignedBigInteger('endorsed_by_id');
            $table->timestamps();

            $table->unique(['profile_id', 'skill_id', 'endorsed_by_id'], 'uk_endorsement');
            $table->foreign('profile_id', 'fk_community_end_profile')
                ->references('id')->on('community_profiles')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_endorsements');
        Schema::dropIfExists('community_profile_skills');
        Schema::dropIfExists('community_skills');
    }
};
