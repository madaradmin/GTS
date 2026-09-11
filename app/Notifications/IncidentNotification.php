<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class IncidentNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $type,
        public string $title,
        public string $message,
        public ?int $incidentId = null,
        public ?string $folio = null,
        public ?string $actionUrl = null,
        public string $icon = 'notifications',
        public string $level = 'info' // info, warning, critical, success
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'incident_id' => $this->incidentId,
            'folio' => $this->folio,
            'action_url' => $this->actionUrl,
            'icon' => $this->icon,
            'level' => $this->level,
        ];
    }
}
