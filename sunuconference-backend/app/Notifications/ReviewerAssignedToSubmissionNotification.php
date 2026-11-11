<?php

namespace App\Notifications;

use App\Models\Submission;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewerAssignedToSubmissionNotification extends Notification
{
    public function __construct(public readonly Submission $submission) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /** @return array<string, int|string> */
    public function toArray(object $notifiable): array
    {
        return [
            'event' => 'submission.reviewer_assigned',
            'entity_type' => 'submission',
            'entity_id' => $this->submission->id,
            'message' => "Vous avez été assigné à la soumission « {$this->submission->title} » de la conférence « {$this->submission->conference->name} », dans le topic « {$this->submission->topic->name} ».",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nouvelle relecture à effectuer')
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Vous avez été assigné à la relecture de la soumission « {$this->submission->title} ».")
            ->line("Conférence : {$this->submission->conference->name}")
            ->line("Topic : {$this->submission->topic->name}")
            ->line('Connectez-vous à votre espace reviewer pour déposer votre relecture.')
            ->action('Accéder à mes relectures', config('app.url'));
    }
}
