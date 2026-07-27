<?php

namespace App\Listeners;

use App\Events\BookingCancelled;
use App\Models\CustomerSearchActivity;
use App\Services\CustomerSearchActivityService;
use Illuminate\Support\Facades\Log;
use Throwable;

class HandleBookingCancelled
{
    public function __construct(
        private readonly CustomerSearchActivityService $searchActivityService
    ) {
    }

    /**
     * Handle the event.
     */
    public function handle(BookingCancelled $event): void
    {
        try {
            $search = $event->search()->fresh();

            if (!$search instanceof CustomerSearchActivity) {
                Log::warning('BookingCancelled received invalid search activity.');

                return;
            }

            $booking = $this->normalizeBookingData(
                $event->booking()
            );

            $booking['cancellation_reason'] = $event->cancellationReason();

            $this->searchActivityService->markBookingCancelled(
                search: $search,
                data: $booking
            );

            Log::warning('Booking cancellation tracked.', [
                'search_activity_id' => $search->id,
                'uuid' => $search->uuid,
                'booking_id' => $event->bookingId(),
                'booking_number' => $event->bookingNumber(),
                'cancelled_by' => $event->cancelledBy(),
                'refund_amount' => $event->refundAmount(),
                'refund_status' => $event->refundStatus(),
                'cancellation_charge' => $event->cancellationCharge(),
                'reason' => $event->cancellationReason(),
            ]);
        } catch (Throwable $e) {
            Log::error('HandleBookingCancelled failed.', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw $e;
        }
    }

    /**
     * Normalize booking payload.
     */
    private function normalizeBookingData(array $data): array
    {
        $normalized = [];

        $integerFields = [
            'booking_id',
            'id',
            'customer_id',
            'vehicle_id',
            'driver_id',
            'transporter_id',
        ];

        foreach ($integerFields as $field) {
            if (isset($data[$field]) && is_numeric($data[$field])) {
                $normalized[$field] = (int) $data[$field];
            }
        }

        $floatFields = [
            'refund_amount',
            'cancellation_charge',
            'paid_amount',
            'total_amount',
        ];

        foreach ($floatFields as $field) {
            if (isset($data[$field]) && is_numeric($data[$field])) {
                $normalized[$field] = (float) $data[$field];
            }
        }

        $stringFields = [
            'booking_no',
            'booking_number',
            'status',
            'refund_status',
            'cancelled_by',
            'cancellation_type',
            'payment_status',
        ];

        foreach ($stringFields as $field) {
            if (isset($data[$field])) {
                $normalized[$field] = trim((string) $data[$field]);
            }
        }

        if (isset($data['cancelled_at'])) {
            $normalized['cancelled_at'] = $data['cancelled_at'];
        }

        if (isset($data['metadata']) && is_array($data['metadata'])) {
            $normalized['metadata'] = $data['metadata'];
        }

        return $normalized;
    }
}