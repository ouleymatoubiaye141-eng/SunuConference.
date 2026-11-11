<?php

namespace App\Notifications;

use App\Models\Conference;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConferenceRejectedNotification extends Notification
{
    public function __construct(
        public readonly Conference $conference,
        public readonly string $reason,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /** @return array<string, int|string> */
    public function toArray(object $notifiable): array
    {
        return [
            'event' => 'conference.rejected',
            'entity_type' => 'conference',
            'entity_id' => $this->conference->id,
            'message' => "Votre conférence « {$this->conference->name} » a été rejetée.",
            'reason' => $this->reason,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre conférence a été rejetée')
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre conférence « {$this->conference->name} » n’a pas été validée.")
            ->line("Motif : {$this->reason}")
            ->action('Accéder à mon espace', config('app.url'));
    }
}
