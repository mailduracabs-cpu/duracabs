<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Notification List
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'mobile' => ['nullable', 'string', 'max:20'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->first());
        }

        try {
            [$userId, $mobile] = $this->resolveNotificationOwner($request);

            if (! $userId && blank($mobile)) {
                return response()->json([
                    'status' => false,
                    'message' => 'User identification is required.',
                    'data' => [],
                ], 422);
            }

            return response()->json([
                'status' => true,
                'message' => 'Notifications fetched successfully.',
                'data' => $this->notificationService->list(
                    $userId,
                    $mobile
                ),
            ]);
        } catch (Throwable $e) {
            Log::error('Notification list failed.', [
                'message' => $e->getMessage(),
                'user_id' => $request->user()?->id
                    ?? $request->input('user_id'),
                'mobile' => $request->input('mobile'),
            ]);

            return $this->serverError(
                'Unable to fetch notifications.',
                $e
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Mark Single Notification As Read
    |--------------------------------------------------------------------------
    */

    public function read(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notification_id' => [
                'required',
                'integer',
            ],
            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'mobile' => [
                'nullable',
                'string',
                'max:20',
            ],
        ]);

        if ($validator->fails()) {
            return $this->validationError(
                $validator->errors()->first()
            );
        }

        try {
            [$userId, $mobile] = $this->resolveNotificationOwner(
                $request
            );

            if (! $userId && blank($mobile)) {
                return response()->json([
                    'status' => false,
                    'message' => 'User identification is required.',
                ], 422);
            }

            $this->notificationService->markAsRead(
                notificationId: $request->integer('notification_id'),
                userId: $userId,
                mobile: $mobile
            );

            return response()->json([
                'status' => true,
                'message' => 'Notification marked as read.',
            ]);
        } catch (Throwable $e) {
            Log::error('Notification read failed.', [
                'notification_id' =>
                    $request->input('notification_id'),
                'message' => $e->getMessage(),
            ]);

            return $this->serverError(
                'Unable to update notification.',
                $e
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Mark All Notifications As Read
    |--------------------------------------------------------------------------
    */

    public function readAll(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'mobile' => [
                'nullable',
                'string',
                'max:20',
            ],
        ]);

        if ($validator->fails()) {
            return $this->validationError(
                $validator->errors()->first()
            );
        }

        try {
            [$userId, $mobile] = $this->resolveNotificationOwner(
                $request
            );

            if (! $userId && blank($mobile)) {
                return response()->json([
                    'status' => false,
                    'message' => 'User identification is required.',
                ], 422);
            }

            $this->notificationService->markAllAsRead(
                userId: $userId,
                mobile: $mobile
            );

            return response()->json([
                'status' => true,
                'message' => 'All notifications marked as read.',
                'data' => [
                    'unread_count' => 0,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Notification read-all failed.', [
                'user_id' => $request->user()?->id
                    ?? $request->input('user_id'),
                'mobile' => $request->input('mobile'),
                'message' => $e->getMessage(),
            ]);

            return $this->serverError(
                'Unable to mark notifications as read.',
                $e
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Unread Notification Count
    |--------------------------------------------------------------------------
    */

    public function count(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'mobile' => [
                'nullable',
                'string',
                'max:20',
            ],
        ]);

        if ($validator->fails()) {
            return $this->validationError(
                $validator->errors()->first()
            );
        }

        try {
            [$userId, $mobile] = $this->resolveNotificationOwner(
                $request
            );

            if (! $userId && blank($mobile)) {
                return response()->json([
                    'status' => false,
                    'message' => 'User identification is required.',
                    'data' => [
                        'count' => 0,
                    ],
                ], 422);
            }

            $count = $this->notificationService->unreadCount(
                $userId,
                $mobile
            );

            return response()->json([
                'status' => true,
                'message' => 'Notification count fetched.',
                'data' => [
                    'count' => $count,
                    'unread_count' => $count,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Notification count failed.', [
                'user_id' => $request->user()?->id
                    ?? $request->input('user_id'),
                'mobile' => $request->input('mobile'),
                'message' => $e->getMessage(),
            ]);

            return $this->serverError(
                'Unable to fetch notification count.',
                $e
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Send Push Notification
    |--------------------------------------------------------------------------
    |
    | target:
    | user         = one user using user_id or mobile
    | users        = multiple users using user_ids
    | device       = direct device_token
    | topic        = Firebase topic
    | all          = all active users
    |
    */

    public function send(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'target' => [
                'nullable',
                Rule::in([
                    'user',
                    'users',
                    'device',
                    'topic',
                    'all',
                ]),
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'message' => [
                'required',
                'string',
                'max:2000',
            ],

            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],

            'user_ids' => [
                'nullable',
                'array',
                'max:500',
            ],

            'user_ids.*' => [
                'integer',
                'distinct',
                'exists:users,id',
            ],

            'mobile' => [
                'nullable',
                'string',
                'max:20',
            ],

            'device_token' => [
                'nullable',
                'string',
                'max:4096',
            ],

            'topic' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9\-_.~%]+$/',
            ],

            'type' => [
                'nullable',
                'string',
                'max:100',
            ],

            'data' => [
                'nullable',
                'array',
            ],

            'save_in_database' => [
                'nullable',
                'boolean',
            ],
        ]);

        if ($validator->fails()) {
            return $this->validationError(
                $validator->errors()->first()
            );
        }

        try {
            $target = $request->input('target')
                ?? $this->detectTarget($request);

            $data = array_merge(
                [
                    'type' => $request->input(
                        'type',
                        'general'
                    ),
                    'click_action' => $request->input(
                        'data.click_action',
                        'OPEN_NOTIFICATION'
                    ),
                ],
                $request->input('data', [])
            );

            $saveInDatabase = $request->boolean(
                'save_in_database',
                true
            );

            $result = match ($target) {
                'user' => $this->sendToSingleUser(
                    $request,
                    $data,
                    $saveInDatabase
                ),

                'users' => $this->sendToMultipleUsers(
                    $request,
                    $data,
                    $saveInDatabase
                ),

                'device' => $this->sendToDevice(
                    $request,
                    $data
                ),

                'topic' => $this->sendToTopic(
                    $request,
                    $data
                ),

                'all' => $this->notificationService
                    ->sendToAllUsers(
                        title: $request->string('title')->toString(),
                        message: $request
                            ->string('message')
                            ->toString(),
                        data: $data,
                        saveInDatabase: $saveInDatabase
                    ),

                default => [
                    'status' => false,
                    'message' => 'Invalid notification target.',
                ],
            };

            $status = (bool) ($result['status'] ?? false);

            return response()->json([
                'status' => $status,
                'message' => $result['message']
                    ?? ($status
                        ? 'Notification sent successfully.'
                        : 'Notification could not be sent.'),
                'data' => $result,
            ], $status ? 200 : 422);
        } catch (Throwable $e) {
            Log::error('Send notification failed.', [
                'target' => $request->input('target'),
                'user_id' => $request->input('user_id'),
                'user_ids' => $request->input('user_ids'),
                'topic' => $request->input('topic'),
                'message' => $e->getMessage(),
            ]);

            return $this->serverError(
                'Unable to send notification.',
                $e
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Save or Refresh Device Token
    |--------------------------------------------------------------------------
    */

    public function saveDeviceToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_token' => [
                'required',
                'string',
                'max:4096',
            ],
            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'mobile' => [
                'nullable',
                'string',
                'max:20',
            ],
        ]);

        if ($validator->fails()) {
            return $this->validationError(
                $validator->errors()->first()
            );
        }

        try {
            $user = $request->user();

            if (! $user instanceof User) {
                $user = $this->findRequestedUser($request);
            }

            if (! $user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found.',
                ], 404);
            }

            $saved = $this->notificationService
                ->updateDeviceToken(
                    $user,
                    $request->string('device_token')->toString()
                );

            return response()->json([
                'status' => $saved,
                'message' => $saved
                    ? 'Device token saved successfully.'
                    : 'Unable to save device token.',
            ], $saved ? 200 : 500);
        } catch (Throwable $e) {
            Log::error('Device token save failed.', [
                'user_id' => $request->user()?->id
                    ?? $request->input('user_id'),
                'message' => $e->getMessage(),
            ]);

            return $this->serverError(
                'Unable to save device token.',
                $e
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Remove Device Token
    |--------------------------------------------------------------------------
    */

    public function removeDeviceToken(
        Request $request
    ): JsonResponse {
        $validator = Validator::make($request->all(), [
            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'mobile' => [
                'nullable',
                'string',
                'max:20',
            ],
        ]);

        if ($validator->fails()) {
            return $this->validationError(
                $validator->errors()->first()
            );
        }

        try {
            $user = $request->user();

            if (! $user instanceof User) {
                $user = $this->findRequestedUser($request);
            }

            if (! $user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found.',
                ], 404);
            }

            $removed = $this->notificationService
                ->removeDeviceToken($user);

            return response()->json([
                'status' => $removed,
                'message' => $removed
                    ? 'Device token removed successfully.'
                    : 'Unable to remove device token.',
            ], $removed ? 200 : 500);
        } catch (Throwable $e) {
            Log::error('Device token removal failed.', [
                'user_id' => $request->user()?->id
                    ?? $request->input('user_id'),
                'message' => $e->getMessage(),
            ]);

            return $this->serverError(
                'Unable to remove device token.',
                $e
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Private Sending Helpers
    |--------------------------------------------------------------------------
    */

    private function sendToSingleUser(
        Request $request,
        array $data,
        bool $saveInDatabase
    ): array {
        $user = $this->findRequestedUser($request);

        if (! $user) {
            return [
                'status' => false,
                'message' => 'User not found.',
                'database_saved' => false,
                'push_sent' => false,
            ];
        }

        return $this->notificationService->sendToUser(
            user: $user,
            title: $request->string('title')->toString(),
            message: $request->string('message')->toString(),
            data: $data,
            saveInDatabase: $saveInDatabase
        );
    }

    private function sendToMultipleUsers(
        Request $request,
        array $data,
        bool $saveInDatabase
    ): array {
        $userIds = collect(
            $request->input('user_ids', [])
        )
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return [
                'status' => false,
                'message' => 'At least one user ID is required.',
            ];
        }

        $users = User::query()
            ->whereIn('id', $userIds)
            ->get();

        if ($users->isEmpty()) {
            return [
                'status' => false,
                'message' => 'No matching users found.',
            ];
        }

        return $this->notificationService->sendToUsers(
            users: $users,
            title: $request->string('title')->toString(),
            message: $request->string('message')->toString(),
            data: $data,
            saveInDatabase: $saveInDatabase
        );
    }

    private function sendToDevice(
        Request $request,
        array $data
    ): array {
        $deviceToken = trim(
            (string) $request->input('device_token')
        );

        if ($deviceToken === '') {
            return [
                'status' => false,
                'message' => 'Device token is required.',
                'push_sent' => false,
            ];
        }

        $sent = $this->notificationService->sendPush(
            deviceToken: $deviceToken,
            title: $request->string('title')->toString(),
            message: $request->string('message')->toString(),
            data: $data
        );

        return [
            'status' => $sent,
            'message' => $sent
                ? 'Device notification sent successfully.'
                : 'Device notification failed.',
            'push_sent' => $sent,
            'database_saved' => false,
        ];
    }

    private function sendToTopic(
        Request $request,
        array $data
    ): array {
        $topic = trim(
            (string) $request->input('topic')
        );

        if ($topic === '') {
            return [
                'status' => false,
                'message' => 'Notification topic is required.',
            ];
        }

        return $this->notificationService->sendToTopic(
            topic: $topic,
            title: $request->string('title')->toString(),
            message: $request->string('message')->toString(),
            data: $data
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Private User Helpers
    |--------------------------------------------------------------------------
    */

    private function resolveNotificationOwner(
        Request $request
    ): array {
        $authenticatedUser = $request->user();

        if ($authenticatedUser instanceof User) {
            return [
                $authenticatedUser->id,
                $authenticatedUser->mobile,
            ];
        }

        $userId = $request->input(
            'user_id',
            $request->query('user_id')
        );

        $mobile = $request->input(
            'mobile',
            $request->query('mobile')
        );

        return [
            filled($userId) ? (int) $userId : null,
            filled($mobile)
                ? trim((string) $mobile)
                : null,
        ];
    }

    private function findRequestedUser(
        Request $request
    ): ?User {
        if ($request->filled('user_id')) {
            return User::query()->find(
                $request->integer('user_id')
            );
        }

        if ($request->filled('mobile')) {
            return User::query()
                ->where(
                    'mobile',
                    trim((string) $request->input('mobile'))
                )
                ->first();
        }

        $authenticatedUser = $request->user();

        return $authenticatedUser instanceof User
            ? $authenticatedUser
            : null;
    }

    private function detectTarget(Request $request): string
    {
        if ($request->filled('topic')) {
            return 'topic';
        }

        if ($request->filled('device_token')) {
            return 'device';
        }

        if (
            is_array($request->input('user_ids')) &&
            count($request->input('user_ids')) > 0
        ) {
            return 'users';
        }

        if (
            $request->filled('user_id') ||
            $request->filled('mobile')
        ) {
            return 'user';
        }

        return 'user';
    }

    /*
    |--------------------------------------------------------------------------
    | Response Helpers
    |--------------------------------------------------------------------------
    */

    private function validationError(string $message): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => $message,
        ], 422);
    }

    private function serverError(
        string $message,
        Throwable $e
    ): JsonResponse {
        return response()->json([
            'status' => false,
            'message' => $message,
            'error' => config('app.debug')
                ? $e->getMessage()
                : null,
        ], 500);
    }
}