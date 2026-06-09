<?php

namespace Tests\Feature\Billing;

use App\Billing\MembershipTierResolver;
use App\Models\Billing\BillingPayment;
use App\Models\Billing\BillingPlan;
use App\Models\Billing\BillingSubscription;
use App\Models\Core\User;
use App\Services\Billing\InvoiceNumberAllocator;
use App\Services\Billing\PaymentService;
use App\Services\Billing\SubscriptionService;
use App\Services\Billing\WebhookProcessor;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Billing substrate verification (A–G). Mandatory release gate (D-084). Service-level coverage of
 * webhook idempotency/signature, immediate revocation, TenantScope isolation, invoice-sequence
 * uniqueness, duplicate-payment protection, and the Membership integration hook.
 */
class BillingSubstrateTest extends TestCase
{
    use RefreshDatabase;

    private function plan(array $overrides = []): BillingPlan
    {
        return BillingPlan::withoutGlobalScopes()->create(array_merge([
            'tenant_id' => 1, 'name' => 'Pro', 'slug' => 'pro-'.uniqid(), 'type' => 'subscription',
            'module' => 'membership', 'billing_period' => 'monthly', 'price' => 10, 'currency' => 'NGN',
            'knowledge_tier_grant' => 2,
        ], $overrides));
    }

    private function sub(int $tenant, int $userId, int $planId, string $status, string $gwId): BillingSubscription
    {
        return BillingSubscription::withoutGlobalScopes()->create([
            'tenant_id' => $tenant, 'user_id' => $userId, 'plan_id' => $planId, 'status' => $status,
            'current_period_start' => now(), 'current_period_end' => now()->addMonth(),
            'gateway_subscription_id' => $gwId,
        ]);
    }

    private function webhook(string $event, string $reference, int $amountKobo = 1000): string
    {
        return json_encode(['event' => $event, 'data' => ['reference' => $reference, 'amount' => $amountKobo, 'currency' => 'NGN']]);
    }

    /** A — webhook idempotency: duplicate delivery is a no-op. */
    public function test_a_webhook_idempotency(): void
    {
        config(['ics.billing.sandbox' => true]); // signature accepted in sandbox
        $plan = $this->plan();
        $sub = $this->sub(1, 1, $plan->id, 'past_due', 'REF-A');
        $processor = app(WebhookProcessor::class);

        $this->assertSame('processed', $processor->process('paystack', $this->webhook('charge.success', 'REF-A'), null));
        $this->assertSame('duplicate_noop', $processor->process('paystack', $this->webhook('charge.success', 'REF-A'), null));
        $this->assertSame(1, BillingPayment::withoutGlobalScopes()->where('gateway_transaction_id', 'REF-A')->count());
    }

    /** B — signature validation: invalid signature is rejected, not processed. */
    public function test_b_signature_validation(): void
    {
        config(['ics.billing.sandbox' => false, 'ics.billing.paystack.secret_key' => 'sk_test_secret']);
        $processor = app(WebhookProcessor::class);

        $result = $processor->process('paystack', $this->webhook('charge.success', 'REF-B'), 'wrong-signature');
        $this->assertSame('rejected_signature', $result);
    }

    /** C — immediate revocation: cancelling removes entitlement at once. */
    public function test_c_immediate_revocation(): void
    {
        $plan = $this->plan();
        $sub = $this->sub(1, 1, $plan->id, 'active', 'REF-C');
        $this->assertTrue($sub->isEntitling());

        app(SubscriptionService::class)->cancel($sub, 'test');
        $this->assertFalse($sub->fresh()->isEntitling());
    }

    /** D — TenantScope isolation: tenant 1 cannot see tenant 2's subscription. */
    public function test_d_tenant_isolation(): void
    {
        config(['ics.tenancy.enabled' => true]);
        $plan = $this->plan();
        $this->sub(1, 1, $plan->id, 'active', 'REF-D1');
        $this->sub(2, 2, $plan->id, 'active', 'REF-D2');

        app(TenantContext::class)->set(1);
        $ids = BillingSubscription::query()->pluck('gateway_subscription_id');
        $this->assertTrue($ids->contains('REF-D1'));
        $this->assertFalse($ids->contains('REF-D2'), 'Cross-tenant leakage in billing subscriptions');
    }

    /** E — invoice sequence uniqueness (per tenant+year). */
    public function test_e_invoice_sequence_uniqueness(): void
    {
        $alloc = app(InvoiceNumberAllocator::class);
        $numbers = collect(range(1, 5))->map(fn () => $alloc->next(1));
        $this->assertSame(5, $numbers->unique()->count());
        $this->assertStringContainsString('INV-1-', $numbers->first());
    }

    /** F — duplicate payment protection (idempotent on gateway_transaction_id). */
    public function test_f_duplicate_payment_protection(): void
    {
        $attrs = ['gateway_transaction_id' => 'TXN-F', 'user_id' => 1, 'gateway' => 'paystack', 'amount' => 10, 'status' => 'pending'];
        app(PaymentService::class)->record($attrs);
        app(PaymentService::class)->record($attrs);
        $this->assertSame(1, BillingPayment::withoutGlobalScopes()->where('gateway_transaction_id', 'TXN-F')->count());
    }

    /** G — membership integration hook: live entitlement only; revoked on cancel. */
    public function test_g_membership_hook(): void
    {
        $user = User::factory()->create(['tenant_id' => 1]);
        $plan = $this->plan(['knowledge_tier_grant' => 3]);
        $sub = $this->sub(1, $user->id, $plan->id, 'active', 'REF-G');

        $resolver = app(MembershipTierResolver::class);
        $this->assertSame(3, $resolver->grantsFor($user)['knowledge']);

        app(SubscriptionService::class)->cancel($sub, 'test');
        $this->assertNull($resolver->grantsFor($user)['knowledge'], 'Entitlement must drop immediately on cancel');
    }
}
