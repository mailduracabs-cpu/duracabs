<?php

namespace App\Listeners;

use App\Events\PaymentSucceeded;
use App\Models\CustomerSearchActivity;
use App\Services\CustomerSearchActivityService;
use Illuminate\Support\Facades\Log;
use Throwable;

class HandlePaymentSucceeded
{
    public function __construct(
        private readonly CustomerSearchActivityService $searchActivityService
    ) {
    }

    /**
     * Handle the event.
     */
    public function handle(PaymentSucceeded $event): void
    {
        try {
            $search = $event->search()->fresh();

            if (!$search instanceof CustomerSearchActivity) {
                Log::warning('PaymentSucceeded received invalid search activity.');

                return;
            }

            if (!$search->is_converted) {
                $this->searchActivityService->markConverted(
                    search: $search,
                    bookingData: $this->normalizeBookingData(
                        $event->booking()
                    ),
                    paymentData: $this->normalizePaymentData(
                        $event->payment()
                    )
                );
            }

            Log::info('Customer search converted successfully.', [
                'search_activity_id' => $search->id,
                'uuid' => $search->uuid,
                'booking_id' => $event->bookingId(),
                'booking_number' => $event->bookingNumber(),
                'amount' => $event->amount(),
                'gateway' => $event->gateway(),
                'payment_method' => $event->paymentMethod(),
                'transaction_id' => $event->transactionId(),
            ]);
        } catch (Throwable $e) {
            Log::error('HandlePaymentSucceeded failed.', [
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
        ];

        foreach ($stringFields as $field) {
            if (isset($data[$field])) {
                $normalized[$field] = trim((string) $data[$field]);
            }
        }

        if (isset($data['metadata']) && is_array($data['metadata'])) {
            $normalized['metadata'] = $data['metadata'];
        }

        return $normalized;
    }

    /**
     * Normalize payment payload.
     */
    private function normalizePaymentData(array $data): array
    {
        $normalized = [];

        $floatFields = [
            'amount',
            'paid_amount',
            'grand_total',
        ];

        foreach ($floatFields as $field) {
            if (isset($data[$field]) && is_numeric($data[$field])) {
                $normalized[$field] = (float) $data[$field];
            }
        }

        $stringFields = [
            'gateway',
            'payment_method',
            'transaction_id',
            'gateway_payment_id',
            'gateway_order_id',
            'currency',
            'status',
        ];

        foreach ($stringFields as $field) {
            if (isset($data[$field])) {
                $normalized[$field] = trim((string) $data[$field]);
            }
        }

        if (isset($data['metadata']) && is_array($data['metadata'])) {
            $normalized['metadata'] = $data['metadata'];
        }

        return $normalized;
    }
}