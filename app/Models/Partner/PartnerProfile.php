<?php

namespace App\Models\Partner;

use App\Models\Concerns\BelongsToAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Partner profile (partner_profiles). ORG-OWNED — account_id REQUIRED (D-055); isolated by
 * AccountScope + PartnerProfilePolicy. Approval/suspension are staff-only, audited
 * (PORTAL_MANAGEMENT; suspension HIGH, D-056).
 */
class PartnerProfile extends Model
{
    use BelongsToAccount;
    use SoftDeletes;

    protected $table = 'partner_profiles';

    public const STATUSES = ['pending', 'active', 'suspended', 'terminated'];

    protected $fillable = [
        'tenant_id', 'user_id', 'account_id', 'tier_id', 'organisation_name', 'status',
        'approved_at', 'approved_by', 'agreement_signed_at',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'agreement_signed_at' => 'datetime',
        ];
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(PartnerTier::class, 'tier_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(PartnerReferral::class, 'partner_id');
    }

    public function agreements(): HasMany
    {
        return $this->hasMany(PartnerAgreement::class, 'partner_id');
    }
}
