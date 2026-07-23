<?php

// app/Policies/TicketPolicy.php

namespace App\Policies;

use App\Enums\StatutTicket;
use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    /**
     * Determine whether the user can view any tickets.
     * Le filtrage fin (client voit uniquement ses propres tickets)
     * est effectué dans le contrôleur.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view a specific ticket.
     * Même logique : le contrôleur vérifie la propriété pour les clients.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create a ticket.
     * Seuls les clients peuvent créer un ticket manuellement.
     */
    public function create(User $user): bool
    {
        return $user->role->value === 'client';
    }

    /**
     * Determine whether the user can take charge of the ticket.
     * Seuls les techniciens et administrateurs peuvent prendre en charge
     * un ticket au statut "nouveau".
     */
    public function prendreEnCharge(User $user, Ticket $ticket): bool
    {
        if (!$user->isTechnicien() && !$user->isAdministrateur()) {
            return false;
        }
        return $ticket->statut === StatutTicket::NOUVEAU;
    }

    /**
     * Determine whether the user can assign the ticket to a technician.
     * Réservé à l'administrateur, sur un ticket "nouveau".
     */
    public function assigner(User $user, Ticket $ticket): bool
    {
        return $user->isAdministrateur() && $ticket->statut === StatutTicket::NOUVEAU;
    }

    /**
     * Determine whether the user can resolve the ticket.
     * Doit être technicien assigné au ticket OU administrateur,
     * et le ticket doit être au statut "assigne".
     */
    public function resoudre(User $user, Ticket $ticket): bool
    {
        if (!$user->isTechnicien() && !$user->isAdministrateur()) {
            return false;
        }
        if ($ticket->statut !== StatutTicket::ASSIGNE) {
            return false;
        }
        if ($user->isTechnicien() && $ticket->technicien_id !== $user->id) {
            return false;
        }
        return true;
    }

    /**
     * Determine whether the user can release the ticket.
     * Mêmes conditions que pour résoudre : technicien assigné ou admin,
     * sur un ticket "assigne".
     */
    public function liberer(User $user, Ticket $ticket): bool
    {
        if (!$user->isTechnicien() && !$user->isAdministrateur()) {
            return false;
        }
        if ($ticket->statut !== StatutTicket::ASSIGNE) {
            return false;
        }
        if ($user->isTechnicien() && $ticket->technicien_id !== $user->id) {
            return false;
        }
        return true;
    }
}
