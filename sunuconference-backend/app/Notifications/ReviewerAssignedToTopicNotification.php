<?php

namespace App\Notifications;

use App\Models\Topic;
use Illuminate\Notifications\Notification;

class ReviewerAssignedToTopicNotification extends Notification
{
    public function __construct(public readonly Topic $topic) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, int|string> */
    public function toArray(object $notifiable): array
    {
        return [
            'event' => 'topic.reviewer_assigned',
            'entity_type' => 'topic',
            'entity_id' => $this->topic->id,
            'message' => "Vous avez été assigné au topic « {$this->topic->name} » de la conférence « {$this->topic->conference->name} ».",
        ];
    }
}
