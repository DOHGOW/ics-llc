<?php

namespace App\Services\Auth;

use App\Audit\AuditCategory;
use App\Authorization\EscalationReasonCode;
use App\Authorization\Roles;
use App\Authorization\SuperAdminGuard;
use App\Events\Core\RoleAssigned;
use App\Events\Core\RoleRevoked;
use App\Models\Core\RoleEscalationApproval;
use App\Models\Core\User;
use App\Notifications\Core\SecurityAlertNotification;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\Notification;

/**
 * Role-assignment escalation guard (D-044).
 *
 * Rules:
 *  - No actor may grant a role at or above its own privilege level.
 *  - The Super Admin role is NEVER directly assignable — it requires the
 *    four-eyes flow: one Super Admin requests, a DIFFERENT Super Admin approves.
 *  - Every assignment / decision is mirrored to the immutable core_audit_logs.
 *
 * Scope-limited: this is a single-purpose security guard, not a workflow engine.
 */
class RoleAssignmentService
{
    public function __construct(private readonly AuditService $audit) {}

    /** Highest privilege level held by the actor. */
    public function actorLevel(User $actor): int
    {
        return (int) $actor->getRoleNames()
            ->map(fn (string $role) => Roles::level($role))
            ->max() ?? 0;
    }

    public function canGrant(User $actor, string $role): bool
    {
        if ($role === Roles::SUPER_ADMIN) {
            return false; // never a direct grant — four-eyes only
        }

        return Roles::level($role) < $this->actorLevel($actor);
    }

    /**
     * Direct (non-escalation) role assignment for roles strictly below the
     * actor's level. Super Admin is rejected here.
     *
     * @throws \DomainException
     */
    public function assign(User $actor, User $target, string $role, string $ip): void
    {
        if ($role === Roles::SUPER_ADMIN) {
            throw new \DomainException('The Super Admin role requires the four-eyes approval flow.');
        }

        if (! in_array($role, Roles::ALL, true)) {
            throw new \DomainException('Unknown role.');
        }

        if (! $this->canGrant($actor, $role)) {
            throw new \DomainException('You cannot grant a role at or above your own privilege level.');
        }

        $previous = $target->getRoleNames()->first();
        $target->assignRole($role);

        // R-2: a role change revokes the target's active tokens (no stale privilege).
        $target->tokens()->delete();

        // Domain event → AuditEventSubscriber records the role_assignment audit;
        // SecurityAlertSubscriber raises an alert (R-7).
        event(new RoleAssigned($target, $role, $previous, $actor->id, $this->actorRole($actor), $ip));
    }

    /**
     * Revoke a role. R-3: the last active Super Admin's role cannot be revoked.
     * Revoking the Super Admin role requires a Super Admin actor.
     *
     * @throws \DomainException
     */
    public function revokeRole(User $actor, User $target, string $role, string $ip): void
    {
        if ($role === Roles::SUPER_ADMIN) {
            if (! $actor->hasRole(Roles::SUPER_ADMIN)) {
                throw new \DomainException('Only a Super Admin may revoke the Super Admin role.');
            }
            if (SuperAdminGuard::isLastActive($target)) {
                throw new \DomainException('Cannot revoke the last active Super Admin.');
            }
        } elseif (Roles::level($role) >= $this->actorLevel($actor)) {
            throw new \DomainException('You cannot revoke a role at or above your own privilege level.');
        }

        $target->removeRole($role);

        // R-2: revoke tokens on role change.
        $target->tokens()->delete();

        event(new RoleRevoked($target, $role, $actor->id, $this->actorRole($actor), $ip));
    }

