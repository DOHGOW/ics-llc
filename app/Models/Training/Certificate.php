<?php

namespace App\Models\Training;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Certificate (training_certificates) — tamper-evident credential (D-059). Lifecycle:
 * valid → expired/revoked/superseded. Public verification is minimal-disclosure
 * (CertificateService::verify). verification_hash detects tampering.
 */
class Certificate extends Model
{
    protected $table = 'training_certificates';

    public const STATUSES = ['valid', 'expired', 'revoked', 'superseded'];

    protected $fillable = [
        'tenant_id', 'enrollment_id', 'user_id', 'course_id', 'certificate_number', 'issued_at',
        'pdf_path', 'verification_url', 'status', 'expires_at', 'revoked_at', 'revoked_by',
        'revocation_reason', 'reissued_from_id', 'verification_hash',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(TrainingCourse::class, 'course_id');
    }

    public function holder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Live status — reflects expiry even if the stored flag lags (D-059 §4). */
    public function liveStatus(): string
    {
        if ($this->status === 'valid' && $this->expires_at !== null && $this->expires_at->isPast()) {
            return 'expired';
        }

        return $this->status;
    }
}
