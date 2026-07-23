<?php

// app/Notifications/TicketLibereNotification.php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketLibereNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Ticket $ticket)
    {
    }

    /**
     * Cette notification n'est jamais envoyée à un client, mais par sécurité on filtre tout de même.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $quiALibere = $this->ticket->technicien_id
            ? $this->ticket->technicien->nom.' '.$this->ticket->technicien->prenom
            : 'Administrateur';

        return (new MailMessage)
            ->subject('Ticket libéré : '.$this->ticket->titre)
            ->line('Le ticket a été libéré et remis dans la file d\'attente.')
            ->line('Site : '.$this->ticket->site->nom)
            ->line('Libéré par : '.$quiALibere)
            ->action('Voir le ticket', url('/tickets/'.$this->ticket->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'titre'     => $this->ticket->titre,
            'site_nom'  => $this->ticket->site->nom,
            'message'   => 'Un ticket a été libéré et est de nouveau disponible.',
        ];
    }
}
