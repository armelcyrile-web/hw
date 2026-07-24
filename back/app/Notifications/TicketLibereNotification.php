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

    public function __construct(public Ticket $ticket) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = config('app.frontend_url') . '/technicien/tickets/' . $this->ticket->id;

        return (new MailMessage)
            ->subject('HostWatch - Ticket libéré sur ' . $this->ticket->site->nom)
            ->greeting('Bonjour ' . $notifiable->prenom . ',')
            ->line('Le ticket du site **' . $this->ticket->site->nom . '** a été libéré et est de nouveau ouvert.')
            ->line('**Titre :** ' . $this->ticket->titre)
            ->line('Il est désormais à la disposition de toute l\'équipe technique.')
            ->line('Merci de le prendre en charge si vous êtes disponible.')
            ->action('Voir le ticket', $url)
            ->salutation('L\'équipe HostWatch');
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
