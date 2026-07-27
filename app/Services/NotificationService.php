<?php

namespace App\Services;

use App\Http\Resources\Api\V1\NotificationResource;
use App\Models\User;
use App\Repositories\NotificationRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotificationService
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository,
        private readonly FirebaseService $firebaseService,
        private readonly NotificationManagerService $notificationManagerService,
        private readonly BookingNotificationContentService $bookingNotificationContentService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Notification Listing
    |--------------------------------------------------------------------------
    */

    public function list(
        int|string|null $userId = null,
        ?string $mobile = null
    ): array {
        return NotificationResource::collection(
            $this->notificationRepository->list($userId, $mobile)
        )->resolve();
    }

    /*
    |--------------------------------------------------------------------------
    | Mark Notifications As Read
    |--------------------------------------------------------------------------
    */

    public function markAsRead(
        int|string|null $notificationId = null,
        int|string|null $userId = null,
        ?string $mobile = null
    ): void {
        $this->notificationRepository->markAsRead(
            $notificationId,
            $userId,
            $mobile
        );
    }

    public function markAllAsRead(
        int|string|null $userId = null,
        ?string $mobile = null
    ): void {
        $this->notificationRepository->markAsRead(
            null,
            $userId,
            $mobile
        );
    }

    public function unreadCount(
        int|string|null $userId = null,
        ?string $mobile = null
    ): int {
        if (! method_exists($this->notificationRepository, 'unreadCount')) {
            return 0;
        }

        try {
            return (int) $this->notificationRepository->unreadCount(
                $userId,
                $mobile
            );
        } catch (Throwable $e) {
            Log::error('Unread notification count failed.', [
                'user_id' => $userId,
                'mobile' => $mobile,
                'message' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Store Repository-Based In-App Notification
    |--------------------------------------------------------------------------
    */

    public function create(array $data): bool
    {
        if (! method_exists($this->notificationRepository, 'create')) {
            Log::warning(
                'NotificationRepository create method is unavailable.'
            );

            return false;
        }

        try {
            return (bool) $this->notificationRepository->create(
                $this->prepareDatabaseData($data)
            );
        } catch (Throwable $e) {
            Log::error('Database notification create failed.', [
                'message' => $e->getMessage(),
                'data' => $data,
            ]);

            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Firebase Push
    |--------------------------------------------------------------------------
    */

    public function sendPush(
        ?string $deviceToken,
        string $title,
        string $message,
        array $data = []
    ): bool {
        return (bool) ($this->sendPushDetailed(
            deviceToken: $deviceToken,
            title: $title,
            message: $message,
            data: $data
        )['status'] ?? false);
    }

    public function sendPushDetailed(
        ?string $deviceToken,
        string $title,
        string $message,
        array $data = [],
        ?string $imageUrl = null
    ): array {
        $deviceToken = filled($deviceToken)
            ? trim((string) $deviceToken)
            : '';

        if ($deviceToken === '') {
            return [
                'status' => false,
                'message' => 'Device token is required.',
                'invalid_token' => false,
            ];
        }

        try {
            $result = $this->firebaseService->sendNotification(
                deviceToken: $deviceToken,
                title: trim($title),
                body: trim($message),
                data: $this->normalizePushData($data),
                imageUrl: $imageUrl
            );

            if (! $this->isSuccessfulResult($result)) {
                Log::warning('Firebase notification failed.', [
                    'message' => $result['message'] ?? null,
                    'error_code' => $result['error_code'] ?? null,
                    'invalid_token' => $result['invalid_token'] ?? false,
                    'response' => $result['response'] ?? null,
                ]);
            }

            return is_array($result)
                ? $result
                : [
                    'status' => (bool) $result,
                    'invalid_token' => false,
                ];
        } catch (Throwable $e) {
            Log::error('Firebase push notification exception.', [
                'message' => $e->getMessage(),
            ]);

            return [
                'status' => false,
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'Push notification failed.',
                'invalid_token' => false,
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Send To One User (Repository In-App + Push)
    |--------------------------------------------------------------------------
    */

    public function sendToUser(
        User|int|string $user,
        string $title,
        string $message,
        array $data = [],
        bool $saveInDatabase = true
    ): array {
        $resolvedUser = $this->resolveUser($user);

        if (! $resolvedUser) {
            return [
                'status' => false,
                'message' => 'User not found.',
                'database_saved' => false,
                'push_sent' => false,
            ];
        }

        $title = trim($title) !== '' ? trim($title) : 'Dura Cabs';
        $message = trim($message);

        if ($message === '') {
            return [
                'status' => false,
                'message' => 'Notification message is required.',
                'user_id' => $resolvedUser->id,
                'database_saved' => false,
                'push_sent' => false,
            ];
        }

        $payload = $this->prepareNotificationPayload(
            user: $resolvedUser,
            title: $title,
            message: $message,
            data: $data
        );

        $databaseSaved = $saveInDatabase
            ? $this->create($payload)
            : false;

        $pushResult = $this->sendPushDetailed(
            deviceToken: $resolvedUser->device_token,
            title: $title,
            message: $message,
            data: array_merge($data, [
                'user_id' => (string) $resolvedUser->id,
            ]),
            imageUrl: $this->extractImageUrl($data)
        );

        $pushSent = $this->isSuccessfulResult($pushResult);
        $invalidTokenRemoved = false;

        if (
            ! $pushSent
            && filled($resolvedUser->device_token)
            && ($pushResult['invalid_token'] ?? false)
        ) {
            $invalidTokenRemoved = $this->removeDeviceToken($resolvedUser);
        }

        return [
            'status' => $databaseSaved || $pushSent,
            'message' => match (true) {
                $databaseSaved && $pushSent =>
                    'Notification saved and push delivered successfully.',
                $databaseSaved =>
                    'Notification saved, but push was not delivered.',
                $pushSent =>
                    'Push notification delivered successfully.',
                default => 'Notification delivery failed.',
            },
            'user_id' => $resolvedUser->id,
            'database_saved' => $databaseSaved,
            'push_sent' => $pushSent,
            'invalid_token_removed' => $invalidTokenRemoved,
            'push_response' => $pushResult,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Central Multi-Channel Delivery
    |--------------------------------------------------------------------------
    */

    public function sendMultiChannel(
        User|int|string $user,
        string $event,
        string $title,
        string $message,
        array $channels = ['push'],
        array $data = [],
        bool $saveInDatabase = true,
        bool $fallback = false
    ): array {
        $resolvedUser = $this->resolveUser($user);

        if (! $resolvedUser) {
            return [
                'status' => false,
                'message' => 'User not found.',
                'database_saved' => false,
                'delivery' => [],
            ];
        }

        $channels = $this->normalizeManagerChannels($channels);
        $databaseSaved = false;

        if ($saveInDatabase) {
            $databaseSaved = $this->create(
                $this->prepareNotificationPayload(
                    user: $resolvedUser,
                    title: $title,
                    message: $message,
                    data: $data
                )
            );
        }

        // Repository-based notification above is the app's in-app source.
        // Avoid sending a second Laravel database notification here.
        $deliveryChannels = array_values(array_filter(
            $channels,
            fn (string $channel) => ! in_array(
                $channel,
                ['database', 'in_app'],
                true
            )
        ));

        if ($deliveryChannels === []) {
            return [
                'status' => $databaseSaved,
                'message' => $databaseSaved
                    ? 'In-app notification saved successfully.'
                    : 'No external notification channel supplied.',
                'user_id' => $resolvedUser->id,
                'database_saved' => $databaseSaved,
                'delivery' => [],
            ];
        }

        $recipients = [
            'push' => $resolvedUser->device_token,
            'firebase' => $resolvedUser->device_token,
            'whatsapp' => $resolvedUser->mobile,
            'mobile' => $resolvedUser->mobile,
            'email' => $this->validRealEmail($resolvedUser->email),
        ];

        $managerPayload = array_merge($data, [
            'user_id' => (string) $resolvedUser->id,
        ]);

        try {
            $delivery = $fallback
                ? $this->notificationManagerService->sendWithFallback(
                    event: $event,
                    channels: $deliveryChannels,
                    recipients: $recipients,
                    message: $message,
                    subject: $title,
                    payload: $managerPayload,
                    notifiable: $resolvedUser
                )
                : $this->notificationManagerService->send(
                    event: $event,
                    channels: $deliveryChannels,
                    recipients: $recipients,
                    message: $message,
                    subject: $title,
                    payload: $managerPayload,
                    notifiable: $resolvedUser
                );

            return [
                'status' => $databaseSaved || ($delivery['status'] ?? false),
                'message' => ($delivery['status'] ?? false)
                    ? 'Notification processed successfully.'
                    : ($databaseSaved
                        ? 'In-app notification saved; external delivery failed.'
                        : 'Notification delivery failed.'),
                'user_id' => $resolvedUser->id,
                'database_saved' => $databaseSaved,
                'delivery' => $delivery,
            ];
        } catch (Throwable $e) {
            Log::error('Multi-channel notification failed.', [
                'user_id' => $resolvedUser->id,
                'event' => $event,
                'channels' => $deliveryChannels,
                'message' => $e->getMessage(),
            ]);

            return [
                'status' => $databaseSaved,
                'message' => $databaseSaved
                    ? 'In-app notification saved; external delivery failed.'
                    : 'Notification delivery failed.',
                'user_id' => $resolvedUser->id,
                'database_saved' => $databaseSaved,
                'delivery' => [
                    'status' => false,
                    'message' => config('app.debug')
                        ? $e->getMessage()
                        : 'External notification delivery failed.',
                ],
            ];
        }
    }

    public function sendToCustomer(
        User|int|string $customer,
        string $title,
        string $message,
        array $data = [],
        array $channels = ['push'],
        bool $saveInDatabase = true,
        bool $fallback = false
    ): array {
        return $this->sendMultiChannel(
            user: $customer,
            event: (string) ($data['event'] ?? 'customer_notification'),
            title: $title,
            message: $message,
            channels: $channels,
            data: array_merge(['audience' => 'customer'], $data),
            saveInDatabase: $saveInDatabase,
            fallback: $fallback
        );
    }

    public function sendToDriver(
        User|int|string $driver,
        string $title,
        string $message,
        array $data = [],
        array $channels = ['push'],
        bool $saveInDatabase = true,
        bool $fallback = false
    ): array {
        return $this->sendMultiChannel(
            user: $driver,
            event: (string) ($data['event'] ?? 'driver_notification'),
            title: $title,
            message: $message,
            channels: $channels,
            data: array_merge(['audience' => 'driver'], $data),
            saveInDatabase: $saveInDatabase,
            fallback: $fallback
        );
    }

    public function sendToVendor(
        User|int|string $vendor,
        string $title,
        string $message,
        array $data = [],
        array $channels = ['push'],
        bool $saveInDatabase = true,
        bool $fallback = false
    ): array {
        return $this->sendMultiChannel(
            user: $vendor,
            event: (string) ($data['event'] ?? 'vendor_notification'),
            title: $title,
            message: $message,
            channels: $channels,
            data: array_merge(['audience' => 'vendor'], $data),
            saveInDatabase: $saveInDatabase,
            fallback: $fallback
        );
    }

    public function sendToAdmin(
        User|int|string $admin,
        string $title,
        string $message,
        array $data = [],
        array $channels = ['push'],
        bool $saveInDatabase = true,
        bool $fallback = false
    ): array {
        return $this->sendMultiChannel(
            user: $admin,
            event: (string) ($data['event'] ?? 'admin_notification'),
            title: $title,
            message: $message,
            channels: $channels,
            data: array_merge(['audience' => 'admin'], $data),
            saveInDatabase: $saveInDatabase,
            fallback: $fallback
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Send To Multiple Users
    |--------------------------------------------------------------------------
    */

    public function sendToUsers(
        iterable $users,
        string $title,
        string $message,
        array $data = [],
        bool $saveInDatabase = true
    ): array {
        $collection = $this->resolveUsers($users);

        $summary = [
            'status' => false,
            'total' => $collection->count(),
            'database_saved' => 0,
            'push_sent' => 0,
            'failed' => 0,
            'results' => [],
        ];

        foreach ($collection as $user) {
            $result = $this->sendToUser(
                user: $user,
                title: $title,
                message: $message,
                data: $data,
                saveInDatabase: $saveInDatabase
            );

            if ($result['database_saved'] ?? false) {
                $summary['database_saved']++;
            }

            if ($result['push_sent'] ?? false) {
                $summary['push_sent']++;
            }

            if (! ($result['status'] ?? false)) {
                $summary['failed']++;
            }

            $summary['results'][] = $result;
        }

        $summary['status'] =
            $summary['database_saved'] > 0
            || $summary['push_sent'] > 0;

        return $summary;
    }

    public function sendToAllUsers(
        string $title,
        string $message,
        array $data = [],
        bool $saveInDatabase = true
    ): array {
        $query = User::query();

        if ($this->userHasAttributeColumn('is_active')) {
            $query->where('is_active', true);
        }

        return $this->sendToUsers(
            users: $query->get(),
            title: $title,
            message: $message,
            data: $data,
            saveInDatabase: $saveInDatabase
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Firebase Topic
    |--------------------------------------------------------------------------
    */

    public function sendToTopic(
        string $topic,
        string $title,
        string $message,
        array $data = []
    ): array {
        $topic = trim($topic);

        if ($topic === '') {
            return [
                'status' => false,
                'message' => 'Notification topic is required.',
            ];
        }

        try {
            $result = $this->firebaseService->sendToTopic(
                topic: $topic,
                title: $title,
                body: $message,
                data: $this->normalizePushData($data),
                imageUrl: $this->extractImageUrl($data)
            );

            return [
                'status' => $this->isSuccessfulResult($result),
                'message' => $this->isSuccessfulResult($result)
                    ? 'Topic notification sent successfully.'
                    : ($result['message'] ?? 'Topic notification failed.'),
                'response' => $result,
            ];
        } catch (Throwable $e) {
            Log::error('Topic notification failed.', [
                'topic' => $topic,
                'message' => $e->getMessage(),
            ]);

            return [
                'status' => false,
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'Topic notification failed.',
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Booking Notification
    |--------------------------------------------------------------------------
    */

    public function sendBookingNotification(
        User|int|string $user,
        string $bookingId,
        string $bookingStatus,
        ?string $title = null,
        ?string $message = null,
        string $bookingType = 'taxi',
        array $extraData = []
    ): array {
        $status = $this->normalizeBookingStatus($bookingStatus);
        $content = $this->bookingNotificationContent($status, $bookingId);

        return $this->sendToUser(
            user: $user,
            title: $title ?? $content['title'],
            message: $message ?? $content['message'],
            data: array_merge([
                'type' => 'booking',
                'event' => 'booking_' . $status,
                'booking_id' => (string) $bookingId,
                'booking_status' => $status,
                'booking_type' => $bookingType,
                'click_action' => 'OPEN_BOOKING',
            ], $extraData)
        );
    }

    public function sendBookingMultiChannel(
        User|int|string $user,
        string $bookingId,
        string $bookingStatus,
        array $channels = ['push', 'whatsapp', 'email'],
        ?string $title = null,
        ?string $message = null,
        string $bookingType = 'taxi',
        array $extraData = [],
        bool $fallback = false
    ): array {
        $status = $this->normalizeBookingStatus($bookingStatus);
        $content = $this->bookingNotificationContent($status, $bookingId);

        return $this->sendMultiChannel(
            user: $user,
            event: 'booking_' . $status,
            title: $title ?? $content['title'],
            message: $message ?? $content['message'],
            channels: $channels,
            data: array_merge([
                'type' => 'booking',
                'booking_id' => (string) $bookingId,
                'booking_status' => $status,
                'booking_type' => $bookingType,
                'click_action' => 'OPEN_BOOKING',
            ], $extraData),
            saveInDatabase: true,
            fallback: $fallback
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Offer, Payment And Refund Notifications
    |--------------------------------------------------------------------------
    */

    public function sendOfferNotification(
        User|int|string $user,
        string $title,
        string $message,
        int|string|null $offerId = null,
        array $extraData = []
    ): array {
        return $this->sendToUser(
            user: $user,
            title: $title,
            message: $message,
            data: array_merge([
                'type' => 'offer',
                'event' => 'offer_notification',
                'offer_id' => $offerId !== null
                    ? (string) $offerId
                    : '',
                'click_action' => 'OPEN_OFFER',
            ], $extraData)
        );
    }

    public function sendPaymentNotification(
        User|int|string $user,
        string $paymentStatus,
        int|float|string $amount,
        ?string $bookingId = null,
        array $extraData = []
    ): array {
        $status = strtolower(trim($paymentStatus));
        $isSuccess = in_array(
            $status,
            ['success', 'successful', 'paid', 'completed'],
            true
        );

        $title = $isSuccess
            ? 'Payment Successful'
            : 'Payment Update';

        $message = $isSuccess
            ? 'Your payment of ₹' . $amount . ' was successful.'
            : 'Your payment status is ' . ucfirst($status) . '.';

        return $this->sendToUser(
            user: $user,
            title: $title,
            message: $message,
            data: array_merge([
                'type' => 'payment',
                'event' => 'payment_' . ($status ?: 'update'),
                'payment_status' => $status,
                'amount' => (string) $amount,
                'booking_id' => $bookingId ?? '',
                'click_action' => 'OPEN_PAYMENT',
            ], $extraData)
        );
    }

    public function sendRefundNotification(
        User|int|string $user,
        int|float|string $amount,
        ?string $bookingId = null,
        string $refundStatus = 'processed',
        array $extraData = []
    ): array {
        $status = strtolower(trim($refundStatus));

        return $this->sendToUser(
            user: $user,
            title: 'Refund Update',
            message: 'Your refund of ₹'
                . $amount
                . ' is '
                . ucfirst($status ?: 'processed')
                . '.',
            data: array_merge([
                'type' => 'refund',
                'event' => 'refund_' . ($status ?: 'processed'),
                'refund_status' => $status ?: 'processed',
                'amount' => (string) $amount,
                'booking_id' => $bookingId ?? '',
                'click_action' => 'OPEN_REFUND',
            ], $extraData)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Device Token Management
    |--------------------------------------------------------------------------
    */

    public function updateDeviceToken(
        User $user,
        ?string $deviceToken
    ): bool {
        try {
            $token = filled($deviceToken)
                ? trim((string) $deviceToken)
                : null;

            if (method_exists($this->firebaseService, 'saveDeviceToken')) {
                return $this->firebaseService->saveDeviceToken(
                    $user,
                    $token
                );
            }

            $user->forceFill([
                'device_token' => $token,
            ])->save();

            return true;
        } catch (Throwable $e) {
            Log::error('Device token update failed.', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function removeDeviceToken(User $user): bool
    {
        return $this->updateDeviceToken($user, null);
    }

    public function removeInvalidDeviceTokens(array $tokens): int
    {
        if (method_exists($this->firebaseService, 'removeInvalidTokens')) {
            return $this->firebaseService->removeInvalidTokens($tokens);
        }

        return 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Private Helpers
    |--------------------------------------------------------------------------
    */

    private function resolveUser(User|int|string $user): ?User
    {
        if ($user instanceof User) {
            return $user;
        }

        if (is_numeric($user)) {
            return User::query()->find((int) $user);
        }

        $value = trim((string) $user);

        if ($value === '') {
            return null;
        }

        $cleanMobile = preg_replace('/\D+/', '', $value);

        return User::query()
            ->where(function ($query) use ($value, $cleanMobile): void {
                $query->where('email', $value);

                if ($cleanMobile !== '') {
                    $query->orWhere('mobile', $cleanMobile);
                }
            })
            ->first();
    }

    private function resolveUsers(iterable $users): Collection
    {
        return collect($users)
            ->map(fn ($user) => $this->resolveUser($user))
            ->filter(fn ($user) => $user instanceof User)
            ->unique('id')
            ->values();
    }

    private function prepareNotificationPayload(
        User $user,
        string $title,
        string $message,
        array $data = []
    ): array {
        return [
            'user_id' => $user->id,
            'mobile' => $user->mobile,
            'title' => $title,
            'message' => $message,
            'body' => $message,
            'type' => $data['type'] ?? 'general',
            'data' => $data,
            'is_read' => false,
            'read_at' => null,
        ];
    }

    private function prepareDatabaseData(array $data): array
    {
        $message = trim((string) (
            $data['message']
            ?? $data['body']
            ?? ''
        ));

        return [
            'user_id' => $data['user_id'] ?? null,
            'mobile' => $data['mobile'] ?? null,
            'title' => $data['title'] ?? 'Dura Cabs',
            'message' => $message,
            'body' => $message,
            'type' => $data['type'] ?? 'general',
            'data' => is_array($data['data'] ?? null)
                ? $data['data']
                : [],
            'is_read' => (bool) ($data['is_read'] ?? false),
            'read_at' => $data['read_at'] ?? null,
        ];
    }

    private function normalizePushData(array $data): array
    {
        $normalized = [];

        foreach ($data as $key => $value) {
            $key = trim((string) $key);

            if ($key === '') {
                continue;
            }

            if ($value === null) {
                $normalized[$key] = '';
                continue;
            }

            if (is_bool($value)) {
                $normalized[$key] = $value ? 'true' : 'false';
                continue;
            }

            if (is_array($value) || is_object($value)) {
                $normalized[$key] = json_encode(
                    $value,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ) ?: '';

                continue;
            }

            $normalized[$key] = (string) $value;
        }

        return $normalized;
    }

    private function normalizeManagerChannels(array $channels): array
    {
        return collect($channels)
            ->filter(fn ($channel) => is_string($channel))
            ->map(fn ($channel) => strtolower(trim($channel)))
            ->map(fn ($channel) => match ($channel) {
                'fcm' => 'push',
                'notification' => 'database',
                default => $channel,
            })
            ->filter(fn ($channel) => in_array($channel, [
                'push',
                'firebase',
                'whatsapp',
                'email',
                'database',
                'in_app',
            ], true))
            ->unique()
            ->values()
            ->all();
    }

    private function isSuccessfulResult(mixed $result): bool
    {
        if (is_bool($result)) {
            return $result;
        }

        return is_array($result)
            && (bool) ($result['status'] ?? false);
    }

    private function extractImageUrl(array $data): ?string
    {
        $image = $data['image_url']
            ?? $data['image']
            ?? null;

        return filled($image) ? trim((string) $image) : null;
    }

    private function validRealEmail(mixed $email): ?string
    {
        $email = trim((string) $email);

        if (
            $email === ''
            || str_ends_with(strtolower($email), '@duracabs.local')
            || ! filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            return null;
        }

        return $email;
    }

    private function normalizeBookingStatus(string $status): string
    {
        $status = strtolower(trim($status));

        return match ($status) {
            'confirm' => 'confirmed',
            'cancel', 'canceled' => 'cancelled',
            'new', 'received' => 'pending',
            'run', 'in_progress', 'in-progress' => 'running',
            'complete', 'closed' => 'completed',
            default => $status !== '' ? $status : 'pending',
        };
    }

    private function bookingNotificationContent(
        string $status,
        string $bookingId
    ): array {
        return $this->bookingNotificationContentService->make(
            status: $status,
            bookingId: $bookingId
        );
    }

    private function userHasAttributeColumn(string $column): bool
    {
        try {
            return in_array(
                $column,
                (new User())->getConnection()
                    ->getSchemaBuilder()
                    ->getColumnListing((new User())->getTable()),
                true
            );
        } catch (Throwable) {
            return false;
        }
    }
}