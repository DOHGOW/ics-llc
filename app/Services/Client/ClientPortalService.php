<?php

namespace App\Services\Client;

use App\Events\Portal\DeliverableStatusChanged;
use App\Events\Portal\ProjectStatusChanged;
use App\Events\Portal\TicketResolved;
use App\Models\Client\ClientProject;
use App\Models\Client\Deliverable;
use App\Models\Client\Ticket;
use App\Models\Core\User;

/**
 * Client Portal orchestration (Wave 2). Lifecycle transitions flow through here so each
 * governance event fires once and is audited under portal_management (D-056).
 */
class ClientPortalService
{
    public function changeProjectStatus(ClientProject $project, string $toStatus, User $actor): ClientProject
    {
        $from = $project->status;
        if ($from === $toStatus) {
            return $project;
        }

        $project->forceFill([
            'status' => $toStatus,
            'actual_end_date' => $toStatus === 'completed' ? now()->toDateString() : $project->actual_end_date,
        ])->save();

        event(new ProjectStatusChanged($project, $from, $toStatus, $actor->id, $this->roleOf($actor)));

        return $project;
    }

    public function changeDeliverableStatus(Deliverable $deliverable, string $toStatus, User $actor): Deliverable
    {
        $from = $deliverable->status;
        if ($from === $toStatus) {
            return $deliverable;
        }

        $deliverable->forceFill([
            'status' => $toStatus,
            'submitted_at' => $toStatus === 'submitted' ? now() : $deliverable->submitted_at,
            'approved_at' => $toStatus === 'approved' ? now() : $deliverable->approved_at,
            'approved_by' => $toStatus === 'approved' ? $actor->id : $deliverable->approved_by,
        ])->save();

        event(new DeliverableStatusChanged($deliverable, $from, $toStatus, $actor->id, $this->roleOf($actor)));

        return $deliverable;
    }

    public function resolveTicket(Ticket $ticket, User $actor): Ticket
    {
        $ticket->forceFill(['status' => 'resolved', 'resolved_at' => now()])->save();
        event(new TicketResolved($ticket, $actor->id, $this->roleOf($actor)));

        return $ticket;
    }

    private function roleOf(User $user): ?string
    {
        return $user->getRoleNames()->first();
    }
}
