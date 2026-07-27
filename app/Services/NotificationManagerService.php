<?php

namespace App\Services;

use App\Models\NotificationDeliveryLog;
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
                    payload: $payload
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
                            : []
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
        array $payload
    ): array {
        return match ($channel) {
            'whatsapp' => $this->sendWhatsApp(
                number: (string) $recipient,
                message: $message
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
        string $message
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

        if (method_exists(WhatsAppService::class, 'sendMessage')) {
            $result = WhatsAppService::sendMessage(
                $number,
                $message
            );

            return is_array($result)
                ? array_merge(['channel' => 'whatsapp'], $result)
                : [
                    'status' => (bool) $result,
                    'channel' => 'whatsapp',
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
            'message' => $sent
                ? 'WhatsApp sent successfully.'
                : 'WhatsApp delivery failed.',
        ];
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