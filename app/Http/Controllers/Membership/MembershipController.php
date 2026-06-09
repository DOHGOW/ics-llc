<?php

namespace App\Http\Controllers\Membership;

use App\Http\Controllers\Controller;
use App\Models\Billing\BillingPlan;
use App\Services\Membership\MembershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Member self-service (Wave Membership). Read-only projection of the caller's LIVE membership
 * entitlement + the membership plan catalogue. SUBSCRIBING reuses the Billing endpoints
 * (POST /api/v1/billing/subscribe) — Membership adds NO parallel payment path (it is a Billing
 * consumer). Tenant-scoped automatically (BillingPlan/BillingSubscription are TenantScoped).
 */
class MembershipController extends Controller
{
    public function __construct(private readonly MembershipService $memberships) {}

    /** The caller's live membership entitlement projection (content tiers granted, if any). */
    public function status(Request $request): JsonResponse
    {
        return response()->json($this->memberships->entitlementFor($request->user()));
    }

    /** Public membership plan catalogue (module='membership' only). */
    public function plans(): JsonResponse
    {
        return response()->json(
            BillingPlan::query()->where('module', 'membership')->where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'name', 'slug', 'billing_period', 'price', 'currency', 'trial_days',
                    'knowledge_tier_grant', 'research_tier_grant', 'features'])
        );
    }
}
