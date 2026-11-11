<?php

namespace App\Notifications;

use App\Models\Conference;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConferenceValidatedNotification extends Notification
{
    public function __construct(public readonly Conference $conference) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /** @return array<string, int|string> */
    public function toArray(object $notifiable): array
    {
        return [
            'event' => 'conference.validated',
            'entity_type' => 'conference',
            'entity_id' => $this->conference->id,
            'message' => "Votre conférence « {$this->conference->name} » a été validée.",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre conférence a été validée')
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Félicitations, votre conférence « {$this->conference->name} » a été validée.")
            ->line('Vous pouvez désormais accéder à votre espace PC Chair.')
            ->action('Accéder à mon espace', config('app.url'));
    }
}