    /**
     * Step 1 of four-eyes: a Super Admin REQUESTS a Super Admin grant for a target.
     *
     * @throws \DomainException
     */
    public function requestSuperAdmin(User $requester, User $target, string $reasonCode, string $ip): RoleEscalationApproval
    {
        if (! $requester->hasRole(Roles::SUPER_ADMIN)) {
            throw new \DomainException('Only a Super Admin may initiate a Super Admin escalation.');
        }

        if (! EscalationReasonCode::isValid($reasonCode)) {
            throw new \DomainException('Invalid reason code.');
        }

        $request = RoleEscalationApproval::create([
            'requester_id' => $requester->id,
            'target_user_id' => $target->id,
            'requested_role' => Roles::SUPER_ADMIN,
            'previous_role' => $target->getRoleNames()->first(),
            'reason_code' => $reasonCode,
            'status' => 'pending',
            'requester_ip' => $ip,
            'expires_at' => now()->addDays(2),
        ]);

        $this->audit->log('SUPER_ADMIN_REQUESTED', 'authorization', AuditCategory::ESCALATION_REQUEST,
            $requester->id, Roles::SUPER_ADMIN, RoleEscalationApproval::class, $request->id,
            null, ['target_user_id' => $target->id, 'reason_code' => $reasonCode], $ip);

        return $request;
    }

    /**
     * Step 2 of four-eyes: a DIFFERENT Super Admin APPROVES, which performs the grant.
     *
     * @throws \DomainException
     */
    public function approveSuperAdmin(User $approver, RoleEscalationApproval $request, string $ip): void
    {
        if (! $approver->hasRole(Roles::SUPER_ADMIN)) {
            throw new \DomainException('Only a Super Admin may approve an escalation.');
        }

        if ($approver->id === $request->requester_id) {
            throw new \DomainException('Four-eyes: the approver must be a different Super Admin from the requester.');
        }

        if (! $request->isPending()) {
            throw new \DomainException('This request is not pending.');
        }

        if ($request->isExpired()) {
            $request->update(['status' => 'expired']);
            $this->audit->log('SUPER_ADMIN_EXPIRED', 'authorization', AuditCategory::ESCALATION_APPROVAL,
                $approver->id, Roles::SUPER_ADMIN, RoleEscalationApproval::class, $request->id, null, null, $ip);
            throw new \DomainException('This request has expired.');
        }

        $target = User::findOrFail($request->target_user_id);
        $target->assignRole(Roles::SUPER_ADMIN);

        // R-2: revoke the target's existing tokens on this privilege change.
        $target->tokens()->delete();

        $request->update([
            'status' => 'approved',
            'approver_id' => $approver->id,
            'approver_ip' => $ip,
            'decided_at' => now(),
        ]);

        $this->audit->log('SUPER_ADMIN_GRANTED', 'authorization', AuditCategory::ESCALATION_APPROVAL,
            $approver->id, Roles::SUPER_ADMIN, RoleEscalationApproval::class, $request->id,
            ['previous_role' => $request->previous_role],
            ['target_user_id' => $target->id, 'requester_id' => $request->requester_id], $ip);

        // R-7: high-sensitivity security alert on a Super Admin grant.
        $this->alertSecurity('Super Admin role GRANTED', [
            'target_user_id' => $target->id,
            'approver_id' => $approver->id,
            'requester_id' => $request->requester_id,
            'ip' => $ip,
        ]);
    }

    /**
     * @throws \DomainException
     */
    public function reject(User $approver, RoleEscalationApproval $request, string $ip): void
    {
        if (! $approver->hasRole(Roles::SUPER_ADMIN) || $approver->id === $request->requester_id) {
            throw new \DomainException('Only a different Super Admin may reject this request.');
        }

        if (! $request->isPending()) {
            throw new \DomainException('This request is not pending.');
        }

        $request->update([
            'status' => 'rejected',
            'approver_id' => $approver->id,
            'approver_ip' => $ip,
            'decided_at' => now(),
        ]);

        $this->audit->log('SUPER_ADMIN_REJECTED', 'authorization', AuditCategory::ESCALATION_APPROVAL,
            $approver->id, Roles::SUPER_ADMIN, RoleEscalationApproval::class, $request->id, null, null, $ip);
    }

    private function actorRole(User $actor): ?string
    {
        return $actor->getRoleNames()->first();
    }

    /** @param array<string,mixed> $context */
    private function alertSecurity(string $summary, array $context): void
    {
        $recipients = (array) config('ics.security.alert_recipients', []);

        if ($recipients !== []) {
            Notification::route('mail', $recipients)
                ->notify(new SecurityAlertNotification($summary, $context));
        }
    }
}
