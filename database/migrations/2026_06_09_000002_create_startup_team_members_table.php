<?php

/*
| Migration: create_startup_team_members_table  (Wave 5A / D-064 / M-4)
| Team membership = the participation key (D-061). `ownership_percent` is the MINIMAL gated
| governance representation (C-1): access-restricted to founders/admins/staff/granted-investors;
| EXCLUDED from every public/community/marketplace/analytics projection. D-064: totals = 100%,
| non-negative; founder ownership changes audited HIGH; ≥1 active founder always.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('startup_team_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('startup_id');
            $table->unsignedBigInteger('user_id')->nullable(); // NULL = unregistered member
            $table->string('name');
            $table->enum('role', ['founder', 'co_founder', 'admin', 'member'])->default('member'); // M-4
            $table->string('email')->nullable();
            $table->boolean('is_founder')->default(false);
            $table->decimal('ownership_percent', 5, 2)->nullable(); // GATED governance data (C-1)
            $table->enum('status', ['active', 'departed'])->default('active');
            $table->timestamps();

            $table->index('startup_id', 'idx_startup_members_startup');
            $table->index('user_id', 'idx_startup_members_user');

            $table->foreign('startup_id', 'fk_startup_members_startup')
                ->references('id')->on('startup_profiles')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('startup_team_members');
    }
};
