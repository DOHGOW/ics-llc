<?php

namespace App\Models\Partner;

use Illuminate\Database\Eloquent\Model;

/**
 * Partner tier (partner_tiers). Reference data — NOT org-owned. Drives commission_rate
 * (D-031) and tier progression (min_referrals).
 */
class PartnerTier extends Model
{
    protected $table = 'partner_tiers';

    protected $fillable = [
        'name', 'slug', 'benefits', 'min_referrals', 'commission_rate', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'benefits' => 'array',
            'commission_rate' => 'decimal:2',
        ];
    }
}
