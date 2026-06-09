<?php

namespace App\Http\Controllers\Startup;

use App\Http\Controllers\Controller;
use App\Models\Startup\Startup;
use App\Services\Startup\OwnershipService;
use App\Services\Startup\StartupAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gated ownership/cap-table governance (Wave 5A / C-1 / D-064). Access limited to founders,
 * authorized startup admins, and approved ICS staff (granted investors join in 5d). NEVER public.
 * Full cap table / valuation / fundraising / docs are Investment Network (5d) data-room data.
 */
class OwnershipController extends Controller
{
    public function __construct(
        private readonly StartupAccessService $access,
        private readonly OwnershipService $ownership,
    ) {}

    public function show(Request $request, Startup $startup): JsonResponse
    {
        abort_unless($this->access->canViewOwnership($request->user(), $startup), 403); // C-1 gate

        // Explicit gated read — ownership_percent is $hidden by default; selected here intentionally.
        $rows = $startup->teamMembers()->where('status', 'active')
            ->get(['id', 'name', 'role', 'is_founder', 'ownership_percent']);

        return response()->json(['ownership' => $rows, 'total' => round((float) $rows->sum('ownership_percent'), 2)]);
    }

    public function set(Request $request, Startup $startup): JsonResponse
    {
        abort_unless($this->access->canManage($request->user(), $startup), 403);

        $data = $request->validate([
            'allocations' => ['required', 'array'],
            'allocations.*' => ['numeric'],
        ]);

        // D-064: validated (≤100%, non-negative) inside the service; change audited HIGH.
        $this->ownership->setOwnership($startup, $data['allocations'], $request->user());

        return response()->json(['message' => __('Ownership updated.')]);
    }
}
