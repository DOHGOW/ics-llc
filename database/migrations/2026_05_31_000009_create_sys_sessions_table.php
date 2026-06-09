<?php

/*
|--------------------------------------------------------------------------
| Migration: create_sys_sessions_table         (Task T-3.7)
|--------------------------------------------------------------------------
| Purpose:       Database session store option (D-037). NOTE: the Phase 1
|                shared-hosting default is FILE sessions (M-1 / SPOF-01) to
|                spare scarce DB connections (LIM-08); this table is the
|                provisioned alternative and is inactive unless
|                SESSION_DRIVER=database is selected. VPS uses redis.
| Decision IDs:  D-037 (config-driven session driver).
| Security:      Session payload carries authentication state — sensitive.
|                `user_id` is indexed but NOT foreign-keyed (avoid cascade
|                side effects on session rows). Secure cookies enforced (T-4.6).
| Dependencies:  Logically core_users (no FK by design).
| Extension pts: Activate by setting SESSION_DRIVER=database (config-only).
| Companion cfg: If activated, config/session.php 'table' => 'sys_sessions'.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_sessions');
    }
};
