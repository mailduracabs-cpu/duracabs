<?php

namespace App\Services;

use App\Models\NotificationDeliveryLog;
use App\Models\WhatsAppTemplate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotificationManagerService
{
    private const MAX_RETRY_COUNT = 3;

    private const SUPPORTED_CHANNELS = [
        'whatsapp',
        'database',
        'in_app',
        'push',
        'firebase',
        'email',
    ];

    /**
     * Send a notification through one or more channels.
     *
     * Example:
     *
     * $service->send(
     *     event: 'booking_created',
     *     channels: ['push', 'whatsapp', 'email'],
     *     recipients: [
     *         'push' => $user->device_token,
     *         'whatsapp' => $user->mobile,
     *         'email' => $user->email,
     *     ],
     *     message: 'Your booking has been created.',
     *     subject: 'Booking Created',
     *     payload: [
     *         'booking_id' => 'DURA000001',
     *         'type' => 'booking',
     *     ],
     *     notifiable: $user
     * );
     */
    public function send(
        string $event,
        array $channels,
        array $recipients,
        string $message,
        ?string $subject = null,
        array $payload = [],
        ?Model $notifiable = null
    ): array {
        $event = trim($event);
        $message = trim($message);
        $subject = filled($subject)
            ? trim((string) $subject)
            : 'Dura Cabs';

        $channels = $this->normalizeChannels($channels);

        if ($event === '') {
            return [
                'status' => false,
                'message' => 'Notification event is required.',
                'total' => 0,
                'sent' => 0,
                'failed' => 0,
                'results' => [],
            ];
        }

        if ($message === '') {
            return [
                'status' => false,
                'message' => 'Notification message is required.',
                'total' => 0,
                'sent' => 0,
                'failed' => 0,
                'results' => [],
            ];
        }

        if ($channels === []) {
            return [
                'status' => false,
                'message' => 'No supported notification channels supplied.',
                'total' => 0,
                'sent' => 0,
                'failed' => 0,
                'results' => [],
            ];
        }

        $results = [];
        $sent = 0;
        $failed = 0;

        foreach ($channels as $channel) {
            $recipient = $this->resolveRecipient(
                channel: $channel,
                recipients: $recipients,
                notifiable: $notifiable
            );

            if (! $this->hasValidRecipient($channel, $recipient)) {
                $failed++;

                $results[$channel] = [
                    'status' => false,
                    'channel' => $channel,
                    'message' => 'Recipient missing or invalid.',
                ];

                continue;
            }

            $log = $this->createDeliveryLog(
                event: $event,
                channel: $channel,
                recipient: $recipient,
                subject: $subject,
                message: $message,
                payload: $payload,
                notifiable: $notifiable
            );

            try {
                $response = $this->deliver(
                    channel: $channel,
                    recipient: $recipient,
                    message: $message,
                    subject: $subject,
                    payload: $payload,
                    event: $event,
                    notifiable: $notifiable
                );

                $success = (bool) ($response['status'] ?? false);

                $this->updateDeliveryLog(
                    log: $log,
                    success: $success,
                    response: $response
                );

                if ($success) {
                    $sent++;
                } else {
                    $failed++;
                }

                $results[$channel] = array_merge(
                    [
                        'channel' => $channel,
                        'status' => $success,
                    ],
                    $response
                );
            } catch (Throwable $exception) {
                $failed++;

                $this->markDeliveryException(
                    log: $log,
                    exception: $exception
                );

                Log::error('Central notification delivery failed.', [
                    'channel' => $channel,
                    'event' => $event,
                    'notifiable_type' => $notifiable?->getMorphClass(),
                    'notifiable_id' => $notifiable?->getKey(),
                    'error' => $exception->getMessage(),
                ]);

                $results[$channel] = [
                    'status' => false,
                    'channel' => $channel,
                    'message' => config('app.debug')
                        ? $exception->getMessage()
                        : 'Notification delivery failed.',
                ];
            }
        }

        return [
            'status' => $sent > 0,
            'message' => $sent > 0
                ? 'Notification delivery processed successfully.'
                : 'Notification delivery failed on all channels.',
            'event' => $event,
            'total' => count($channels),
            'sent' => $sent,
            'failed' => $failed,
            'results' => $results,
        ];
    }

    /**
     * Retry previously failed notification deliveries.
     */
    public function retryFailed(int $limit = 100): array
    {
        $limit = min(max($limit, 1), 500);

        $processed = 0;
        $sent = 0;
        $failed = 0;
        $results = [];

        NotificationDeliveryLog::query()
            ->where('status', 'failed')
            ->where('retry_count', '<', self::MAX_RETRY_COUNT)
            ->oldest('id')
            ->limit($limit)
            ->get()
            ->each(function (
                NotificationDeliveryLog $log
            ) use (
                &$processed,
                &$sent,
                &$failed,
                &$results
            ): void {
                $processed++;

                try {
                    $response = $this->deliver(
                        channel: (string) $log->channel,
                        recipient: $log->recipient,
                        message: (string) $log->message,
                        subject: $log->subject,
                        payload: is_array($log->payload)
                            ? $log->payload
                            : [],
                        event: (string) $log->event,
                        notifiable: null
                    );

                    $success = (bool) ($response['status'] ?? false);

                    $log->update([
                        'status' => $success ? 'sent' : 'failed',
                        'gateway_response' => $response,
                        'retry_count' => (int) $log->retry_count + 1,
                        'sent_at' => $success ? now() : null,
                        'failed_at' => $success ? null : now(),
                        'failure_reason' => $success
                            ? null
                            : (
                                $response['message']
                                ?? 'Notification retry failed.'
                            ),
                    ]);

                    if ($success) {
                        $sent++;
                    } else {
                        $failed++;
                    }

                    $results[] = [
                        'delivery_log_id' => $log->id,
                        'channel' => $log->channel,
                        'status' => $success,
                        'response' => $response,
                    ];
                } catch (Throwable $exception) {
                    $failed++;

                    $log->update([
                        'status' => 'failed',
                        'retry_count' => (int) $log->retry_count + 1,
                        'failed_at' => now(),
                        'failure_reason' => $exception->getMessage(),
                    ]);

                    Log::error('Notification retry failed.', [
                        'delivery_log_id' => $log->id,
                        'channel' => $log->channel,
                        'event' => $log->event,
                        'error' => $exception->getMessage(),
                    ]);

                    $results[] = [
                        'delivery_log_id' => $log->id,
                        'channel' => $log->channel,
                        'status' => false,
                        'message' => config('app.debug')
                            ? $exception->getMessage()
                            : 'Notification retry failed.',
                    ];
                }
            });

        return [
            'status' => $sent > 0,
            'processed' => $processed,
            'sent' => $sent,
            'failed' => $failed,
            'results' => $results,
        ];
    }

    /**
     * Send notification using fallback channels.
     *
     * Processing stops as soon as one channel succeeds.
     */
    public function sendWithFallback(
        string $event,
        array $channels,
        array $recipients,
        string $message,
        ?string $subject = null,
        array $payload = [],
        ?Model $notifiable = null
    ): array {
        $channels = $this->normalizeChannels($channels);
        $attempts = [];

        foreach ($channels as $channel) {
            $result = $this->send(
                event: $event,
                channels: [$channel],
                recipients: $recipients,
                message: $message,
                subject: $subject,
                payload: $payload,
                notifiable: $notifiable
            );

            $attempts[$channel] = $result;

            if ($result['status'] ?? false) {
                return [
                    'status' => true,
                    'message' => "Notification delivered through {$channel}.",
                    'delivered_channel' => $channel,
                    'attempts' => $attempts,
                ];
            }
        }

        return [
            'status' => false,
            'message' => 'Notification failed on all fallback channels.',
            'delivered_channel' => null,
            'attempts' => $attempts,
        ];
    }

    /**
     * Deliver notification to the selected channel.
     */
    private function deliver(
        string $channel,
        mixed $recipient,
        string $message,
        ?string $subject,
        array $payload,
        string $event,
        ?Model $notifiable = null
    ): array {
        return match ($channel) {
            'whatsapp' => $this->sendWhatsApp(
                number: (string) $recipient,
                message: $message,
                event: $event,
                payload: $payload,
                notifiable: $notifiable
            ),

            'database',
            'in_app' => $this->sendDatabase(
                recipient: $recipient,
                subject: $subject,
                message: $message,
                payload: $payload
            ),

            'push',
            'firebase' => $this->sendFirebase(
                token: (string) $recipient,
                subject: $subject,
                message: $message,
                payload: $payload
            ),

            'email' => $this->sendEmail(
                email: (string) $recipient,
                subject: $subject,
                message: $message,
                payload: $payload
            ),

            default => [
                'status' => false,
                'channel' => $channel,
                'message' => "Unsupported notification channel: {$channel}",
            ],
        };
    }

    /**
     * Send WhatsApp notification using existing WhatsAppService.
     */
    private function sendWhatsApp(
        string $number,
        string $message,
        string $event,
        array $payload = [],
        ?Model $notifiable = null
    ): array {
        $number = trim($number);

        if ($number === '') {
            return [
                'status' => false,
                'channel' => 'whatsapp',
                'message' => 'WhatsApp number is required.',
            ];
        }

        if (! class_exists(WhatsAppService::class)) {
            return [
                'status' => false,
                'channel' => 'whatsapp',
                'message' => 'WhatsAppService is unavailable.',
            ];
        }

        $template = $this->resolveWhatsAppTemplate($event, $payload);

        if ($template) {
            $bodyParameters = $this->resolveWhatsAppBodyParameters(
                template: $template,
                payload: $payload,
                notifiable: $notifiable
            );

            $headerParameters = $this->buildWhatsAppHeaderParameters(
                $template
            );

            $buttonParameters = $this->buildWhatsAppButtonParameters(
                $template,
                $payload
            );

            $result = WhatsAppService::sendTemplate(
                number: $number,
                templateName: $template->template_name,
                languageCode: $template->language ?: 'en',
                bodyParameters: $bodyParameters,
                headerParameters: $headerParameters,
                buttonParameters: $buttonParameters
            );

            return array_merge(
                [
                    'channel' => 'whatsapp',
                    'delivery_type' => 'template',
                    'template_id' => $template->id,
                    'template_name' => $template->template_name,
                ],
                is_array($result)
                    ? $result
                    : [
                        'status' => (bool) $result,
                        'message' => $result
                            ? 'WhatsApp template sent successfully.'
                            : 'WhatsApp template delivery failed.',
                    ]
            );
        }

        /*
         * Approved database template na mile to existing plain-text flow
         * preserve rakha gaya hai. Customer ke 24-hour WhatsApp window ke
         * bahar Meta plain text ko reject kar sakta hai.
         */
        if (method_exists(WhatsAppService::class, 'sendMessage')) {
            $result = WhatsAppService::sendMessage(
                $number,
                $message
            );

            return is_array($result)
                ? array_merge([
                    'channel' => 'whatsapp',
                    'delivery_type' => 'text_fallback',
                ], $result)
                : [
                    'status' => (bool) $result,
                    'channel' => 'whatsapp',
                    'delivery_type' => 'text_fallback',
                    'message' => $result
                        ? 'WhatsApp sent successfully.'
                        : 'WhatsApp delivery failed.',
                ];
        }

        $sent = WhatsAppService::send(
            $number,
            $message
        );

        return [
            'status' => $sent,
            'channel' => 'whatsapp',
            'delivery_type' => 'text_fallback',
            'message' => $sent
                ? 'WhatsApp sent successfully.'
                : 'WhatsApp delivery failed.',
        ];
    }

    private function resolveWhatsAppTemplate(
        string $event,
        array $payload
    ): ?WhatsAppTemplate {
        $explicitTemplateName = trim((string) (
            $payload['whatsapp_template']
            ?? $payload['template_name']
            ?? ''
        ));

        $templateName = $explicitTemplateName !== ''
            ? $explicitTemplateName
            : $this->templateNameForEvent($event, $payload);

        if ($templateName === null) {
            return null;
        }

        try {
            return WhatsAppTemplate::query()
                ->where('template_name', $templateName)
                ->where('is_active', true)
                ->where('status', WhatsAppTemplate::STATUS_ACTIVE)
                ->where(
                    'meta_status',
                    WhatsAppTemplate::META_STATUS_APPROVED
                )
                ->first();
        } catch (Throwable $exception) {
            Log::error('WhatsApp database template resolution failed.', [
                'event' => $event,
                'template_name' => $templateName,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function templateNameForEvent(
        string $event,
        array $payload
    ): ?string {
        $event = strtolower(trim($event));
        $bookingType = strtolower(trim((string) (
            $payload['booking_type']
            ?? $payload['service_type']
            ?? ''
        )));

        if (
            str_contains($bookingType, 'self')
            || str_starts_with($event, 'self_drive_')
            || str_starts_with($event, 'selfdrive_')
        ) {
            return match ($event) {
                'booking_pending',
                'booking_created',
                'booking_received',
                'self_drive_booking_received',
                'selfdrive_booking_received' =>
                    'selfdrive_booking_received_v1',

                'booking_confirmed',
                'self_drive_booking_confirmed',
                'selfdrive_booking_confirmed' =>
                    'selfdrive_booking_confirmed_v1',

                'self_drive_pickup_reminder',
                'selfdrive_pickup_reminder' =>
                    'selfdrive_pickup_reminder_v1',

                'self_drive_return_reminder',
                'selfdrive_return_reminder' =>
                    'selfdrive_return_reminder_v1',

                'security_refunded',
                'self_drive_security_refunded' =>
                    'security_refunded_v1',

                default => null,
            };
        }

        return match ($event) {
            'booking_pending',
            'booking_created',
            'booking_received' =>
                'booking_received_v1',

            'booking_confirmed' =>
                'booking_confirmed_v1',

            'booking_cancelled',
            'booking_canceled' =>
                'booking_cancelled_v1',

            'driver_assigned',
            'booking_driver_assigned' =>
                'driver_assigned_v1',

            'booking_running',
            'trip_started' =>
                'trip_started_v1',

            'booking_completed',
            'trip_completed' =>
                'trip_completed_v1',

            'payment_success',
            'payment_successful',
            'payment_paid',
            'payment_completed',
            'payment_received' =>
                'payment_received_v1',

            'payment_pending',
            'payment_reminder' =>
                'payment_reminder_v1',

            'invoice_ready',
            'invoice_generated' =>
                'invoice_ready_v1',

            'review_request',
            'feedback_request' =>
                'review_request_v1',

            'vendor_new_booking' =>
                'vendor_new_booking_v1',

            'admin_new_booking' =>
                'admin_new_booking_v1',

            default => null,
        };
    }

    private function resolveWhatsAppBodyParameters(
        WhatsAppTemplate $template,
        array $payload,
        ?Model $notifiable
    ): array {
        $variables = is_array($template->variables)
            ? $template->variables
            : [];

        usort(
            $variables,
            static fn (array $first, array $second): int =>
                (int) ($first['position'] ?? 0)
                <=>
                (int) ($second['position'] ?? 0)
        );

        $parameters = [];

        foreach ($variables as $index => $variable) {
            if (! is_array($variable)) {
                continue;
            }

            $key = trim((string) ($variable['key'] ?? ''));

            if ($key === '') {
                continue;
            }

            $value = $this->resolveWhatsAppVariableValue(
                key: $key,
                payload: $payload,
                notifiable: $notifiable
            );

            if ($this->isEmptyWhatsAppValue($value)) {
                $value = $variable['sample'] ?? '-';
            }

            $parameters[] = $this->stringWhatsAppValue($value);
        }

        return $parameters;
    }

    private function resolveWhatsAppVariableValue(
        string $key,
        array $payload,
        ?Model $notifiable
    ): mixed {
        $direct = data_get($payload, $key);

        if (! $this->isEmptyWhatsAppValue($direct)) {
            return $direct;
        }

        return match ($key) {
            'customer_name' => $this->firstWhatsAppValue([
                data_get($payload, 'name'),
                data_get($payload, 'customer.name'),
                $notifiable?->getAttribute('name'),
                'Customer',
            ]),

            'customer_mobile' => $this->firstWhatsAppValue([
                data_get($payload, 'mobile'),
                data_get($payload, 'phone'),
                data_get($payload, 'customer.mobile'),
                $notifiable?->getAttribute('mobile'),
                $notifiable?->getAttribute('phone'),
            ]),

            'booking_id' => $this->firstWhatsAppValue([
                data_get($payload, 'booking_no'),
                data_get($payload, 'booking_number'),
                data_get($payload, 'order_id'),
                data_get($payload, 'id'),
            ]),

            'service_type' => $this->firstWhatsAppValue([
                data_get($payload, 'service'),
                data_get($payload, 'booking_type'),
                data_get($payload, 'ride_type'),
                data_get($payload, 'trip_type'),
            ]),

            'vehicle_name' => $this->firstWhatsAppValue([
                data_get($payload, 'vehicle'),
                data_get($payload, 'car_name'),
                data_get($payload, 'product_name'),
                data_get($payload, 'vehicle.name'),
            ]),

            'vehicle_number' => $this->firstWhatsAppValue([
                data_get($payload, 'car_number'),
                data_get($payload, 'registration_number'),
                data_get($payload, 'vehicle.number'),
                data_get($payload, 'vehicle.vehicle_number'),
            ]),

            'pickup',
            'pickup_location' => $this->firstWhatsAppValue([
                data_get($payload, 'pickup_address'),
                data_get($payload, 'pickup_city'),
                data_get($payload, 'pickup_location'),
                data_get($payload, 'from'),
                data_get($payload, 'source'),
            ]),

            'drop' => $this->firstWhatsAppValue([
                data_get($payload, 'drop_city'),
                data_get($payload, 'drop_location'),
                data_get($payload, 'to'),
                data_get($payload, 'destination'),
                data_get($payload, 'return_location'),
            ]),

            'route' => $this->whatsAppRoute($payload),

            'travel_date' => $this->firstWhatsAppValue([
                data_get($payload, 'date'),
                data_get($payload, 'pickup_date'),
                data_get($payload, 'journey_date'),
                data_get($payload, 'start_date'),
            ]),

            'travel_time' => $this->firstWhatsAppValue([
                data_get($payload, 'time'),
                data_get($payload, 'pickup_time'),
                data_get($payload, 'journey_time'),
                data_get($payload, 'start_time'),
                data_get($payload, 'return_time'),
            ]),

            'driver_name' => $this->firstWhatsAppValue([
                data_get($payload, 'driver.name'),
                data_get($payload, 'driver'),
            ]),

            'driver_mobile' => $this->firstWhatsAppValue([
                data_get($payload, 'driver.mobile'),
                data_get($payload, 'driver.phone'),
                data_get($payload, 'driver_number'),
            ]),

            'total_amount' => $this->firstWhatsAppValue([
                data_get($payload, 'amount'),
                data_get($payload, 'grand_total'),
                data_get($payload, 'total'),
                data_get($payload, 'total_fare'),
            ]),

            'paid_amount' => $this->firstWhatsAppValue([
                data_get($payload, 'advance_amount'),
                data_get($payload, 'advance'),
                data_get($payload, 'amount_paid'),
            ]),

            'remaining_amount' => $this->firstWhatsAppValue([
                data_get($payload, 'pending_amount'),
                data_get($payload, 'balance_amount'),
                data_get($payload, 'due_amount'),
            ]),

            'payment_status',
            'refund_status' => $this->firstWhatsAppValue([
                data_get($payload, 'status'),
                data_get($payload, 'payment.status'),
                data_get($payload, 'refund.status'),
            ]),

            'payment_link' => $this->firstWhatsAppValue([
                data_get($payload, 'payment_url'),
                data_get($payload, 'checkout_link'),
                config('app.url'),
            ]),

            'invoice_link' => $this->firstWhatsAppValue([
                data_get($payload, 'invoice_url'),
                data_get($payload, 'invoice.link'),
            ]),

            'review_link' => $this->firstWhatsAppValue([
                data_get($payload, 'review_url'),
                data_get($payload, 'feedback_link'),
            ]),

            'otp' => $this->firstWhatsAppValue([
                data_get($payload, 'code'),
                data_get($payload, 'verification_code'),
            ]),

            'support_number' => $this->firstWhatsAppValue([
                data_get($payload, 'support_mobile'),
                '+91 70888 73331',
            ]),

            'cancellation_reason' => $this->firstWhatsAppValue([
                data_get($payload, 'reason'),
                data_get($payload, 'cancel_reason'),
            ]),

            'security_deposit' => $this->firstWhatsAppValue([
                data_get($payload, 'security'),
                data_get($payload, 'security_amount'),
            ]),

            'refund_amount' => $this->firstWhatsAppValue([
                data_get($payload, 'refund_amount'),
                data_get($payload, 'security_refund_amount'),
                data_get($payload, 'amount'),
            ]),

            'vendor_name' => $this->firstWhatsAppValue([
                data_get($payload, 'vendor.name'),
                data_get($payload, 'transporter_name'),
                $notifiable?->getAttribute('name'),
                'Vendor',
            ]),

            default => null,
        };
    }

    private function whatsAppRoute(array $payload): ?string
    {
        $pickup = $this->firstWhatsAppValue([
            data_get($payload, 'pickup'),
            data_get($payload, 'pickup_city'),
            data_get($payload, 'pickup_location'),
            data_get($payload, 'from'),
        ]);

        $drop = $this->firstWhatsAppValue([
            data_get($payload, 'drop'),
            data_get($payload, 'drop_city'),
            data_get($payload, 'drop_location'),
            data_get($payload, 'to'),
        ]);

        if (
            ! $this->isEmptyWhatsAppValue($pickup)
            && ! $this->isEmptyWhatsAppValue($drop)
        ) {
            return trim((string) $pickup)
                . ' to '
                . trim((string) $drop);
        }

        return $this->firstWhatsAppValue([
            data_get($payload, 'journey'),
            data_get($payload, 'trip_route'),
        ]);
    }

    private function buildWhatsAppHeaderParameters(
        WhatsAppTemplate $template
    ): array {
        $type = strtolower(trim((string) $template->header_type));
        $media = trim((string) $template->header_media);

        if (
            ! in_array($type, ['image', 'video', 'document'], true)
            || $media === ''
        ) {
            return [];
        }

        $parameter = [
            'type' => $type,
            $type => [
                'link' => $media,
            ],
        ];

        if ($type === 'document') {
            $parameter['document']['filename'] = basename(
                parse_url($media, PHP_URL_PATH) ?: 'document'
            );
        }

        return [$parameter];
    }

    private function buildWhatsAppButtonParameters(
        WhatsAppTemplate $template,
        array $payload
    ): array {
        $buttons = is_array($template->buttons)
            ? $template->buttons
            : [];

        $parameters = [];

        foreach ($buttons as $index => $button) {
            if (! is_array($button)) {
                continue;
            }

            $type = strtolower(trim((string) ($button['type'] ?? '')));
            $value = trim((string) ($button['value'] ?? ''));

            if (
                ! in_array($type, ['url', 'quick_reply'], true)
                || $value === ''
            ) {
                continue;
            }

            foreach ($payload as $key => $payloadValue) {
                if (! is_scalar($payloadValue)) {
                    continue;
                }

                $value = str_replace(
                    ['{' . $key . '}', '{{' . $key . '}}'],
                    (string) $payloadValue,
                    $value
                );
            }

            $parameters[] = [
                'sub_type' => $type,
                'index' => (string) $index,
                'value' => $value,
            ];
        }

        return $parameters;
    }

    private function firstWhatsAppValue(array $values): mixed
    {
        foreach ($values as $value) {
            if (! $this->isEmptyWhatsAppValue($value)) {
                return $value;
            }
        }

        return null;
    }

    private function isEmptyWhatsAppValue(mixed $value): bool
    {
        return $value === null
            || (is_string($value) && trim($value) === '');
    }

    private function stringWhatsAppValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value) || is_object($value)) {
            return json_encode(
                $value,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ) ?: '-';
        }

        return trim((string) $value) !== ''
            ? trim((string) $value)
            : '-';
    }

    /**
     * Send database/in-app notification.
     */
    private function sendDatabase(
        mixed $recipient,
        ?string $subject,
        string $message,
        array $payload
    ): array {
        if (
            ! is_object($recipient)
            || ! method_exists($recipient, 'notify')
        ) {
            return [
                'status' => false,
                'channel' => 'database',
                'message' => 'Database recipient must be a notifiable model.',
            ];
        }

        $notificationClass =
            \App\Notifications\GenericPlatformNotification::class;

        if (! class_exists($notificationClass)) {
            return [
                'status' => false,
                'channel' => 'database',
                'message' => 'GenericPlatformNotification class is missing.',
            ];
        }

        $recipient->notify(
            new $notificationClass(
                title: $subject ?? 'Dura Cabs',
                message: $message,
                payload: $payload
            )
        );

        return [
            'status' => true,
            'channel' => 'database',
            'message' => 'Database notification sent successfully.',
        ];
    }

    /**
     * Send Firebase notification using existing FirebaseService.
     */
    private function sendFirebase(
        string $token,
        ?string $subject,
        string $message,
        array $payload
    ): array {
        $token = trim($token);

        if ($token === '') {
            return [
                'status' => false,
                'channel' => 'push',
                'message' => 'Firebase device token is required.',
            ];
        }

        if (! class_exists(FirebaseService::class)) {
            return [
                'status' => false,
                'channel' => 'push',
                'message' => 'FirebaseService is unavailable.',
            ];
        }

        /** @var FirebaseService $firebaseService */
        $firebaseService = app(FirebaseService::class);

        $imageUrl = $payload['image_url']
            ?? $payload['image']
            ?? null;

        if (method_exists($firebaseService, 'sendNotification')) {
            $result = $firebaseService->sendNotification(
                deviceToken: $token,
                title: $subject ?? 'Dura Cabs',
                body: $message,
                data: $payload,
                imageUrl: filled($imageUrl)
                    ? (string) $imageUrl
                    : null
            );

            return is_array($result)
                ? array_merge(['channel' => 'push'], $result)
                : [
                    'status' => (bool) $result,
                    'channel' => 'push',
                ];
        }

        foreach (['sendToToken', 'send'] as $method) {
            if (! method_exists($firebaseService, $method)) {
                continue;
            }

            $result = $firebaseService->{$method}(
                $token,
                $subject ?? 'Dura Cabs',
                $message,
                $payload
            );

            return is_array($result)
                ? array_merge(['channel' => 'push'], $result)
                : [
                    'status' => (bool) $result,
                    'channel' => 'push',
                ];
        }

        return [
            'status' => false,
            'channel' => 'push',
            'message' => 'No supported Firebase send method found.',
        ];
    }

    /**
     * Send email notification using existing EmailService.
     */
    private function sendEmail(
        string $email,
        ?string $subject,
        string $message,
        array $payload = []
    ): array {
        $email = trim($email);

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'status' => false,
                'channel' => 'email',
                'message' => 'Valid email address is required.',
            ];
        }

        if (! class_exists(EmailService::class)) {
            return [
                'status' => false,
                'channel' => 'email',
                'message' => 'EmailService is unavailable.',
            ];
        }

        $isHtml = (bool) ($payload['is_html'] ?? false);

        $sent = EmailService::send(
            email: $email,
            subject: $subject ?? 'Dura Cabs',
            body: $message,
            isHtml: $isHtml
        );

        return [
            'status' => $sent,
            'channel' => 'email',
            'message' => $sent
                ? 'Email sent successfully.'
                : 'Email delivery failed.',
        ];
    }

    /**
     * Create pending notification delivery log.
     */
    private function createDeliveryLog(
        string $event,
        string $channel,
        mixed $recipient,
        ?string $subject,
        string $message,
        array $payload,
        ?Model $notifiable
    ): ?NotificationDeliveryLog {
        try {
            return NotificationDeliveryLog::query()->create([
                'notifiable_type' => $notifiable?->getMorphClass(),
                'notifiable_id' => $notifiable?->getKey(),
                'channel' => $channel,
                'recipient' => $this->recipientForLog($recipient),
                'event' => $event,
                'status' => 'pending',
                'subject' => $subject,
                'message' => $message,
                'payload' => $payload,
                'retry_count' => 0,
            ]);
        } catch (Throwable $exception) {
            Log::error('Notification delivery log creation failed.', [
                'event' => $event,
                'channel' => $channel,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Update notification log after a delivery attempt.
     */
    private function updateDeliveryLog(
        ?NotificationDeliveryLog $log,
        bool $success,
        array $response
    ): void {
        if (! $log) {
            return;
        }

        try {
            $log->update([
                'status' => $success ? 'sent' : 'failed',
                'gateway_response' => $response,
                'sent_at' => $success ? now() : null,
                'failed_at' => $success ? null : now(),
                'failure_reason' => $success
                    ? null
                    : (
                        $response['message']
                        ?? 'Notification delivery failed.'
                    ),
            ]);
        } catch (Throwable $exception) {
            Log::error('Notification delivery log update failed.', [
                'delivery_log_id' => $log->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Mark notification delivery as failed after an exception.
     */
    private function markDeliveryException(
        ?NotificationDeliveryLog $log,
        Throwable $exception
    ): void {
        if (! $log) {
            return;
        }

        try {
            $log->update([
                'status' => 'failed',
                'failed_at' => now(),
                'failure_reason' => $exception->getMessage(),
            ]);
        } catch (Throwable $logException) {
            Log::error('Notification exception log update failed.', [
                'delivery_log_id' => $log->id,
                'delivery_error' => $exception->getMessage(),
                'log_error' => $logException->getMessage(),
            ]);
        }
    }

    /**
     * Normalize and validate supplied notification channels.
     */
    private function normalizeChannels(array $channels): array
    {
        return collect($channels)
            ->filter(fn ($channel) => is_string($channel))
            ->map(fn ($channel) => strtolower(trim($channel)))
            ->map(fn ($channel) => match ($channel) {
                'fcm' => 'push',
                'notification' => 'database',
                default => $channel,
            })
            ->filter(
                fn ($channel) => in_array(
                    $channel,
                    self::SUPPORTED_CHANNELS,
                    true
                )
            )
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Resolve recipient from explicitly supplied recipients or notifiable model.
     */
    private function resolveRecipient(
        string $channel,
        array $recipients,
        ?Model $notifiable
    ): mixed {
        $aliases = match ($channel) {
            'push', 'firebase' => [
                'push',
                'firebase',
                'fcm',
                'device_token',
            ],

            'whatsapp' => [
                'whatsapp',
                'mobile',
                'phone',
            ],

            'email' => [
                'email',
            ],

            'database', 'in_app' => [
                'database',
                'in_app',
                'notification',
            ],

            default => [$channel],
        };

        foreach ($aliases as $key) {
            if (
                array_key_exists($key, $recipients)
                && $recipients[$key] !== null
                && $recipients[$key] !== ''
            ) {
                return $recipients[$key];
            }
        }

        if (! $notifiable) {
            return null;
        }

        return match ($channel) {
            'push',
            'firebase' => $notifiable->getAttribute('device_token'),

            'whatsapp' => $notifiable->getAttribute('mobile')
                ?? $notifiable->getAttribute('phone'),

            'email' => $notifiable->getAttribute('email'),

            'database',
            'in_app' => $notifiable,

            default => null,
        };
    }

    /**
     * Check whether channel recipient is usable.
     */
    private function hasValidRecipient(
        string $channel,
        mixed $recipient
    ): bool {
        if (in_array($channel, ['database', 'in_app'], true)) {
            return is_object($recipient)
                && method_exists($recipient, 'notify');
        }

        if (! is_scalar($recipient)) {
            return false;
        }

        $recipient = trim((string) $recipient);

        if ($recipient === '') {
            return false;
        }

        if ($channel === 'email') {
            return filter_var(
                $recipient,
                FILTER_VALIDATE_EMAIL
            ) !== false;
        }

        return true;
    }

    /**
     * Convert recipient value into safe delivery-log value.
     */
    private function recipientForLog(mixed $recipient): ?string
    {
        if (is_scalar($recipient)) {
            return trim((string) $recipient);
        }

        if ($recipient instanceof Model) {
            return sprintf(
                '%s:%s',
                $recipient->getMorphClass(),
                (string) $recipient->getKey()
            );
        }

        return null;
    }
}