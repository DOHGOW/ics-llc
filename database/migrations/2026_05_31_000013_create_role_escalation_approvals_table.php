<?php

/*
|--------------------------------------------------------------------------
| Migration: create_role_escalation_approvals_table   (Task T-5, D-045)
|--------------------------------------------------------------------------
| Purpose:       Single-purpose four-eyes approval record for Super Admin role
|                escalation (D-044). NOT a generic workflow engine — it stores
|                ONLY role-escalation approvals.
| Decision IDs:  D-044 (escalation guard, four-eyes), D-045 (this amendment),
|                D-006/D-039 (audit).
| Security:      Captures requester, approver, target, requested/previous role,
|                reason code, status, IPs, timestamps. A request is DECIDED ONCE
|                (pending → approved/rejected/expired). Every transition is also
|                mirrored to the immutable core_audit_logs — that is the immutable
|                audit trail; this table is the lightweight request record.
| Dependencies:  core_users (requester/target/approver).
| Extension pts: `requested_role`/`reason_code` are strings — the same table could
|                back other escalations in future WITHOUT schema change, but only
|                Super Admin escalation is wired now (scope-limited by design).
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_role_escalation_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('core_users');
            $table->foreignId('target_user_id')->constrained('core_users');
            $table->foreignId('approver_id')->nullable()->constrained('core_users');
            $table->string('requested_role');
            $table->string('previous_role')->nullable();
            $table->string('reason_code', 50);
            $table->string('status', 20)->default('pending'); // pending|approved|rejected|expired
            $table->string('requester_ip', 45)->nullable();
            $table->string('approver_ip', 45)->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('status', 'idx_escalation_status');
            $table->index('target_user_id', 'idx_escalation_target');
            $table->index('requester_id', 'idx_escalation_requester');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_role_escalation_approvals');
    }
};
