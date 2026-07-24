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

        return (new MailMessage)
            ->subject('HostWatch - Nouveau ticket sur ' . $this->ticket->site->nom)
            ->greeting('Bonjour ' . $notifiable->prenom . ',')
            ->line('Un nouveau ticket a été créé sur le site **' . $this->ticket->site->nom . '** (' . $this->ticket->site->url . ').')
            ->line('**Titre :** ' . $this->ticket->titre)
            ->line('**Priorité :** ' . $this->ticket->priorite->value)
            ->line('**Origine :** ' . ($this->ticket->origine->value === 'automatique' ? 'Surveillance automatique' : 'Signalement manuel'))
            ->line('Vous pouvez consulter le détail et prendre les mesures nécessaires.')
            ->action('Voir le ticket', $url)
            ->salutation('L\'équipe HostWatch');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'titre'     => $this->ticket->titre,
            'site_nom'  => $this->ticket->site->nom,
            'priorite'  => $this->ticket->priorite->value,
            'message'   => 'Un nouveau ticket a été créé pour le site ' . $this->ticket->site->nom . '.',
        ];
    }
}
