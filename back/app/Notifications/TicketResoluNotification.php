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

    public function __construct(public Ticket $ticket) {}

    public function via(object $notifiable): array
    {
        if ($notifiable->role->value === 'client') {
            return ['database'];
        }
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = config('app.frontend_url') . '/technicien/tickets/' . $this->ticket->id;

        $duree = $this->ticket->date_resolution
            ? $this->ticket->created_at->diffInMinutes($this->ticket->date_resolution) . ' min'
            : 'non calculée';

        return (new MailMessage)
            ->subject('HostWatch - Ticket résolu sur ' . $this->ticket->site->nom)
            ->greeting('Bonjour ' . $notifiable->prenom . ',')
            ->line('Le ticket concernant le site **' . $this->ticket->site->nom . '** a été résolu.')
            ->line('**Titre :** ' . $this->ticket->titre)
            ->line('**Durée d\'intervention :** ' . $duree)
            ->line('L\'incident est clos. Aucune action supplémentaire n\'est requise.')
            ->action('Voir le ticket', $url)
            ->salutation('L\'équipe HostWatch');
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
