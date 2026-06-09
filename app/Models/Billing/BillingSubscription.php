<?php

namespace App\Models\Billing;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Billing subscription (billing_subscriptions, D-084) — THE entitlement source. Tenant-scoped
 * (D-086). Entitlement exists ONLY while status ∈ {trial, active} (D-084 immediate revocation);
 * any other status grants NOTHING. MembershipTierResolver reads this live (no cached grant, C-3).
 */
class BillingSubscription extends Model
{
    use BelongsToTenant;

    protected $table = 'billing_subscriptions';

    /** Statuses that grant entitlement (D-084). */
    public const ENTITLING_STATUSES = ['trial', 'active'];

    public const STATUSES = ['trial', 'active', 'past_due', 'cancelled', 'expired'];

    protected $fillable = [
        'tenant_id', 'user_id', 'plan_id', 'status', 'quantity', 'trial_ends_at', 'current_period_start',
        'current_period_end', 'cancelled_at', 'cancellation_reason', 'ends_at', 'gateway_subscription_id',
        'gateway_customer_id', 'gateway_email_token', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime', 'current_period_start' => 'datetime',
            'current_period_end' => 'datetime', 'cancelled_at' => 'datetime', 'ends_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(BillingPlan::class, 'plan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** D-084/C-3: entitlement is LIVE — granted ONLY while trial/active (period not lapsed). */
    public function isEntitling(): bool
    {
        if (! in_array($this->status, self::ENTITLING_STATUSES, true)) {
            return false;
        }

        return $this->current_period_end === null || $this->current_period_end->isFuture()
            || $this->status === 'trial';
    }
}
