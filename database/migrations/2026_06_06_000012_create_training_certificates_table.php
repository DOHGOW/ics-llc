<?php

/*
| Migration: create_training_certificates_table  (Wave 4a / D-059)
| Tamper-evident credential. Beyond the original blueprint (D-059 amendment): status
| lifecycle (valid/expired/revoked/superseded), optional expires_at, revocation fields,
| reissue lineage (reissued_from_id), and verification_hash. Number = ICS-CERT-{YYYY}-{NNNNNN}.
| Public verification is minimal-disclosure (see TRAINING_CERTIFICATION_GOVERNANCE_REVIEW).
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_certificates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('enrollment_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('course_id');
            $table->string('certificate_number', 50)->unique();
            $table->timestamp('issued_at');
            $table->string('pdf_path', 500)->nullable();
            $table->string('verification_url', 500)->nullable();
            // D-059 governance:
            $table->enum('status', ['valid', 'expired', 'revoked', 'superseded'])->default('valid');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->unsignedBigInteger('revoked_by')->nullable();
            $table->text('revocation_reason')->nullable();
            $table->unsignedBigInteger('reissued_from_id')->nullable();
            $table->char('verification_hash', 64)->nullable();
            $table->timestamps();

            $table->index('user_id', 'idx_training_certs_user');
            $table->index('course_id', 'idx_training_certs_course');
            $table->index('status', 'idx_training_certs_status');
            $table->foreign('enrollment_id', 'fk_training_certs_enrollment')
                ->references('id')->on('training_enrollments')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_certificates');
    }
};
