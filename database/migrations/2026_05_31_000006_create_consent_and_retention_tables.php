<?php

/*
|--------------------------------------------------------------------------
| Migration: create_consent_and_retention_tables   (Task T-3.6)
|--------------------------------------------------------------------------
| Purpose:       NDPA/GDPR foundations (D-006):
|                - core_consent_logs: ledger of user consents (lawful basis).
|                - core_retention_policies: configurable retention rules that
|                  drive automated, policy-based purging of data.
| Decision IDs:  D-006 (NDPA, GDPR-ready), D-039 (data protection).
| Security:      Consent records capture consent_type, policy_version,
|                timestamp and IP — supporting audit, withdrawal and proof of
|                lawful basis. Retention policies govern PII lifecycle; erasure
|                is performed by anonymisation (app layer, T-4.7), not raw
|                deletion that would break the audit trail.
| Dependencies:  core_consent_logs.user_id -> core_users (cascade on delete).
| Extension pts: `consent_type` extensible (registration/marketing/processing);
|                withdrawal workflow (T-4.7); scheduled purge command consuming
|                core_retention_policies (later sprint, flag-gated heavy job).
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_consent_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('core_users')->cascadeOnDelete();
            $table->string('consent_type', 100);
            $table->string('policy_version', 20);
            $table->timestamp('consented_at');
            $table->timestamp('withdrawn_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('consent_type', 'idx_consent_type');
        });

        Schema::create('core_retention_policies', function (Blueprint $table) {
            $table->id();
            $table->string('module', 50);
            $table->string('record_type', 100);
            $table->integer('retention_days');
            $table->boolean('auto_purge')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['module', 'record_type'], 'uk_retention_module_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_retention_policies');
        Schema::dropIfExists('core_consent_logs');
    }
};
