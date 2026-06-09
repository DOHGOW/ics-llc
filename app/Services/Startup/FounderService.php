<?php

namespace App\Services\Startup;

use App\Events\Startup\OwnershipTransferred;
use App\Models\Core\User;
use App\Models\Startup\OwnershipTransfer;
use App\Models\Startup\Startup;
use App\Models\Startup\TeamMember;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Founder & team governance (H-2 / D-064). A startup can NEVER become ownerless: ≥1 active
 * founder at all times; founder removal requires a prior ownership transfer; removal that would
 * orphan is blocked. Transfers are immutable + audited HIGH.
 */
class FounderService
{
    /**
     * Transfer primary ownership to another active founder. Records an immutable transfer and
     * fires OwnershipTransferred (HIGH audit). The target must already be an active founder.
     */
    public function transferOwnership(Startup $startup, int $toFounderId, User $actor, ?string $reason = null): Startup
    {
        return DB::transaction(function () use ($startup, $toFounderId, $actor, $reason): Startup {
            $target = TeamMember::where('startup_id', $startup->id)->where('user_id', $toFounderId)
                ->where('is_founder', true)->where('status', 'active')->first();
            if ($target === null) {
                throw ValidationException::withMessages(['to_founder_id' => __('Target must be an active founder of this startup.')]);
            }

            $from = $startup->founder_id;
            $startup->forceFill(['founder_id' => $toFounderId])->save();

            OwnershipTransfer::create([
                'startup_id' => $startup->id,
                'from_founder_id' => $from,
                'to_founder_id' => $toFounderId,
                'actor_id' => $actor->id,
                'reason' => $reason,
                'created_at' => now(),
            ]);

            event(new OwnershipTransferred($startup, $from, $toFounderId, $actor->id, $actor->getRoleNames()->first()));

            return $startup;
        });
    }

    /**
     * Remove a team member. BLOCKED if removing the last active founder OR the primary owner
     * without a prior transfer (orphan guard, H-2/D-064).
     */
    public function removeMember(Startup $startup, TeamMember $member): void
    {
        $isPrimaryOwner = (int) $member->user_id === (int) $startup->founder_id;
        if ($isPrimaryOwner) {
            throw ValidationException::withMessages([
                'member' => __('Transfer ownership before removing the primary founder (a startup cannot be ownerless).'),
            ]);
        }

        if ($member->is_founder) {
            $activeFounders = $startup->activeFounders()->count();
            if ($activeFounders <= 1) {
                throw ValidationException::withMessages(['member' => __('A startup must keep at least one active founder.')]);
            }
        }

        $member->forceFill(['status' => 'departed'])->save();
    }
}
