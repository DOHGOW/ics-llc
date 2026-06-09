<?php

namespace App\Http\Controllers\Billing;

use App\Billing\Gateways\PaymentGateway;
use App\Http\Controllers\Controller;
use App\Models\Billing\BillingInvoice;
use App\Models\Billing\BillingPlan;
use App\Models\Billing\BillingSubscription;
use App\Services\Billing\InvoiceNumberAllocator;
use App\Services\Billing\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Subscribe + manage own subscriptions (Wave Billing). Entitlement is NEVER granted before payment
 * for paid plans (C-3): paid-no-trial subscriptions are created NON-entitling (past_due) until a
 * charge.success webhook activates them; free plans activate immediately; trial plans start in trial.
 */
class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
        private readonly InvoiceNumberAllocator $invoiceNumbers,
        private readonly PaymentGateway $gateway,
    ) {}

    public function subscribe(Request $request): JsonResponse
    {
        $data = $request->validate(['plan_id' => ['required', 'integer', 'exists:billing_plans,id']]);
        $plan = BillingPlan::findOrFail($data['plan_id']);
        $user = $request->user();

        $sub = $this->subscriptions->startTrialOrActive($user, $plan);

        // Free or trial → entitling now. Paid-no-trial → require payment first (C-3).
        if ($plan->price > 0 && $sub->status === 'active') {
            $init = $this->gateway->initializeTransaction((int) round($plan->price * 100), (string) $user->email, ['subscription_id' => $sub->id]);
            $sub->forceFill(['gateway_subscription_id' => $init['reference']])->save();
            $this->subscriptions->markPastDue($sub); // non-entitling until charge.success

            $invoice = BillingInvoice::create([
                'tenant_id' => $sub->tenant_id,
                'invoice_number' => $this->invoiceNumbers->next($sub->tenant_id),
                'user_id' => $user->id,
                'subscription_id' => $sub->id,
                'status' => 'issued',
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(7)->toDateString(),
                'subtotal' => $plan->price, 'total' => $plan->price, 'currency' => $plan->currency,
            ]);
            $invoice->items()->create([
                'description' => $plan->name, 'quantity' => 1, 'unit_price' => $plan->price,
                'subtotal' => $plan->price, 'module' => 'membership',
            ]);

            return response()->json([
                'subscription_id' => $sub->id,
                'status' => $sub->fresh()->status,
                'invoice_number' => $invoice->invoice_number,
                'checkout' => $init,
            ], 201);
        }

        return response()->json(['subscription_id' => $sub->id, 'status' => $sub->status], 201);
    }

    public function mine(Request $request): JsonResponse
    {
        return response()->json(
            BillingSubscription::query()->where('user_id', $request->user()->id)
                ->with('plan:id,name,module')
                ->get(['id', 'plan_id', 'status', 'current_period_end', 'trial_ends_at'])
        );
    }

    public function cancel(Request $request, BillingSubscription $subscription): JsonResponse
    {
        abort_unless((int) $subscription->user_id === (int) $request->user()->id, 403);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $this->subscriptions->cancel($subscription, $data['reason'] ?? 'user_cancelled'); // immediate revocation (C-3)

        return response()->json(['message' => __('Subscription cancelled.')]);
    }
}
