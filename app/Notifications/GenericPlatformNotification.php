<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class GenericPlatformNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $title,
        public readonly string $message,
        public readonly array $payload = []
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'payload' => $this->payload,
        ];
    }
}
