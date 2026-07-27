<?php

namespace App\Listeners;

use App\Events\PaymentStarted;
use App\Models\CustomerSearchActivity;
use App\Services\CustomerSearchActivityService;
use Illuminate\Support\Facades\Log;
use Throwable;

class HandlePaymentStarted
{
    public function __construct(
        private readonly CustomerSearchActivityService $searchActivityService
    ) {
    }

    /**
     * Handle the event.
     */
    public function handle(PaymentStarted $event): void
    {
        try {
            $search = $event->search()->fresh();

            if (!$search instanceof CustomerSearchActivity) {
                Log::warning('PaymentStarted received invalid search activity.');

                return;
            }

            if ($search->is_converted) {
                return;
            }

            $payment = $this->normalizePaymentData(
                $event->payment()
            );

            $this->searchActivityService->markPaymentStarted(
                search: $search,
                data: $payment
            );

            Log::info('Payment started tracked.', [
                'search_activity_id' => $search->id,
                'uuid' => $search->uuid,
                'amount' => $payment['amount'] ?? null,
                'payment_method' => $payment['payment_method'] ?? null,
                'gateway' => $payment['gateway'] ?? null,
            ]);
        } catch (Throwable $e) {
            Log::error('HandlePaymentStarted failed.', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw $e;
        }
    }

    /**
     * Normalize payment payload.
     */
    private function normalizePaymentData(array $data): array
    {
        $normalized = [];

        $floatFields = [
            'amount',
            'grand_total',
            'paid_amount',
            'wallet_amount',
            'coupon_discount',
        ];

        foreach ($floatFields as $field) {
            if (
                isset($data[$field]) &&
                is_numeric($data[$field])
            ) {
                $normalized[$field] = (float) $data[$field];
            }
        }

        $stringFields = [
            'payment_method',
            'gateway',
            'currency',
            'transaction_id',
            'order_id',
            'gateway_order_id',
            'gateway_payment_id',
            'gateway_signature',
            'status',
        ];

        foreach ($stringFields as $field) {
            if (isset($data[$field])) {
                $normalized[$field] = trim((string) $data[$field]);
            }
        }

        if (
            isset($data['metadata']) &&
            is_array($data['metadata'])
        ) {
            $normalized['metadata'] = $data['metadata'];
        }

        return $normalized;
    }
}