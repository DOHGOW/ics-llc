<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Services\Billing\WebhookProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PUBLIC gateway webhook endpoint (Wave Billing / D-084). NO auth — the gateway calls it. Security
 * is the SIGNATURE (verified first inside the processor), then idempotency + replay safety. Always
 * returns 200 quickly (gateways retry on non-2xx); the processor records + dedupes every delivery.
 */
class WebhookController extends Controller
{
    public function __construct(private readonly WebhookProcessor $processor) {}

    public function handle(Request $request, string $gateway): JsonResponse
    {
        $raw = $request->getContent();
        // Paystack sends x-paystack-signature; other gateways use their own header.
        $signature = $request->header('x-paystack-signature')
            ?? $request->header('verif-hash')
            ?? $request->header('stripe-signature');

        $result = $this->processor->process($gateway, $raw, $signature);

        return response()->json(['status' => $result]);
    }
}
