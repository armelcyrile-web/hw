<?php

// app/Notifications/NouveauTicketNotification.php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NouveauTicketNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Ticket $ticket)
    {
    }

    /**
     * Get the notification's delivery channels.
     *
     * Choix d'implémentation : on détermine les canaux en fonction du rôle du destinataire.
     * - Si le destinataire est le client propriétaire du ticket -> database uniquement (confirmation in-app).
     * - Sinon (technicien ou admin) -> mail + database (alerte).
     */
    public function via(object $notifiable): array
    {
        if ($notifiable->role->value === 'client') {
            return ['database'];
        }

        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nouveau ticket : '.$this->ticket->titre)
            ->line('Un nouveau ticket a été créé.')
            ->line('Site : '.$this->ticket->site->nom.' ('.$this->ticket->site->url.')')
            ->line('Titre : '.$this->ticket->titre)
            ->line('Priorité : '.$this->ticket->priorite->value)
            ->line('Origine : '.$this->ticket->origine->value)
            ->line('Description : '.$this->ticket->description)
            ->action('Voir le ticket', url('/tickets/'.$this->ticket->id));
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'titre'     => $this->ticket->titre,
            'site_nom'  => $this->ticket->site->nom,
            'priorite'  => $this->ticket->priorite->value,
            'message'   => 'Un nouveau ticket a été créé pour le site '.$this->ticket->site->nom.'.',
        ];
    }
}
