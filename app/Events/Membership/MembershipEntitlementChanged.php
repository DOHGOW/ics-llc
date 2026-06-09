<?php

namespace App\Events\Membership;

use App\Models\Billing\BillingSubscription;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Membership governance event (D-081 MEMBERSHIP_MANAGEMENT). Fired for MANUAL entitlement changes —
 * an admin granting a comp membership or removing one — which are HIGH-sensitivity (manual grant/
 * removal). Routine, payment-driven membership lifecycle is already audited via the Billing layer
 * (SubscriptionStateChanged / BILLING_MANAGEMENT); this event is the membership-specific overlay for
 * manual administrative entitlement actions, so they are NOT lost in the billing stream.
 */
class MembershipEntitlementChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public BillingSubscription $subscription,
        public string $action, // 'granted' | 'revoked'
        public bool $high = true,
        public ?int $actorId = null,
        public ?string $actorRole = null,
    ) {}
}
