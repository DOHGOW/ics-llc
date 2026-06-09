<?php

namespace App\Policies\Client;

use App\Models\Client\ClientProject;
use App\Models\Core\User;
use App\Policies\OrgOwnedPolicy;

/**
 * Client project authorisation (Wave 2). Layer 2 of the isolation control (AccountScope is
 * Layer 1). Clients VIEW their own org's projects (read.own + sameAccount); ICS staff
 * MANAGE (manage + internal-staff bypass). Super Admin via Gate::before; default-deny.
 */
class ClientProjectPolicy extends OrgOwnedPolicy
{
    public function view(User $user, ClientProject $project): bool
    {
        return ($user->can('client.projects.read.own') || $user->can('client.projects.manage'))
            && $this->accessible($user, $project);
    }

    public function manage(User $user, ClientProject $project): bool
    {
        return $user->can('client.projects.manage') && $this->accessible($user, $project);
    }

    public function create(User $user): bool
    {
        return $user->can('client.projects.manage'); // staff create projects for an account
    }
}
