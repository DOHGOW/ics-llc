<?php

namespace App\Services\Startup;

use App\Events\Startup\StartupStatusChanged;
use App\Models\Core\User;
use App\Models\Startup\Startup;

/**
 * Startup governance lifecycle (D-062/D-064). Verification, suspension/reactivation, and
 * graduation to alumni — staff-driven, audited (verify/suspend/reactivate HIGH; graduate normal).
 * lifecycle_stage is the authoritative journey (D-063); transitions are explicit.
 */
class StartupGovernanceService
{
    public function verify(Startup $startup, User $actor): Startup
    {
        $startup->forceFill(['is_verified' => true, 'verified_at' => now(), 'verified_by' => $actor->id])->save();
        event(new StartupStatusChanged($startup, 'verified', $actor->id, $actor->getRoleNames()->first()));

        return $startup;
    }

    public function suspend(Startup $startup, User $actor): Startup
    {
        $startup->forceFill(['status' => 'suspended'])->save();
        event(new StartupStatusChanged($startup, 'suspended', $actor->id, $actor->getRoleNames()->first()));

        return $startup;
    }

    public function reactivate(Startup $startup, User $actor): Startup
    {
        $startup->forceFill(['status' => 'active'])->save();
        event(new StartupStatusChanged($startup, 'reactivated', $actor->id, $actor->getRoleNames()->first()));

        return $startup;
    }

    public function graduateToAlumni(Startup $startup, User $actor): Startup
    {
        $startup->forceFill(['lifecycle_stage' => 'alumni'])->save();
        event(new StartupStatusChanged($startup, 'graduated', $actor->id, $actor->getRoleNames()->first()));

        return $startup;
    }

    /** Explicit lifecycle transition (D-063). */
    public function setLifecycleStage(Startup $startup, string $stage): Startup
    {
        $startup->forceFill(['lifecycle_stage' => $stage])->save();

        return $startup;
    }
}
