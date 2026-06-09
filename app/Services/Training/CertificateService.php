<?php

namespace App\Services\Training;

use App\Events\Training\CertificateIssued;
use App\Events\Training\CertificateRevoked;
use App\Models\Core\User;
use App\Models\Training\Certificate;
use App\Models\Training\Enrollment;
use Illuminate\Support\Facades\DB;

/**
 * Certificate governance (D-059). Numbering ICS-CERT-{YYYY}-{NNNNNN} via a race-safe
 * per-year sequence; tamper-evident verification_hash; revoke/reissue lifecycle; public
 * minimal-disclosure verification. See TRAINING_CERTIFICATION_GOVERNANCE_REVIEW.md.
 */
class CertificateService
{
    /** Idempotent issue on completion — one valid certificate per enrollment. */
    public function issueForEnrollment(Enrollment $enrollment): Certificate
    {
        $existing = Certificate::query()
            ->where('enrollment_id', $enrollment->id)
            ->whereIn('status', ['valid', 'superseded'])
            ->first();
        if ($existing !== null) {
            return $existing;
        }

        return DB::transaction(function () use ($enrollment): Certificate {
            $number = $this->allocateNumber($enrollment->tenant_id);
            $course = $enrollment->course;
            $issuedAt = now();
            $expiresAt = ($course && $course->validity_months)
                ? $issuedAt->copy()->addMonths((int) $course->validity_months)
                : null;

            $certificate = new Certificate([
                'tenant_id' => $enrollment->tenant_id,
                'enrollment_id' => $enrollment->id,
                'user_id' => $enrollment->user_id,
                'course_id' => $enrollment->course_id,
                'certificate_number' => $number,
                'issued_at' => $issuedAt,
                'status' => 'valid',
                'expires_at' => $expiresAt,
            ]);
            $certificate->verification_url = url('/api/v1/training/certificates/verify/'.$number);
            $certificate->verification_hash = $this->hash($certificate);
            $certificate->save();

            event(new CertificateIssued($certificate, auth()->id(), optional(auth()->user())->getRoleNames()->first()));

            return $certificate;
        });
    }

    public function revoke(Certificate $certificate, string $reason, User $actor): Certificate
    {
        $certificate->forceFill([
            'status' => 'revoked',
            'revoked_at' => now(),
            'revoked_by' => $actor->id,
            'revocation_reason' => $reason,
        ])->save();

        event(new CertificateRevoked($certificate, $reason, $actor->id, $actor->getRoleNames()->first()));

        return $certificate;
    }

    /** Reissue: new number, supersede the prior, keep lineage (D-059 §5). */
    public function reissue(Certificate $prior, User $actor): Certificate
    {
        abort_if($prior->status === 'revoked', 422, 'Revoked certificates cannot be reissued.');

        return DB::transaction(function () use ($prior, $actor): Certificate {
            $number = $this->allocateNumber($prior->tenant_id);

            $new = $prior->replicate(['status', 'certificate_number', 'verification_url', 'verification_hash', 'reissued_from_id']);
            $new->certificate_number = $number;
            $new->status = 'valid';
            $new->issued_at = $prior->issued_at; // credential achievement date preserved
            $new->reissued_from_id = $prior->id;
            $new->verification_url = url('/api/v1/training/certificates/verify/'.$number);
            $new->verification_hash = $this->hash($new);
            $new->save();

            $prior->forceFill(['status' => 'superseded'])->save();

            event(new CertificateIssued($new, $actor->id, $actor->getRoleNames()->first()));

            return $new;
        });
    }

    /** Public minimal-disclosure verification (D-059 §2) — no PII beyond name + course. */
    public function verify(string $number): ?array
    {
        $certificate = Certificate::query()->with(['holder:id,name', 'course:id,title'])
            ->where('certificate_number', $number)->first();
        if ($certificate === null) {
            return null;
        }

        $status = $certificate->liveStatus();
        $intact = hash_equals((string) $certificate->verification_hash, $this->hash($certificate));

        return [
            'certificate_number' => $certificate->certificate_number,
            'holder' => optional($certificate->holder)->name,
            'course' => optional($certificate->course)->title,
            'issued_at' => optional($certificate->issued_at)->toDateString(),
            'expires_at' => optional($certificate->expires_at)->toDateString(),
            'status' => $status,
            'valid' => $status === 'valid' && $intact,
        ];
    }

    private function allocateNumber(?int $tenantId): string
    {
        $year = (int) now()->year;

        $seq = DB::transaction(function () use ($tenantId, $year): int {
            $row = DB::table('training_certificate_sequences')
                ->where('tenant_id', $tenantId)->where('year', $year)->lockForUpdate()->first();

            if ($row === null) {
                DB::table('training_certificate_sequences')->insert([
                    'tenant_id' => $tenantId, 'year' => $year, 'last_sequence' => 1,
                ]);

                return 1;
            }

            $next = $row->last_sequence + 1;
            DB::table('training_certificate_sequences')
                ->where('id', $row->id)->update(['last_sequence' => $next]);

            return $next;
        });

        return sprintf('ICS-CERT-%d-%06d', $year, $seq);
    }

    /**
     * SHA-256 over the IMMUTABLE credential facts (D-059 §1). Excludes `status` deliberately
     * — status is a lifecycle field (revoke/expire/supersede) tracked separately, so the
     * integrity check stays stable across the certificate's life and detects tampering of
     * number/holder/course/issue-date.
     */
    private function hash(Certificate $certificate): string
    {
        return hash('sha256', implode('|', [
            $certificate->certificate_number,
            (int) $certificate->user_id,
            (int) $certificate->course_id,
            optional($certificate->issued_at)->toIso8601String(),
        ]));
    }
}
