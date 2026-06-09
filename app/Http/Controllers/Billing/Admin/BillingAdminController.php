<?php

namespace App\Http\Controllers\Billing\Admin;

use App\Authorization\Roles;
use App\Http\Controllers\Controller;
use App\Models\Billing\BillingPayment;
use App\Models\Billing\BillingSubscription;
use App\Services\Billing\PaymentService;
use App\Services\Billing\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * HQ billing administration (Wave Billing / D-085). Override/refund/reactivate are HIGH-audited
 * governance actions (BILLING_MANAGEMENT). Gated to Platform/Super Admin.
 */
class BillingAdminController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
        private readonly PaymentService $payments,
    ) {}

    private function authorizeHq(Request $request): void
    {
        abort_unless($request->user()->hasAnyRole([Roles::SUPER_ADMIN, Roles::PLATFORM_ADMIN]), 403);
    }

    public function override(Request $request, BillingSubscription $subscription): JsonResponse
    {
        $this->authorizeHq($request);
        $data = $request->validate(['status' => ['required', Rule::in(BillingSubscription::STATUSES)]]);
        $this->subscriptions->override($subscription, $data['status'], $request->user());

        return response()->json(['message' => __('Subscription overridden.')]);
    }

    public function reactivate(Request $request, BillingSubscription $subscription): JsonResponse
    {
        $this->authorizeHq($request);
        $this->subscriptions->reactivate($subscription, $request->user());

        return response()->json(['message' => __('Subscription reactivated.')]);
    }

    public function adminCancel(Request $request, BillingSubscription $subscription): JsonResponse
    {
        $this->authorizeHq($request);
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $this->subscriptions->cancel($subscription, $data['reason'], $request->user()); // HIGH

        return response()->json(['message' => __('Subscription cancelled (admin).')]);
    }

    public function refund(Request $request, BillingPayment $payment): JsonResponse
    {
        $this->authorizeHq($request);
        $data = $request->validate(['chargeback' => ['nullable', 'boolean']]);

        // Resolve the subscription via the payment's invoice (if any).
        $sub = $payment->invoice_id
            ? BillingSubscription::acrossTenants()->whereIn('id', function ($q) use ($payment) {
                $q->select('subscription_id')->from('billing_invoices')->where('id', $payment->invoice_id);
            })->first()
            : null;

        $this->payments->refund($payment, $sub, (bool) ($data['chargeback'] ?? false)); // HIGH + immediate revocation

        return response()->json(['message' => __('Payment refunded.')]);
    }
}
