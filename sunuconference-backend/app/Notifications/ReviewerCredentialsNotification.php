<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewerCredentialsNotification extends Notification
{
    public function __construct(
        public readonly User $reviewer,
        public readonly string $temporaryPassword,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Vos identifiants reviewer SunuConférence')
            ->greeting("Bonjour {$this->reviewer->name},")
            ->line('Un compte reviewer a été créé pour vous sur SunuConférence.')
            ->line("Adresse e-mail de connexion : {$this->reviewer->email}")
            ->line("Mot de passe temporaire : {$this->temporaryPassword}")
            ->line('Vous devrez changer ce mot de passe lors de votre première connexion.')
            ->action('Se connecter', config('app.url'));
    }
}
