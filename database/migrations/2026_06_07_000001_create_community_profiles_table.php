<?php

/*
| Migration: create_community_profiles_table  (Wave 4b / D-035 / D-057)
| CTI base. One per user (user_id UNIQUE) → owner-scoped. VISIBILITY-scoped (public/
| authenticated) + status moderation — NOT ContentAccessible (D-057). FULLTEXT for the
| public directory (W4b search). is_verified is a staff governance flag.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('user_id')->unique();
            $table->enum('profile_type', ['founder', 'startup', 'consultant', 'trainer', 'partner', 'researcher']);
            $table->string('display_name');
            $table->string('tagline', 120)->nullable();
            $table->text('bio')->nullable();
            $table->string('avatar_path', 500)->nullable();
            $table->string('cover_image_path', 500)->nullable();
            $table->string('website_url')->nullable();
            $table->char('location_country', 2)->nullable();
            $table->string('location_city', 100)->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->enum('visibility', ['public', 'authenticated'])->default('public');
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('follower_count')->default(0);
            $table->enum('status', ['active', 'suspended', 'hidden'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index('profile_type', 'idx_community_profiles_type');
            $table->index('location_country', 'idx_community_profiles_country');
            $table->index('is_verified', 'idx_community_profiles_verified');
            $table->index('status', 'idx_community_profiles_status');

            $table->foreign('user_id', 'fk_community_profiles_user')
                ->references('id')->on('core_users')->cascadeOnDelete();

            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->fullText(['display_name', 'tagline', 'bio'], 'ft_community_profiles');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_profiles');
    }
};
