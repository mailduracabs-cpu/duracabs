<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

class ReminderNotificationService
{
    public function __construct(
        private readonly NotificationManagerService $notifications
    ) {
    }

    public function bookingReminder(
        Model $booking,
        array $channels,
        array $recipients,
        string $message
    ): array {
        return $this->notifications->send(
            event: 'booking_reminder',
            channels: $channels,
            recipients: $recipients,
            message: $message,
            subject: 'Booking Reminder',
            payload: ['booking_id' => $booking->getKey()],
            notifiable: $booking
        );
    }

    public function tripReminder(
        Model $booking,
        array $channels,
        array $recipients,
        string $message
    ): array {
        return $this->notifications->send(
            event: 'trip_reminder',
            channels: $channels,
            recipients: $recipients,
            message: $message,
            subject: 'Trip Reminder',
            payload: ['booking_id' => $booking->getKey()],
            notifiable: $booking
        );
    }

    public function reviewRequest(
        Model $booking,
        array $channels,
        array $recipients,
        string $message
    ): array {
        return $this->notifications->send(
            event: 'review_request',
            channels: $channels,
            recipients: $recipients,
            message: $message,
            subject: 'How Was Your Trip?',
            payload: ['booking_id' => $booking->getKey()],
            notifiable: $booking
        );
    }
}
