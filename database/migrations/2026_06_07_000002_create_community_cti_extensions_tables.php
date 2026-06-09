<?php

/*
| Migration: community CTI extension tables  (Wave 4b / D-035)
| Six type extensions (profile_id UNIQUE → 1:1 with the base). Each stores its OWN public
| fields. The cross-module link pointers (startup_id/instructor_id/partner_id/author_id) are
| REFERENCES only — W4b-1 forbids exposing the linked module's internals; W4b-2 requires the
| linking user to own the record or be ICS-verified.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_founder_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('profile_id')->unique();
            $table->unsignedBigInteger('startup_id')->nullable();   // link (W4b-2)
            $table->enum('stage', ['idea', 'mvp', 'growth', 'scale', 'exit'])->nullable();
            $table->json('industries')->nullable();
            $table->json('seeking')->nullable();                     // mentorship/AI seam (D-029)
            $table->unsignedTinyInteger('years_experience')->nullable();
            $table->timestamps();
        });

        Schema::create('community_startup_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('profile_id')->unique();
            $table->unsignedBigInteger('startup_id')->nullable();   // link
            $table->unsignedSmallInteger('founding_year')->nullable();
            $table->unsignedTinyInteger('team_size')->nullable();
            $table->enum('stage', ['idea', 'mvp', 'growth', 'scale', 'exit'])->nullable();
            $table->string('industry', 100)->nullable();
            $table->string('business_model', 50)->nullable();
            $table->json('seeking')->nullable();
            $table->timestamps();
        });

        Schema::create('community_consultant_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('profile_id')->unique();
            $table->json('expertise_areas')->nullable();
            $table->unsignedTinyInteger('years_experience')->nullable();
            $table->json('certifications')->nullable();
            $table->json('languages')->nullable();
            $table->enum('availability', ['available', 'limited', 'unavailable'])->default('available');
            $table->json('engagement_types')->nullable();            // mentorship seam
            $table->timestamps();
        });

        Schema::create('community_trainer_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('profile_id')->unique();
            $table->unsignedBigInteger('instructor_id')->nullable(); // link (W4b-2)
            $table->json('specializations')->nullable();
            $table->json('certifications')->nullable();
            $table->json('delivery_modes')->nullable();
            $table->unsignedTinyInteger('years_experience')->nullable();
            $table->unsignedInteger('courses_count')->default(0);
            $table->timestamps();
        });

        Schema::create('community_partner_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('profile_id')->unique();
            $table->unsignedBigInteger('partner_id')->nullable();    // link (W4b-2)
            $table->string('organisation_name')->nullable();
            $table->json('partnership_types')->nullable();
            $table->json('service_areas')->nullable();
            $table->json('coverage_regions')->nullable();
            $table->timestamps();
        });

        Schema::create('community_researcher_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('profile_id')->unique();
            $table->unsignedBigInteger('author_id')->nullable();     // link (W4b-2)
            $table->string('institution')->nullable();
            $table->json('research_areas')->nullable();
            $table->string('academic_degree', 100)->nullable();
            $table->string('orcid_id', 50)->nullable();
            $table->unsignedInteger('publications_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_researcher_profiles');
        Schema::dropIfExists('community_partner_profiles');
        Schema::dropIfExists('community_trainer_profiles');
        Schema::dropIfExists('community_consultant_profiles');
        Schema::dropIfExists('community_startup_profiles');
        Schema::dropIfExists('community_founder_profiles');
    }
};
