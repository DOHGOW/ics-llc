<?php

namespace App\Http\Controllers\Membership\Admin;

use App\Audit\AuditCategory;
use App\Audit\AuditSensitivity;
use App\Authorization\Roles;
use App\Http\Controllers\Controller;
use App\Models\Billing\BillingPlan;
use App\Models\Billing\BillingSubscription;
use App\Models\Core\User;
use App\Services\Audit\AuditService;
use App\Services\Membership\MembershipAnalyticsService;
use App\Services\Membership\MembershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Membership administration (Wave Membership, D-087 items 3/5/6/7). Tenant-aware: Platform/Super Admin
 * (HQ) and Franchise Admin (own tenant) manage membership plans + manual entitlement. TenantScope
 * isolates a franchise admin to their own tenant's plans/subscriptions automatically (C-4).
 *
 * All actions are MEMBERSHIP_MANAGEMENT audited (D-081). Tenant-wide policy changes (plan tier grants)
 * and MANUAL entitlement grant/removal are HIGH-sensitivity. Membership may grant CONTENT tiers ONLY —
 * the tier-grant inputs are validated/capped to ics.membership.max_grant_tier (never internal/super, C-2).
 */
class MembershipAdminController extends Controller
{
    public function __construct(
        private readonly MembershipService $memberships,
        private readonly MembershipAnalyticsService $analytics,
        private readonly AuditService $audit,
    ) {}

    private function authorizeAdmin(Request $request): void
    {
        abort_unless(
            $request->user()->hasAnyRole([Roles::SUPER_ADMIN, Roles::PLATFORM_ADMIN, Roles::FRANCHISE_ADMIN]),
            403
        );
    }

    private function tierGrantRules(): array
    {
        $max = (int) config('ics.membership.max_grant_tier', 3);

        // C-2: membership elevates CONTENT tiers ONLY and never internal(4)/super(5) — capped here.
        return ['nullable', 'integer', 'min:1', 'max:'.$max];
    }

    /** Create a membership plan (module forced to 'membership'). Tenant-wide policy change → HIGH. */
    public function storePlan(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string'],
            'billing_period' => ['required', Rule::in(['monthly', 'quarterly', 'annual'])],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'knowledge_tier_grant' => $this->tierGrantRules(),
            'research_tier_grant' => $this->tierGrantRules(),
            'features' => ['nullable', 'array'],
            'gateway_plan_id' => ['nullable', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $data['module'] = 'membership'; // forced — this controller manages membership plans only
        $data['type'] = 'subscription'; // billing_plans.type enum = subscription|one_time
        $plan = BillingPlan::create($data);

        $this->auditPlan($request, 'MEMBERSHIP_PLAN_CREATED', $plan);

        return response()->json(['id' => $plan->id, 'message' => __('Membership plan created.')], 201);
    }

    /** Update a membership plan. Tenant-wide policy change → HIGH. TenantScope guards cross-tenant edits. */
    public function updatePlan(Request $request, BillingPlan $plan): JsonResponse
    {
        $this->authorizeAdmin($request);
        abort_unless($plan->module === 'membership', 404);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'knowledge_tier_grant' => $this->tierGrantRules(),
            'research_tier_grant' => $this->tierGrantRules(),
            'features' => ['nullable', 'array'],
            'gateway_plan_id' => ['nullable', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $plan->fill($data)->save();

        $this->auditPlan($request, 'MEMBERSHIP_PLAN_UPDATED', $plan);

        return response()->json(['message' => __('Membership plan updated.')]);
    }

    /** MANUAL entitlement grant (comp / migration) — D-081 HIGH (handled in the membership event). */
    public function grant(Request $request, User $user): JsonResponse
    {
        $this->authorizeAdmin($request);
        $data = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:billing_plans,id'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $plan = BillingPlan::findOrFail($data['plan_id']);
        $sub = $this->memberships->grantManual($user, $plan, $request->user(), $data['reason'] ?? null);

        return response()->json(['subscription_id' => $sub->id, 'message' => __('Membership granted.')], 201);
    }

    /** MANUAL entitlement removal — D-081 HIGH. Immediate revocation (C-3). */
    public function revoke(Request $request, BillingSubscription $subscription): JsonResponse
    {
        $this->authorizeAdmin($request);
        abort_unless($subscription->plan?->module === 'membership', 404);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $this->memberships->revokeManual($subscription, $request->user(), $data['reason'] ?? null);

        return response()->json(['message' => __('Membership revoked.')]);
    }

    /** Per-tenant membership analytics (financial aggregates only — no PII). */
    public function analytics(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        return response()->json($this->analytics->summary());
    }

    private function auditPlan(Request $request, string $action, BillingPlan $plan): void
    {
        // Tenant-wide membership POLICY change (tier grants) is HIGH-sensitivity (D-081).
        $this->audit->log(
            $action, 'membership', AuditCategory::MEMBERSHIP_MANAGEMENT,
            $request->user()->id, $request->user()->getRoleNames()->first(),
            BillingPlan::class, $plan->id,
            null, ['knowledge_tier_grant' => $plan->knowledge_tier_grant, 'research_tier_grant' => $plan->research_tier_grant],
            $request->ip(), null, $plan->tenant_id, AuditSensitivity::HIGH
        );
    }
}
