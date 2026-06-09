<?php

namespace App\Services\Billing;

use App\Billing\Gateways\PaymentGateway;
use App\Models\Billing\BillingPayment;
use App\Models\Billing\BillingSubscription;
use App\Models\Billing\BillingWebhook;
use Illuminate\Support\Facades\DB;

/**
 * Webhook ingestion (D-084). Enforces, in order: (1) signature verification, (2) event idempotency,
 * (3) transaction boundary, (4) audit logging (via state events), (5) replay safety. A duplicate
 * delivery is a NO-OP. Webhooks are gateway-inbound (no auth/tenant) — the tenant is resolved from
 * the referenced subscription (acrossTenants) BEFORE processing (D-086). Reconciliation NEVER
 * creates entitlements unsupported by subscription state.
 */
class WebhookProcessor
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly PaymentService $payments,
        private readonly SubscriptionService $subscriptions,
    ) {}

    public function process(string $gatewayName, string $rawPayload, ?string $signature): string
    {
        // (1) Signature verification FIRST.
        $valid = $this->gateway->verifySignature($rawPayload, $signature);
        $payload = json_decode($rawPayload, true) ?: [];
        $eventId = $this->gateway->eventId($payload);
        $eventType = $this->gateway->eventType($payload);

        // (2) Idempotency — record once per (gateway, event id); duplicates short-circuit.
        $webhook = BillingWebhook::firstOrCreate(
            ['gateway' => $gatewayName, 'gateway_event_id' => $eventId],
            ['event_type' => $eventType, 'payload' => $payload, 'signature_valid' => $valid, 'created_at' => now()],
        );

        if (! $valid) {
            return 'rejected_signature';
        }
        if ($webhook->processed) {
            return 'duplicate_noop'; // (5) replay-safe
        }

        // (3) Transaction boundary.
        DB::transaction(function () use ($webhook, $eventType, $payload) {
            $this->apply($eventType, $payload);
            $webhook->forceFill(['processed' => true, 'processed_at' => now()])->save();
        });

        return 'processed';
    }

    private function apply(string $eventType, array $payload): void
    {
        $sub = $this->subscriptionFromPayload($payload); // resolves across tenants (D-086)

        match ($eventType) {
            'charge.success', 'subscription.create' => $this->onSuccess($payload, $sub),
            'invoice.payment_failed', 'charge.failed' => $sub && $this->payments->markFailed($this->paymentFromPayload($payload), $sub),
            'subscription.disable', 'subscription.not_renew' => $sub && $this->subscriptions->expire($sub),
            'refund.processed' => $sub && $this->payments->refund($this->paymentFromPayload($payload), $sub, false),
            'charge.dispute.create' => $sub && $this->payments->refund($this->paymentFromPayload($payload), $sub, true),
            default => null, // unknown event types are recorded but not acted on
        };
    }

    private function onSuccess(array $payload, ?BillingSubscription $sub): void
    {
        $payment = $this->payments->record($this->paymentAttributes($payload, $sub));
        $this->payments->markSuccess($payment, $sub);
    }

    private function subscriptionFromPayload(array $payload): ?BillingSubscription
    {
        $data = $payload['data'] ?? [];
        // Match by subscription code, else by the transaction reference (first-payment activation).
        $candidates = array_values(array_filter([
            $data['subscription']['subscription_code'] ?? null,
            $data['subscription_code'] ?? null,
            $data['reference'] ?? null,
        ]));
        if ($candidates === []) {
            return null;
        }

        // Webhooks are system-context: resolve regardless of tenant scope (D-086 reconcile).
        return BillingSubscription::acrossTenants()->whereIn('gateway_subscription_id', $candidates)->first();
    }

    private function paymentFromPayload(array $payload): BillingPayment
    {
        return $this->payments->record($this->paymentAttributes($payload, null));
    }

    private function paymentAttributes(array $payload, ?BillingSubscription $sub): array
    {
        $data = $payload['data'] ?? [];

        return [
            'gateway_transaction_id' => (string) ($data['reference'] ?? $data['id'] ?? ('unknown-'.uniqid())),
            'tenant_id' => $sub?->tenant_id,
            'user_id' => $sub?->user_id ?? 0,
            'gateway' => 'paystack',
            'amount' => isset($data['amount']) ? ((int) $data['amount']) / 100 : 0, // kobo → major
            'currency' => $data['currency'] ?? config('ics.billing.currency', 'NGN'),
            'status' => 'pending',
            'gateway_response' => $data,
        ];
    }
}
