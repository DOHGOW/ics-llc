<?php

namespace App\Policies\Client;

use App\Models\Client\Ticket;
use App\Models\Core\User;
use App\Policies\OrgOwnedPolicy;

/**
 * Ticket authorisation (Wave 2). Clients create/read/reply on their own org's tickets;
 * ICS staff manage all. Internal replies are staff-only (enforced separately at query +
 * resource layers, W2-4). Super Admin via Gate::before; default-deny.
 */
class TicketPolicy extends OrgOwnedPolicy
{
    public function view(User $user, Ticket $ticket): bool
    {
        return ($user->can('client.tickets.read.own') || $user->can('client.tickets.manage'))
            && $this->accessible($user, $ticket);
    }

    public function reply(User $user, Ticket $ticket): bool
    {
        return ($user->can('client.tickets.reply') || $user->can('client.tickets.manage'))
            && $this->accessible($user, $ticket);
    }

    /** Only ICS staff may post internal replies (W2-4 policy layer). */
    public function replyInternal(User $user, Ticket $ticket): bool
    {
        return $user->can('client.tickets.manage') && $this->isInternalStaff($user);
    }

    public function manage(User $user, Ticket $ticket): bool
    {
        return $user->can('client.tickets.manage') && $this->accessible($user, $ticket);
    }

    public function create(User $user): bool
    {
        return $user->can('client.tickets.create');
    }
}
