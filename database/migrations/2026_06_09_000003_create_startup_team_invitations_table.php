<?php

/*
| Migration: create_startup_team_invitations_table  (Wave 5A / M-2)
| Founder/team invitation flow — invite a user to a startup (token + accept), rather than
| direct insert. Founder/admin invites; invitee accepts → becomes a team member.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('startup_team_invitations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('startup_id');
            $table->string('email');
            $table->enum('role', ['co_founder', 'admin', 'member'])->default('member');
            $table->string('token', 64)->unique();
            $table->enum('status', ['pending', 'accepted', 'expired'])->default('pending');
            $table->unsignedBigInteger('invited_by');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('startup_id', 'idx_startup_invites_startup');
            $table->foreign('startup_id', 'fk_startup_invites_startup')
                ->references('id')->on('startup_profiles')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('startup_team_invitations');
    }
};
