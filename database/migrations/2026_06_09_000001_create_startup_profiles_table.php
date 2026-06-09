<?php

/*
| Migration: create_startup_profiles_table  (Wave 5A / D-061 / D-063)
| FOUNDER-OWNED (founder_id) — NOT account-owned (H-3): no account_id, no AccountScope.
| D-063 lifecycle reconciliation: `lifecycle_stage` is the AUTHORITATIVE journey axis;
| `stage` is product maturity only; `status` is admin/moderation state; program_type REMOVED
| (derives from startup_program_enrollments). Cap-table/ownership is NOT here (C-1 → 5d data room).
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('startup_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();   // TenantScope-ready (Franchise)
            $table->unsignedBigInteger('founder_id');               // OWNER (founder-centric, H-3)
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('industry', 100)->nullable();
            // D-063 authoritative journey axis:
            $table->enum('lifecycle_stage', ['idea', 'registered', 'validation', 'incubation', 'acceleration', 'investment_ready', 'alumni'])->default('idea');
            // distinct product-maturity axis (kept):
            $table->enum('stage', ['idea', 'mvp', 'growth', 'scale', 'exit'])->default('idea');
            // admin/moderation state (narrowed):
            $table->enum('status', ['active', 'suspended', 'inactive'])->default('active');
            $table->smallInteger('founding_year')->unsigned()->nullable();
            $table->unsignedTinyInteger('team_size')->nullable();
            $table->string('website')->nullable();
            $table->string('logo_path', 500)->nullable();
            $table->char('country_code', 2)->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('founder_id', 'idx_startup_profiles_founder');
            $table->index('lifecycle_stage', 'idx_startup_profiles_lifecycle');
            $table->index('status', 'idx_startup_profiles_status');

            $table->foreign('founder_id', 'fk_startup_profiles_founder')
                ->references('id')->on('core_users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('startup_profiles');
    }
};
