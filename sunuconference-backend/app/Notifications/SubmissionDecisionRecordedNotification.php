<?php

namespace App\Notifications;

use App\Models\Decision;
use App\Models\Submission;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubmissionDecisionRecordedNotification extends Notification
{
    public function __construct(
        public readonly Submission $submission,
        public readonly Decision $decision,
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
            'event' => 'submission.decision_recorded',
            'entity_type' => 'submission',
            'entity_id' => $this->submission->id,
            'message' => $this->message(),
            'decision' => $this->decision->decision,
            'comments' => $this->decision->comments,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->subject())
            ->greeting("Bonjour {$notifiable->name},")
            ->line($this->message());

        if ($this->decision->comments !== null) {
            $mail->line("Commentaire du PC Chair : {$this->decision->comments}");
        }

        return $mail->action('Consulter ma soumission', config('app.url'));
    }

    private function subject(): string
    {
        return match ($this->decision->decision) {
            Decision::DECISION_ACCEPT => 'Votre soumission a été acceptée',
            Decision::DECISION_REJECT => 'Votre soumission a été rejetée',
            Decision::DECISION_REVISION_REQUIRED => 'Une révision est demandée pour votre soumission',
        };
    }

    private function message(): string
    {
        return match ($this->decision->decision) {
            Decision::DECISION_ACCEPT => "Votre soumission « {$this->submission->title} » a été acceptée.",
            Decision::DECISION_REJECT => "Votre soumission « {$this->submission->title} » a été rejetée.",
            Decision::DECISION_REVISION_REQUIRED => "Une révision est demandée pour votre soumission « {$this->submission->title} ».",
        };
    }
}
