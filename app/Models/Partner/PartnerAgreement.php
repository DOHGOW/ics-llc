<?php

namespace App\Models\Partner;

use App\Models\Concerns\BelongsToAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Partner agreement (partner_agreements). ORG-OWNED — account_id REQUIRED (D-055);
 * isolated by AccountScope. Files are policy-gated/signed (W2-5). Agreement events are
 * audited HIGH-sensitivity (D-056).
 */
class PartnerAgreement extends Model
{
    use BelongsToAccount;
    use SoftDeletes;

    protected $table = 'partner_agreements';

    protected $fillable = [
        'tenant_id', 'account_id', 'partner_id', 'title', 'type', 'effective_date',
        'expiry_date', 'signed_at', 'file_path',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'expiry_date' => 'date',
            'signed_at' => 'datetime',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(PartnerProfile::class, 'partner_id');
    }
}
