<?php

namespace App\Models\Billing;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Billing plan (billing_plans, D-031/D-084). Tenant-scoped (D-086). For module='membership' plans,
 * knowledge_tier_grant/research_tier_grant are the content-tier elevation hook (D-080) read by
 * MembershipTierResolver — ContentAccessService itself is NOT modified in the Billing wave.
 */
class BillingPlan extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'billing_plans';

    protected $fillable = [
        'tenant_id', 'name', 'slug', 'description', 'type', 'module', 'billing_period', 'price',
        'currency', 'trial_days', 'research_tier_grant', 'knowledge_tier_grant', 'features',
        'gateway_plan_id', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['features' => 'array', 'is_active' => 'boolean', 'price' => 'decimal:2'];
    }
}
