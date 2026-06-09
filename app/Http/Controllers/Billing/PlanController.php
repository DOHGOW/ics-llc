<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Billing\BillingPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Public plan catalogue (Wave Billing). Tenant-scoped automatically (BelongsToTenant). */
class PlanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            BillingPlan::query()->where('is_active', true)
                ->when($request->filled('module'), fn ($q) => $q->where('module', $request->string('module')))
                ->orderBy('sort_order')
                ->get(['id', 'name', 'slug', 'module', 'billing_period', 'price', 'currency', 'trial_days', 'features'])
        );
    }
}
