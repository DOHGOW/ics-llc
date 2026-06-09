<?php

/*
|--------------------------------------------------------------------------
| Migration: create_personal_access_tokens_table   (Task T-3.3)
|--------------------------------------------------------------------------
| Purpose:       Laravel Sanctum bearer-token store for stateless API auth
|                (D-021, D-023). Keeps Sanctum's default table name so no
|                model override is required (Task 3 = migrations only).
| Decision IDs:  D-021 (authentication), D-023 (API-first /api/v1).
| Security:      `token` stores a SHA-256 HASH of the token (Sanctum), never
|                the plaintext. `abilities` scopes each token (least privilege).
|                `expires_at` enables short-lived tokens; revocation = row
|                delete (on logout/deactivation/password change).
| Dependencies:  Polymorphic (`tokenable`) — logically core_users; no hard FK.
| Extension pts: `abilities` supports granular API scopes; future API consumers
|                (mobile/PWA, 3rd-party) reuse this table unchanged.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
