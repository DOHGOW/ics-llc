<?php

namespace App\Providers;

use App\Authorization\Roles;
use App\Models\Client\ClientProject;
use App\Models\Client\Ticket;
use App\Models\Core\User;
use App\Models\Partner\PartnerAgreement;
use App\Models\Partner\PartnerProfile;
use App\Models\Partner\PartnerReferral;
use App\Policies\Client\ClientProjectPolicy;
use App\Policies\Client\TicketPolicy;
use App\Policies\Partner\PartnerAgreementPolicy;
use App\Policies\Partner\PartnerProfilePolicy;
use App\Policies\Partner\PartnerReferralPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Authorization wiring (D-021 / D-044 / R-4).
 *
 * Register in bootstrap/providers.php.
 *
 * Default-deny: Laravel Gates deny unless a Policy/Gate explicitly allows, and
 * Spatie permission checks ($user->can('module.resource.action')) grant only what
 * the role→permission map assigns. Gate::before grants ALL only to Super Admin;
 * for every other user it returns null and control falls through to the policies
 * and the permission map (no implicit grants).
 */
class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::before(function (User $user, string $ability) {
            return $user->hasRole(Roles::SUPER_ADMIN) ? true : null;
        });

        Gate::policy(User::class, UserPolicy::class);

        // Wave 2 portal policies (org-owned, OrgOwnedPolicy — Layer 2 of isolation).
        Gate::policy(ClientProject::class, ClientProjectPolicy::class);
        Gate::policy(Ticket::class, TicketPolicy::class);
        Gate::policy(PartnerProfile::class, PartnerProfilePolicy::class);
        Gate::policy(PartnerReferral::class, PartnerReferralPolicy::class);
        Gate::policy(PartnerAgreement::class, PartnerAgreementPolicy::class);

        // Module model policies (CRM, Client, Partner, Startup, …) are registered
        // in their module sprints, extending BasePolicy and enforcing org/owner
        // scoping — the sole Phase 1 isolation control (AUTHORIZATION_SECURITY_AUDIT
        // R-3; TenantScope deferred to Phase 3, D-037).
    }
}
