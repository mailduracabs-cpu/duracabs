<?php

namespace App\Services;

class BookingNotificationContentService
{
    /**
     * Build one consistent booking notification message for every channel.
     *
     * @param  array<string, mixed>  $context
     * @return array{title: string, message: string}
     */
    public function make(
        string $status,
        string|int|null $bookingId,
        array $context = []
    ): array {
        $status = $this->normalizeStatus($status);
        $bookingId = trim((string) $bookingId);
        $bookingLabel = $bookingId !== ''
            ? "booking {$bookingId}"
            : 'booking';

        $pickupDate = trim((string) (
            $context['pickup_date']
            ?? $context['date']
            ?? ''
        ));

        $pickupTime = trim((string) (
            $context['pickup_time']
            ?? $context['time']
            ?? ''
        ));

        $schedule = trim($pickupDate . ' ' . $pickupTime);
        $reason = trim((string) ($context['reason'] ?? ''));

        return match ($status) {
            'pending' => $this->content(
                'Booking Received',
                "Your Dura Cabs {$bookingLabel} has been received"
                    . ($schedule !== '' ? " for {$schedule}" : '')
                    . '.'
            ),
            'confirmed' => $this->content(
                'Booking Confirmed',
                "Your Dura Cabs {$bookingLabel} has been confirmed"
                    . ($schedule !== '' ? " for {$schedule}" : '')
                    . '.'
            ),
            'rescheduled' => $this->content(
                'Booking Rescheduled',
                "Your Dura Cabs {$bookingLabel} has been rescheduled"
                    . ($schedule !== '' ? " to {$schedule}" : '')
                    . '.'
            ),
            'driver_assigned' => $this->content(
                'Driver Assigned',
                "A driver has been assigned to your Dura Cabs {$bookingLabel}."
            ),
            'arriving', 'driver_arriving' => $this->content(
                'Driver Arriving',
                "Your driver for Dura Cabs {$bookingLabel} is arriving soon."
            ),
            'running', 'started' => $this->content(
                'Trip Started',
                "Your trip for Dura Cabs {$bookingLabel} has started."
            ),
            'completed' => $this->content(
                'Trip Completed',
                "Your Dura Cabs {$bookingLabel} has been completed."
            ),
            'cancelled' => $this->content(
                'Booking Cancelled',
                "Your Dura Cabs {$bookingLabel} has been cancelled"
                    . ($reason !== '' ? ". Reason: {$reason}." : '.')
            ),
            'rejected' => $this->content(
                'Booking Rejected',
                "Your Dura Cabs {$bookingLabel} could not be accepted"
                    . ($reason !== '' ? ". Reason: {$reason}." : '.')
            ),
            default => $this->content(
                'Booking Update',
                "Your Dura Cabs {$bookingLabel} status is now {$status}."
            ),
        };
    }

    public function normalizeStatus(string $status): string
    {
        $status = strtolower(trim($status));

        return match ($status) {
            'booking_created', 'new', 'received' => 'pending',
            'booking_confirmed', 'confirm' => 'confirmed',
            'booking_cancelled', 'cancel', 'canceled' => 'cancelled',
            'booking_rescheduled' => 'rescheduled',
            'run', 'in_progress', 'in-progress' => 'running',
            'complete', 'closed' => 'completed',
            default => $status !== '' ? $status : 'pending',
        };
    }

    /** @return array{title: string, message: string} */
    private function content(string $title, string $message): array
    {
        return ['title' => $title, 'message' => $message];
    }
}
