<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Partner\PartnerReferral;
use App\Services\Partner\PartnerPortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Partner referrals + commissions (Wave 2). ORG-OWNED (D-055): AccountScope auto-filters;
 * policy gates. A partner submits/reads own referrals; ICS staff qualify/convert and set
 * commission (administer). W2-3: the crm_lead link (lead_id) is NEVER serialised to a
 * partner — it is `$hidden` on the model and the select lists exclude it.
 */
class ReferralController extends Controller
{
    public function __construct(private readonly PartnerPortalService $portal) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->canAny(['partner.referrals.read.own', 'partner.referrals.read.all']), 403);

        // W2-3: lead_id intentionally NOT selected — partners never see the CRM linkage.
        return response()->json(
            PartnerReferral::query()
                ->select(['id', 'account_id', 'partner_id', 'referred_org_name', 'stage',
                    'commission_amount', 'commission_currency', 'commission_paid_at', 'created_at'])
                ->paginate(25)
        );
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('create', PartnerReferral::class), 403);

        $data = $request->validate([
            'partner_id' => ['required', 'integer', 'exists:partner_profiles,id'],
            'referred_org_name' => ['required', 'string', 'max:255'],
            'referred_contact' => ['nullable', 'string', 'max:255'],
            'referred_email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        // account_id stamped by BelongsToAccount from the partner user (D-055).
        $referral = PartnerReferral::create($data + ['stage' => 'submitted']);

        return response()->json(['id' => $referral->id], 201);
    }

    public function show(Request $request, PartnerReferral $referral): JsonResponse
    {
        abort_unless($request->user()->can('view', $referral), 403);

        return response()->json($referral); // lead_id hidden via $hidden (W2-3)
    }

    public function qualify(Request $request, PartnerReferral $referral): JsonResponse
    {
        abort_unless($request->user()->can('administer', $referral), 403); // ICS staff only
        $this->portal->qualifyReferral($referral, $request->user());

        return response()->json(['message' => __('Referral qualified.')]);
    }

    public function changeStage(Request $request, PartnerReferral $referral): JsonResponse
    {
        abort_unless($request->user()->can('administer', $referral), 403);
        $data = $request->validate(['stage' => ['required', Rule::in(PartnerReferral::STAGES)]]);

        $this->portal->changeReferralStage($referral, $data['stage'], $request->user());

        return response()->json(['message' => __('Referral stage updated.')]);
    }

    public function recordCommission(Request $request, PartnerReferral $referral): JsonResponse
    {
        abort_unless($request->user()->can('administer', $referral), 403);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
        ]);

        $this->portal->recordCommission($referral, (string) $data['amount'], $data['currency'] ?? 'NGN', $request->user());

        return response()->json(['message' => __('Commission recorded.')]);
    }

    public function payCommission(Request $request, PartnerReferral $referral): JsonResponse
    {
        abort_unless($request->user()->can('administer', $referral), 403);
        $this->portal->payCommission($referral, $request->user());

        return response()->json(['message' => __('Commission marked paid.')]);
    }
}
