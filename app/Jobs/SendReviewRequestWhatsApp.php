<?php

namespace App\Jobs;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendReviewRequestWhatsApp implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public readonly int $orderId
    ) {
    }

    public function handle(): void
    {
        $order = Order::query()
            ->with([
                'user',
                'address',
            ])
            ->find($this->orderId);

        if (! $order) {
            Log::warning(
                'Review request WhatsApp skipped because order was not found.',
                ['order_id' => $this->orderId]
            );

            return;
        }

        if ((string) $order->status !== 'closed') {
            Log::info(
                'Review request WhatsApp skipped because trip is not completed.',
                [
                    'order_id' => $order->id,
                    'status' => $order->status,
                ]
            );

            return;
        }

        $extraOptions = $this->decodeExtraOptions(
            $order->extraOptions
        );

        if (filled($extraOptions['review_request_sent_at'] ?? null)) {
            Log::info(
                'Review request WhatsApp already sent.',
                ['order_id' => $order->id]
            );

            return;
        }

        $customerMobile = trim((string) (
            $order->address?->phone
            ?: $order->user?->mobile
            ?: ''
        ));

        if ($customerMobile === '') {
            Log::warning(
                'Review request WhatsApp skipped because customer mobile is missing.',
                ['order_id' => $order->id]
            );

            return;
        }

        $customerName = trim((string) (
            $order->address?->full_name
            ?: $order->user?->name
            ?: 'Customer'
        ));

        $bookingId = OrderResource::bookingNumber($order);

        $reviewLink = route(
            'reviews',
            ['booking' => $bookingId]
        );

        try {
            $result = WhatsAppService::sendTemplate(
                number: $customerMobile,
                templateName: (string) config(
                    'services.whatsapp.templates.review_request',
                    'review_request_v1'
                ),
                languageCode: (string) config(
                    'services.whatsapp.default_language',
                    'en'
                ),
                bodyParameters: [
                    $customerName,
                    $bookingId,
                    $reviewLink,
                ]
            );

            if (! (bool) (
                $result['status']
                ?? $result['success']
                ?? false
            )) {
                Log::warning(
                    'Review request WhatsApp was not accepted.',
                    [
                        'order_id' => $order->id,
                        'result' => $result,
                    ]
                );

                return;
            }

            $extraOptions['review_request_sent_at'] =
                now()->toIso8601String();

            $extraOptions['review_request_message_id'] =
                $result['message_id'] ?? null;

            $order->forceFill([
                'extraOptions' => $extraOptions,
            ])->save();

            Log::info(
                'Review request WhatsApp sent.',
                [
                    'order_id' => $order->id,
                    'booking_id' => $bookingId,
                    'message_id' => $result['message_id'] ?? null,
                ]
            );
        } catch (Throwable $exception) {
            Log::error(
                'Review request WhatsApp failed.',
                [
                    'order_id' => $order->id,
                    'error' => $exception->getMessage(),
                ]
            );

            throw $exception;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeExtraOptions(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return (array) $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}