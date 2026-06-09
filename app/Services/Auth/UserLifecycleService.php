<?php

namespace App\Services\Auth;

use App\Authorization\Roles;
use App\Authorization\SuperAdminGuard;
use App\Events\Core\AccountApproved;
use App\Events\Core\AccountDeactivated;
use App\Events\Core\AccountReactivated;
use App\Events\Core\AccountSuspended;
use App\Events\Core\RoleRevoked;
use App\Models\Core\User;

/**
 * User lifecycle transitions (Task 7 / D-047). Every transition is guarded, then
 * dispatches a lifecycle event — the AuditEventSubscriber records the immutable
 * audit (D-046) and the SecurityAlertSubscriber raises alerts (R-7).
 *
 * Invariants:
 *  - No actor may act on their own account for suspend/deactivate/delete (self-action).
 *  - The last active Super Admin cannot be suspended/deactivated/deleted (R-3).
 *  - Reactivation never restores the Super Admin role — it must be re-granted via
 *    the four-eyes flow (R-4).
 *  - Pending users cannot authenticate (enforced in AuthController: status==='active').
 */
class UserLifecycleService
{
    /** pending → active (approval workflow). */
    public function approve(User $admin, User $user, string $ip): void
    {
        if ($user->status !== 'pending') {
            throw new \DomainException('Only a pending account can be approved.');
        }

        $user->forceFill(['status' => 'active'])->save();

        event(new AccountApproved($user, $admin->id, $this->roleOf($admin), $ip));
    }

    /** active → suspended (temporary hold). Revokes access. */
    public function suspend(User $admin, User $user, string $reason, string $ip): void
    {
        $this->assertNotSelf($admin, $user);
        $this->assertNotLastSuperAdmin($user, 'suspend');

        $user->forceFill(['status' => 'suspended'])->save();
        $user->tokens()->delete();

        event(new AccountSuspended($user, $reason, $admin->id, $this->roleOf($admin), $ip));
    }

    /** suspended|deactivated → active. R-4: never restores Super Admin. */
    public function reactivate(User $admin, User $user, string $ip): void
    {
        if (! in_array($user->status, ['suspended', 'deactivated'], true)) {
            throw new \DomainException('Only a suspended or deactivated account can be reactivated.');
        }

        // R-4: strip Super Admin on reactivation — it must be re-granted via four-eyes.
        if ($user->hasRole(Roles::SUPER_ADMIN)) {
            $user->removeRole(Roles::SUPER_ADMIN);
            event(new RoleRevoked($user, Roles::SUPER_ADMIN, $admin->id, $this->roleOf($admin), $ip));
        }

        $user->forceFill(['status' => 'active'])->save();

        event(new AccountReactivated($user, $admin->id, $this->roleOf($admin), $ip));
    }

    /** active|suspended → deactivated (offboarding). Revokes access. */
    public function deactivate(User $admin, User $user, string $reason, string $ip): void
    {
        $this->assertNotSelf($admin, $user);
        $this->assertNotLastSuperAdmin($user, 'deactivate');

        $user->forceFill(['status' => 'deactivated'])->save();
        $user->tokens()->delete();

        event(new AccountDeactivated($user, $reason, $admin->id, $this->roleOf($admin), $ip));
    }

    /** Admin deletion: revoke access, anonymise PII, soft-delete (audit preserved). */
    public function delete(User $admin, User $user, string $ip): void
    {
        $this->assertNotSelf($admin, $user);
        $this->assertNotLastSuperAdmin($user, 'delete');

        $user->tokens()->delete();

        $user->forceFill([
            'name' => 'Deleted User',
            'email' => 'deleted_'.$user->id.'@anonymised.invalid',
            'last_login_ip' => null,
            'mfa_enabled' => false,
            'mfa_secret' => null,
            'mfa_recovery_codes' => null,
            'status' => 'deactivated',
        ])->save();

        event(new AccountDeactivated($user, 'deleted_by_admin', $admin->id, $this->roleOf($admin), $ip));

        $user->delete();
    }

    private function assertNotSelf(User $admin, User $user): void
    {
        if ($admin->id === $user->id) {
            throw new \DomainException('You cannot perform this action on your own account.');
        }
    }

    private function assertNotLastSuperAdmin(User $user, string $action): void
    {
        if (SuperAdminGuard::isLastActive($user)) {
            throw new \DomainException("Cannot {$action} the last active Super Admin.");
        }
    }

    private function roleOf(User $user): ?string
    {
        return $user->getRoleNames()->first();
    }
}
