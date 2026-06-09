<?php

namespace App\Billing\Gateways;

/**
 * Payment gateway contract (D-037 config-only driver). Implementations are swapped by config
 * (ics.billing.gateway) with no code change. The substrate depends on this abstraction, not on
 * Paystack directly — so Flutterwave/Stripe drop in later.
 */
interface PaymentGateway
{
    /** Verify the inbound webhook signature BEFORE any processing (D-084 / Test B). */
    public function verifySignature(string $rawPayload, ?string $signature): bool;

    /** Extract a stable, unique event id from the payload (idempotency key, Test A). */
    public function eventId(array $payload): ?string;

    /** Normalised event type (e.g. charge.success, subscription.disable, refund.processed). */
    public function eventType(array $payload): string;

    /** Initialise a checkout/transaction; returns ['reference' => ..., 'authorization_url' => ...]. */
    public function initializeTransaction(int $amountKobo, string $email, array $metadata = []): array;
}
