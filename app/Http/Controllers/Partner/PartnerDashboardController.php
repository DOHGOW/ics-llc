<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Partner\PartnerReferral;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Partner dashboard (Wave 2). Account-scoped summary for the signed-in partner — referral
 * funnel + commission totals for THEIR org only. AccountScope auto-restricts every query to
 * the caller's account (a partner sees only their own numbers). W2-3: no CRM data exposed.
 */
class PartnerDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('partner.reports.view'), 403);

        // AccountScope restricts these to the partner's own account automatically.
        $funnel = PartnerReferral::query()
            ->groupBy('stage')->select('stage', DB::raw('COUNT(*) as total'))
            ->pluck('total', 'stage')->all();

        return response()->json([
            'referral_funnel' => $funnel,
            'commission_earned' => (float) PartnerReferral::query()->whereNotNull('commission_paid_at')->sum('commission_amount'),
            'commission_pending' => (float) PartnerReferral::query()
                ->whereNull('commission_paid_at')->whereNotNull('commission_amount')->sum('commission_amount'),
        ]);
    }
}
