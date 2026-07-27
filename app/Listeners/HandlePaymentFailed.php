<?php

namespace App\Listeners;

use App\Events\PaymentFailed;
use App\Models\CustomerSearchActivity;
use App\Services\CustomerSearchActivityService;
use Illuminate\Support\Facades\Log;
use Throwable;

class HandlePaymentFailed
{
    public function __construct(
        private readonly CustomerSearchActivityService $searchActivityService
    ) {
    }

    /**
     * Handle the event.
     */
    public function handle(PaymentFailed $event): void
    {
        try {
            $search = $event->search()->fresh();

            if (!$search instanceof CustomerSearchActivity) {
                Log::warning('PaymentFailed received invalid search activity.');

                return;
            }

            if ($search->is_converted) {
                return;
            }

            $payment = $this->normalizePaymentData(
                $event->payment()
            );

            $payment['failure_reason'] = $event->failureReason();
            $payment['can_retry'] = $event->canRetry();
            $payment['failed_at'] = now();

            $this->searchActivityService->markPaymentFailed(
                search: $search,
                data: $payment
            );

            Log::warning('Customer payment failed.', [
                'search_activity_id' => $search->id,
                'uuid' => $search->uuid,
                'amount' => $event->amount(),
                'gateway' => $event->gateway(),
                'payment_method' => $event->paymentMethod(),
                'transaction_id' => $event->transactionId(),
                'reason' => $event->failureReason(),
                'retry_allowed' => $event->canRetry(),
            ]);
        } catch (Throwable $e) {
            Log::error('HandlePaymentFailed failed.', [
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
            'gateway',
            'payment_method',
            'currency',
            'transaction_id',
            'gateway_payment_id',
            'gateway_order_id',
            'order_id',
            'status',
            'error_code',
            'error_message',
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