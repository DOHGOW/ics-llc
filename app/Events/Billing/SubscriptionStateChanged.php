<?php

namespace App\Events\Billing;

use App\Models\Billing\BillingSubscription;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Billing governance event (D-085 BILLING_MANAGEMENT). `high` is set by the caller for the
 * HIGH-sensitivity actions (manual override, refund, chargeback, admin cancel/reactivate, invoice
 * adjustment, reconciliation override). Routine system transitions are normal sensitivity.
 */
class SubscriptionStateChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public BillingSubscription $subscription,
        public string $action,
        public bool $high = false,
        public ?int $actorId = null,
        public ?string $actorRole = null,
    ) {}
}
