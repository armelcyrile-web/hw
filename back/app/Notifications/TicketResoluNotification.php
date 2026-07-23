<?php

// app/Notifications/TicketResoluNotification.php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketResoluNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Ticket $ticket)
    {
    }

    public function via(object $notifiable): array
    {
        if ($notifiable->role->value === 'client') {
            return ['database'];
        }

        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $duree = $this->ticket->date_resolution
            ? $this->ticket->created_at->diffInMinutes($this->ticket->date_resolution).' min'
            : 'non calculée';

        return (new MailMessage)
            ->subject('Ticket résolu : '.$this->ticket->titre)
            ->line('Le ticket a été résolu.')
            ->line('Site : '.$this->ticket->site->nom)
            ->line('Durée d\'intervention : '.$duree)
            ->action('Voir le ticket', url('/tickets/'.$this->ticket->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'titre'     => $this->ticket->titre,
            'site_nom'  => $this->ticket->site->nom,
            'message'   => 'Le ticket a été résolu.',
        ];
    }
}
