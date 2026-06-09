<?php

/*
|--------------------------------------------------------------------------
| Migration: create_core_audit_logs_table      (Task T-3.5)
|--------------------------------------------------------------------------
| Purpose:       Immutable, append-only audit trail of security- and data-
|                sensitive actions across the platform (D-006, D-039).
| Decision IDs:  D-006 (compliance/audit), D-039 (SEC-03 immutability).
| Security:      APPEND-ONLY by design — only `created_at`, no updated_at/
|                deleted_at. Stores SHA-256 `before_hash`/`after_hash`, NOT raw
|                record data, so sensitive payloads are never copied here.
|                Intentionally has NO foreign keys: the trail must survive
|                deletion of the actor/tenant it references. Immutability is
|                enforced at the application layer (write-only repository,
|                T-6.1) plus periodic off-box export (T-6.2); a DB trigger MAY
|                be added if TRIGGER privilege is confirmed (Quicksheet C8).
|                `actor_id` nullable for system-originated actions.
| Dependencies:  Logically references core_users/core_tenants (no FK by design).
| Extension pts: Optional hard-immutability trigger; partition by `created_at`
|                at scale (VPS/cloud); retention via core_retention_policies
|                (anonymise, never hard-delete the trail).
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_role', 100)->nullable();
            $table->string('action', 50);
            $table->string('module', 50);
            // D-046: audit categorisation + sensitivity (Super Admin actions = high).
            $table->string('category', 50)->default('general');
            $table->string('sensitivity', 10)->default('normal'); // normal | high
            $table->string('record_type', 100)->nullable();
            $table->unsignedBigInteger('record_id')->nullable();
            $table->char('before_hash', 64)->nullable();
            $table->char('after_hash', 64)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('tenant_id', 'idx_audit_tenant');
            $table->index('actor_id', 'idx_audit_actor');
            $table->index('module', 'idx_audit_module');
            $table->index('category', 'idx_audit_category');
            $table->index('sensitivity', 'idx_audit_sensitivity');
            $table->index('created_at', 'idx_audit_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_audit_logs');
    }
};
