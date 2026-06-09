<?php

/*
|--------------------------------------------------------------------------
| Migration: create_core_users_table          (Task T-3.2)
|--------------------------------------------------------------------------
| Purpose:       Central identity record for every platform user (D-021).
|                `tenant_id` is nullable — ICS-owned users are NULL in Phase 1.
| Decision IDs:  D-021 (auth/RBAC subject), D-004 (tenant_id), D-006 (PII),
|                D-039 (password policy, MFA), D-014 (per-user locale).
| Security:      `password` stores a bcrypt hash only (cost 12, set by the app).
|                `mfa_secret` MUST be encrypted at rest via the model's
|                encrypted cast (Task 4) — never store a raw TOTP secret.
|                `email` unique; `status` drives lockout/deactivation. PII
|                (name/email/last_login_ip) is subject to NDPA/GDPR export &
|                erasure flows (T-4.7).
| Dependencies:  core_tenants (FK, ON DELETE SET NULL).
| Extension pts: `password_reset_tokens` table is required for the reset flow
|                and is added in Task 4 (blueprint amendment). SSO columns
|                (provider/provider_id) reserved for D-021 Phase 2.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()
                ->constrained('core_tenants')->nullOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('locale', 10)->default('en');
            $table->string('timezone', 50)->default('UTC');
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            // D-047/R-1: 'pending' gates approval-required registrations (login denied
            // until approved). Default 'active' (admin-created); self-registration of
            // approval-required roles sets 'pending'.
            $table->enum('status', ['active', 'pending', 'suspended', 'deactivated'])->default('active');
            // AF-1 (D-042): mfa_secret holds an ENCRYPTED TOTP secret (model
            // `encrypted` cast). TEXT — ciphertext exceeds VARCHAR(64). No plaintext.
            $table->text('mfa_secret')->nullable();
            $table->boolean('mfa_enabled')->default(false);
            // AF-3 (D-043): MFA recovery codes stored HASHED (JSON array of bcrypt
            // hashes). No plaintext, no reversible encryption.
            $table->text('mfa_recovery_codes')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status', 'idx_core_users_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_users');
    }
};
