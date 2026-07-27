<?php

namespace App\Listeners;

use App\Events\BookingCompleted;
use App\Models\CustomerSearchActivity;
use App\Services\CustomerSearchActivityService;
use Illuminate\Support\Facades\Log;
use Throwable;

class HandleBookingCompleted
{
    public function __construct(
        private readonly CustomerSearchActivityService $searchActivityService
    ) {
    }

    /**
     * Handle the event.
     */
    public function handle(BookingCompleted $event): void
    {
        try {
            $search = $event->search()->fresh();

            if (!$search instanceof CustomerSearchActivity) {
                Log::warning('BookingCompleted received invalid search activity.');

                return;
            }

            $booking = $this->normalizeBookingData(
                $event->booking()
            );

            $this->searchActivityService->markBookingCompleted(
                search: $search,
                data: $booking
            );

            Log::info('Booking completed tracked successfully.', [
                'search_activity_id' => $search->id,
                'uuid' => $search->uuid,
                'booking_id' => $event->bookingId(),
                'booking_number' => $event->bookingNumber(),
                'status' => $event->status(),
                'service_type' => $event->serviceType(),
                'vehicle_id' => $event->vehicleId(),
                'driver_id' => $event->driverId(),
                'total_amount' => $event->totalAmount(),
                'paid_amount' => $event->paidAmount(),
            ]);
        } catch (Throwable $e) {
            Log::error('HandleBookingCompleted failed.', [
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
            'vehicle_id',
            'driver_id',
            'customer_id',
            'transporter_id',
        ];

        foreach ($integerFields as $field) {
            if (isset($data[$field]) && is_numeric($data[$field])) {
                $normalized[$field] = (int) $data[$field];
            }
        }

        $floatFields = [
            'total_amount',
            'paid_amount',
            'balance_amount',
            'distance',
            'trip_distance',
            'rating',
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
            'service_type',
            'trip_type',
            'vehicle_name',
            'driver_name',
            'payment_status',
        ];

        foreach ($stringFields as $field) {
            if (isset($data[$field])) {
                $normalized[$field] = trim((string) $data[$field]);
            }
        }

        if (isset($data['completed_at'])) {
            $normalized['completed_at'] = $data['completed_at'];
        }

        if (isset($data['metadata']) && is_array($data['metadata'])) {
            $normalized['metadata'] = $data['metadata'];
        }

        return $normalized;
    }
}