<?php

namespace App\Models\Partner;

use App\Models\Concerns\BelongsToAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Partner referral (partner_referrals). ORG-OWNED — account_id REQUIRED (D-055);
 * isolated by AccountScope + PartnerReferralPolicy.
 *
 * W2-3 BOUNDARY: `lead_id` links to the internal crm_lead but is ICS-ONLY. It is hidden
 * here ($hidden) and excluded from every partner-facing serialiser — the partner sees
 * referral stage + commission, NEVER the CRM lead, assignment, or workflow.
 */
class PartnerReferral extends Model
{
    use BelongsToAccount;
    use SoftDeletes;

    protected $table = 'partner_referrals';

    public const STAGES = ['submitted', 'qualified', 'converted', 'lost'];

    protected $fillable = [
        'tenant_id', 'account_id', 'partner_id', 'referred_org_name', 'referred_contact',
        'referred_email', 'stage', 'commission_amount', 'commission_currency',
        'commission_paid_at', 'notes',
    ];

    /** W2-3 resource layer: lead_id never leaves the server in a partner response. */
    protected $hidden = ['lead_id'];

    protected function casts(): array
    {
        return [
            'commission_amount' => 'decimal:2',
            'commission_paid_at' => 'datetime',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(PartnerProfile::class, 'partner_id');
    }
}
