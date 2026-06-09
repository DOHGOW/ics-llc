<?php

namespace App\Listeners\Security;

use App\Events\Core\AccountDeactivated;
use App\Events\Core\AccountSuspended;
use App\Events\Core\RoleAssigned;
use App\Events\Core\RoleRevoked;
use App\Notifications\Core\SecurityAlertNotification;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Notification;

/**
 * Raises security alerts (R-7) for high-sensitivity lifecycle events. Recipients are
 * configured in config('ics.security.alert_recipients'). If none are configured the
 * alert is silently skipped (alerting is additive to the immutable audit trail).
 */
class SecurityAlertSubscriber
{
    public function handleRoleAssigned(RoleAssigned $e): void
    {
        $this->alert("Role ASSIGNED: {$e->role}", [
            'target_user_id' => $e->target->id,
            'actor_id' => $e->actorId,
            'previous_role' => $e->previousRole,
            'ip' => $e->ipAddress,
        ]);
    }

    public function handleRoleRevoked(RoleRevoked $e): void
    {
        $this->alert("Role REVOKED: {$e->role}", [
            'target_user_id' => $e->target->id,
            'actor_id' => $e->actorId,
            'ip' => $e->ipAddress,
        ]);
    }

    public function handleSuspended(AccountSuspended $e): void
    {
        $this->alert('Account SUSPENDED', [
            'target_user_id' => $e->user->id,
            'actor_id' => $e->actorId,
            'reason' => $e->reason,
            'ip' => $e->ipAddress,
        ]);
    }

    public function handleDeactivated(AccountDeactivated $e): void
    {
        $this->alert('Account DEACTIVATED', [
            'target_user_id' => $e->user->id,
            'actor_id' => $e->actorId,
            'reason' => $e->reason,
            'ip' => $e->ipAddress,
        ]);
    }

    /** @param array<string,mixed> $context */
    private function alert(string $summary, array $context): void
    {
        $recipients = (array) config('ics.security.alert_recipients', []);

        if ($recipients !== []) {
            Notification::route('mail', $recipients)
                ->notify(new SecurityAlertNotification($summary, $context));
        }
    }

    /** @return array<class-string,string> */
    public function subscribe(Dispatcher $events): array
    {
        return [
            RoleAssigned::class => 'handleRoleAssigned',
            RoleRevoked::class => 'handleRoleRevoked',
            AccountSuspended::class => 'handleSuspended',
            AccountDeactivated::class => 'handleDeactivated',
        ];
    }
}
