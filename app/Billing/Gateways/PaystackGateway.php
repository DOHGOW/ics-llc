<?php

namespace App\Billing\Gateways;

use Illuminate\Support\Str;

/**
 * Paystack gateway (D-084 / D-083 sandbox). Webhook signature = HMAC-SHA512 of the raw body with
 * the secret key (Paystack's documented scheme). In sandbox mode the initialise call returns a
 * stub checkout reference (no live charge); the lifecycle is still driven by (test) webhooks.
 */
class PaystackGateway implements PaymentGateway
{
    public function verifySignature(string $rawPayload, ?string $signature): bool
    {
        $secret = (string) config('ics.billing.paystack.secret_key');
        if ($secret === '' || $signature === null || $signature === '') {
            // No secret configured (e.g. local) → only valid in explicit sandbox.
            return (bool) config('ics.billing.sandbox', true);
        }

        $computed = hash_hmac('sha512', $rawPayload, $secret);

        return hash_equals($computed, $signature);
    }

    public function eventId(array $payload): ?string
    {
        // Paystack events carry data.id / data.reference; prefer the most stable identifier.
        return $payload['data']['reference']
            ?? (isset($payload['data']['id']) ? (string) $payload['data']['id'] : null);
    }

    public function eventType(array $payload): string
    {
        return (string) ($payload['event'] ?? 'unknown');
    }

    public function initializeTransaction(int $amountKobo, string $email, array $metadata = []): array
    {
        // Sandbox: return a stub reference + checkout URL. Live mode would call the Paystack API.
        $reference = 'ICSPAY-'.strtoupper(Str::random(16));

        return [
            'reference' => $reference,
            'authorization_url' => config('ics.billing.sandbox', true)
                ? url('/billing/sandbox/checkout/'.$reference)
                : null,
        ];
    }
}
