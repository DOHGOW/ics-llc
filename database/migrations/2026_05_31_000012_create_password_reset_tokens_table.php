<?php

/*
|--------------------------------------------------------------------------
| Migration: create_password_reset_tokens_table   (Task T-4, D-041 / F-3)
|--------------------------------------------------------------------------
| Purpose:       Password recovery architecture — backs the Laravel password
|                broker (forgot/reset password) for web and API auth.
| Decision IDs:  D-041 (blueprint amendment), D-006, D-039.
| Security:      `token` stores a HASHED reset token (never plaintext). One
|                active token per email (PK on email). Expiry computed from
|                `created_at` (auth.passwords.users.expire); throttling via
|                auth.passwords.users.throttle. No enumeration in the flow.
| Dependencies:  Logically core_users (no FK — broker keys by email).
| Extension pts: Broker config controls expiry/throttle without schema change.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
    }
};
