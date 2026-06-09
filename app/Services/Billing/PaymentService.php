<?php

namespace App\Services\Billing;

use App\Events\Billing\SubscriptionStateChanged;
use App\Models\Billing\BillingPayment;
use App\Models\Billing\BillingSubscription;

/**
 * Payment recording (D-084). Idempotent on gateway_transaction_id (Test F — duplicate gateway
 * transaction = no-op). Refund/chargeback drive immediate entitlement removal via SubscriptionService.
 */
class PaymentService
{
    public function __construct(private readonly SubscriptionService $subscriptions) {}

    /** Idempotent: a repeated gateway_transaction_id returns the existing payment (no double charge). */
    public function record(array $data): BillingPayment
    {
        return BillingPayment::firstOrCreate(
            ['gateway_transaction_id' => $data['gateway_transaction_id']],
            $data,
        );
    }

    public function markSuccess(BillingPayment $payment, ?BillingSubscription $sub = null): void
    {
        $payment->forceFill(['status' => 'success', 'paid_at' => now()])->save();
        if ($sub !== null && $sub->status !== 'active') {
            $this->subscriptions->activate($sub);
        }
    }

    public function markFailed(BillingPayment $payment, ?BillingSubscription $sub = null): void
    {
        $payment->forceFill(['status' => 'failed'])->save();
        if ($sub !== null && $sub->status === 'active') {
            $this->subscriptions->markPastDue($sub); // immediate entitlement removal (C-3)
        }
    }

    /** Refund/chargeback → cancel the subscription → immediate entitlement removal (HIGH audited in service). */
    public function refund(BillingPayment $payment, ?BillingSubscription $sub, bool $chargeback = false): void
    {
        $payment->forceFill(['status' => $chargeback ? 'chargeback' : 'refunded'])->save();
        if ($sub !== null) {
            $sub->forceFill(['status' => 'cancelled', 'cancelled_at' => now(),
                'cancellation_reason' => $chargeback ? 'chargeback' : 'refund'])->save();
            event(new SubscriptionStateChanged($sub, $chargeback ? 'chargeback' : 'refunded', true));
        }
    }
}
