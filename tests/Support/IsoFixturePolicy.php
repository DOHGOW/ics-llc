<?php

namespace Tests\Support;

use App\Models\Core\User;
use App\Policies\OrgOwnedPolicy;

/**
 * Test policy demonstrating the OrgOwnedPolicy framework: a permission gate AND the
 * account/staff accessibility check (Layer 2).
 */
class IsoFixturePolicy extends OrgOwnedPolicy
{
    public function view(User $user, IsoFixture $model): bool
    {
        // Uses a real permission so default-deny + staff bypass are both exercised.
        return $user->can('client.projects.read.own') && $this->accessible($user, $model);
    }
}
