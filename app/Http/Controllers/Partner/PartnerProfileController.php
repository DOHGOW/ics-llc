<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Core\User;
use App\Models\Partner\PartnerProfile;
use App\Services\Partner\PartnerPortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Partner profiles (Wave 2). ORG-OWNED (D-055): AccountScope auto-filters; policy gates.
 * A partner views/updates its own; ICS staff approve/suspend (administer) — audited
 * (suspension HIGH, D-056).
 */
class PartnerProfileController extends Controller
{
    public function __construct(private readonly PartnerPortalService $portal) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->canAny(['partner.profiles.read.own', 'partner.profiles.read.all']), 403);

        return response()->json(
            PartnerProfile::query()->select(['id', 'account_id', 'organisation_name', 'status', 'tier_id'])->paginate(25)
        );
    }

    /** Onboard a partner (D-055): staff-provisioned crm_account + pending profile. */
    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('partner.profiles.create'), 403);

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:core_users,id'],
            'organisation_name' => ['required', 'string', 'max:255'],
            'tier_id' => ['nullable', 'integer', 'exists:partner_tiers,id'],
        ]);

        $profile = $this->portal->onboardPartner(
            User::findOrFail($data['user_id']),
            $data['organisation_name'],
            $data['tier_id'] ?? null,
            $request->user(),
        );

        return response()->json(['id' => $profile->id, 'account_id' => $profile->account_id], 201);
    }

    public function show(Request $request, PartnerProfile $profile): JsonResponse
    {
        abort_unless($request->user()->can('view', $profile), 403);

        return response()->json($profile->load('tier'));
    }

    public function update(Request $request, PartnerProfile $profile): JsonResponse
    {
        abort_unless($request->user()->can('update', $profile), 403);

        $profile->update($request->validate([
            'organisation_name' => ['sometimes', 'string', 'max:255'],
            'tier_id' => ['nullable', 'integer', 'exists:partner_tiers,id'],
        ]));

        return response()->json(['message' => __('Profile updated.')]);
    }

    /** Approve / suspend / terminate — ICS-staff-only governance action. */
    public function changeStatus(Request $request, PartnerProfile $profile): JsonResponse
    {
        abort_unless($request->user()->can('administer', $profile), 403);
        $data = $request->validate(['status' => ['required', Rule::in(PartnerProfile::STATUSES)]]);

        $this->portal->changeProfileStatus($profile, $data['status'], $request->user());

        return response()->json(['message' => __('Partner status updated.')]);
    }
}
