<?php

namespace App\Services\Startup;

use App\Events\Startup\OwnershipChanged;
use App\Models\Core\User;
use App\Models\Startup\Startup;
use App\Models\Startup\TeamMember;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Gated ownership (cap-table) governance for Wave 5A (C-1 / D-064). This holds ONLY the minimal
 * founder/co-founder governance ownership_percent — the FULL cap table / valuation / fundraising
 * / investor docs are Investment Network (Wave 5d) data-room data (system of record).
 *
 * D-064 validation: totals must not exceed 100% (an unallocated remainder is allowed); no
 * negative ownership. Changes fire OwnershipChanged (HIGH audit; amounts not recorded, C-1).
 */
class OwnershipService
{
    /**
     * Set ownership percentages for a startup's members. $allocations = [team_member_id => percent].
     */
    public function setOwnership(Startup $startup, array $allocations, User $actor): void
    {
        $total = 0.0;
        foreach ($allocations as $percent) {
            if ($percent < 0) {
                throw ValidationException::withMessages(['ownership' => __('Ownership cannot be negative.')]);
            }
            $total += (float) $percent;
        }
        if ($total > 100.0 + 1e-9) {
            throw ValidationException::withMessages(['ownership' => __('Ownership totals cannot exceed 100%.')]);
        }

        DB::transaction(function () use ($startup, $allocations): void {
            foreach ($allocations as $memberId => $percent) {
                TeamMember::where('startup_id', $startup->id)->whereKey($memberId)
                    ->update(['ownership_percent' => $percent]);
            }
        });

        event(new OwnershipChanged($startup, $actor->id, $actor->getRoleNames()->first()));
    }
}
