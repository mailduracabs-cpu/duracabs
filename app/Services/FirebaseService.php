<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class FirebaseService
{
    private const FCM_SCOPE =
        'https://www.googleapis.com/auth/firebase.messaging';

    private const OAUTH_TOKEN_URL =
        'https://oauth2.googleapis.com/token';

    private const FIREBASE_IDENTITY_URL =
        'https://identitytoolkit.googleapis.com/v1/accounts:lookup';

    private const ACCESS_TOKEN_CACHE_KEY =
        'firebase_http_v1_access_token';

    private const ACCESS_TOKEN_EXPIRY_CACHE_KEY =
        'firebase_http_v1_access_token_expiry';

    private const DEFAULT_TIMEOUT = 30;

    private const MAX_RETRIES = 3;

    private string $projectId;

    private string $serviceAccountPath;

    private ?string $firebaseApiKey;

    public function __construct()
    {
        $this->projectId = trim(
            (string) config(
                'services.firebase.project_id',
                env('FIREBASE_PROJECT_ID')
            )
        );

        $this->serviceAccountPath = $this->resolveServiceAccountPath(
            (string) config(
                'services.firebase.credentials',
                env('FIREBASE_CREDENTIALS')
            )
        );

        $this->firebaseApiKey = config(
            'services.firebase.api_key',
            env('FIREBASE_API_KEY')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Send Notification To Single Device
    |--------------------------------------------------------------------------
    */

    public function sendNotification(
        string $deviceToken,
        string $title,
        string $body,
        array $data = [],
        ?string $imageUrl = null
    ): array {
        $deviceToken = trim($deviceToken);

        if ($deviceToken === '') {
            return $this->failureResponse(
                message: 'Device token is required.',
                errorCode: 'MISSING_DEVICE_TOKEN',
                invalidToken: true
            );
        }

        $message = [
            'token' => $deviceToken,
            'notification' => $this->notificationPayload(
                title: $title,
                body: $body,
                imageUrl: $imageUrl
            ),
            'data' => $this->normalizeData($data),
            'android' => $this->androidConfig(
                imageUrl: $imageUrl
            ),
            'apns' => $this->apnsConfig(
                imageUrl: $imageUrl
            ),
        ];

        return $this->sendMessage($message);
    }

    /*
    |--------------------------------------------------------------------------
    | Send Notification To Topic
    |--------------------------------------------------------------------------
    */

    public function sendToTopic(
        string $topic,
        string $title,
        string $body,
        array $data = [],
        ?string $imageUrl = null
    ): array {
        $topic = $this->normalizeTopic($topic);

        if ($topic === '') {
            return $this->failureResponse(
                message: 'Firebase topic is required.',
                errorCode: 'MISSING_TOPIC'
            );
        }

        $message = [
            'topic' => $topic,
            'notification' => $this->notificationPayload(
                title: $title,
                body: $body,
                imageUrl: $imageUrl
            ),
            'data' => $this->normalizeData($data),
            'android' => $this->androidConfig(
                imageUrl: $imageUrl
            ),
            'apns' => $this->apnsConfig(
                imageUrl: $imageUrl
            ),
        ];

        return $this->sendMessage($message);
    }

    /*
    |--------------------------------------------------------------------------
    | Send Notification Using Condition
    |--------------------------------------------------------------------------
    */

    public function sendToCondition(
        string $condition,
        string $title,
        string $body,
        array $data = [],
        ?string $imageUrl = null
    ): array {
        $condition = trim($condition);

        if ($condition === '') {
            return $this->failureResponse(
                message: 'Firebase condition is required.',
                errorCode: 'MISSING_CONDITION'
            );
        }

        $message = [
            'condition' => $condition,
            'notification' => $this->notificationPayload(
                title: $title,
                body: $body,
                imageUrl: $imageUrl
            ),
            'data' => $this->normalizeData($data),
            'android' => $this->androidConfig(
                imageUrl: $imageUrl
            ),
            'apns' => $this->apnsConfig(
                imageUrl: $imageUrl
            ),
        ];

        return $this->sendMessage($message);
    }

    /*
    |--------------------------------------------------------------------------
    | Send Notification To Multiple Tokens
    |--------------------------------------------------------------------------
    |
    | HTTP v1 REST API sends one message per request. Tokens are therefore
    | processed individually and the result of each token is returned.
    |
    */

    public function sendMulticast(
        array $deviceTokens,
        string $title,
        string $body,
        array $data = [],
        ?string $imageUrl = null
    ): array {
        $tokens = collect($deviceTokens)
            ->filter(fn ($token) => is_string($token))
            ->map(fn ($token) => trim($token))
            ->filter()
            ->unique()
            ->values();

        if ($tokens->isEmpty()) {
            return [
                'status' => false,
                'message' => 'No valid device tokens supplied.',
                'total' => 0,
                'success_count' => 0,
                'failure_count' => 0,
                'invalid_tokens' => [],
                'results' => [],
            ];
        }

        $successCount = 0;
        $failureCount = 0;
        $invalidTokens = [];
        $results = [];

        foreach ($tokens as $token) {
            $result = $this->sendNotification(
                deviceToken: $token,
                title: $title,
                body: $body,
                data: $data,
                imageUrl: $imageUrl
            );

            if ($result['status'] ?? false) {
                $successCount++;
            } else {
                $failureCount++;
            }

            if ($result['invalid_token'] ?? false) {
                $invalidTokens[] = $token;
            }

            $results[] = [
                'token' => $this->maskToken($token),
                'status' => (bool) ($result['status'] ?? false),
                'message_id' => $result['message_id'] ?? null,
                'error_code' => $result['error_code'] ?? null,
                'error_message' => $result['message'] ?? null,
                'invalid_token' => (bool) (
                    $result['invalid_token'] ?? false
                ),
            ];
        }

        return [
            'status' => $successCount > 0,
            'message' => $successCount > 0
                ? 'Multicast notification processed.'
                : 'All multicast notifications failed.',
            'total' => $tokens->count(),
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'invalid_tokens' => $invalidTokens,
            'results' => $results,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Verify Firebase ID Token
    |--------------------------------------------------------------------------
    |
    | This preserves authentication-related usage of the old FirebaseService.
    | FIREBASE_API_KEY must be configured for this method.
    |
    */

    public function verifyIdToken(string $idToken): array
    {
        $idToken = trim($idToken);

        if ($idToken === '') {
            return [
                'status' => false,
                'message' => 'Firebase ID token is required.',
                'user' => null,
            ];
        }

        if (blank($this->firebaseApiKey)) {
            Log::error(
                'FIREBASE_API_KEY is not configured for ID token verification.'
            );

            return [
                'status' => false,
                'message' => 'Firebase API key is not configured.',
                'user' => null,
            ];
        }

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout(self::DEFAULT_TIMEOUT)
                ->post(
                    self::FIREBASE_IDENTITY_URL .
                    '?key=' .
                    urlencode((string) $this->firebaseApiKey),
                    [
                        'idToken' => $idToken,
                    ]
                );

            if (! $response->successful()) {
                $message = data_get(
                    $response->json(),
                    'error.message',
                    'Firebase token verification failed.'
                );

                Log::warning('Firebase ID token verification failed.', [
                    'http_status' => $response->status(),
                    'firebase_error' => $message,
                ]);

                return [
                    'status' => false,
                    'message' => $message,
                    'user' => null,
                ];
            }

            $firebaseUser = collect(
                $response->json('users', [])
            )->first();

            if (! is_array($firebaseUser)) {
                return [
                    'status' => false,
                    'message' => 'Firebase user was not found.',
                    'user' => null,
                ];
            }

            return [
                'status' => true,
                'message' => 'Firebase token verified successfully.',
                'user' => [
                    'firebase_uid' => $firebaseUser['localId'] ?? null,
                    'localId' => $firebaseUser['localId'] ?? null,
                    'email' => $firebaseUser['email'] ?? null,
                    'email_verified' => (bool) (
                        $firebaseUser['emailVerified'] ?? false
                    ),
                    'display_name' =>
                        $firebaseUser['displayName'] ?? null,
                    'photo_url' => $firebaseUser['photoUrl'] ?? null,
                    'phone_number' =>
                        $firebaseUser['phoneNumber'] ?? null,
                    'provider_user_info' =>
                        $firebaseUser['providerUserInfo'] ?? [],
                    'created_at' =>
                        $firebaseUser['createdAt'] ?? null,
                    'last_login_at' =>
                        $firebaseUser['lastLoginAt'] ?? null,
                ],
                'raw' => $firebaseUser,
            ];
        } catch (Throwable $e) {
            Log::error('Firebase ID token verification exception.', [
                'message' => $e->getMessage(),
            ]);

            return [
                'status' => false,
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'Firebase token verification failed.',
                'user' => null,
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Save Device Token
    |--------------------------------------------------------------------------
    */

    public function saveDeviceToken(
        User|int|string $user,
        ?string $deviceToken
    ): bool {
        try {
            $resolvedUser = $this->resolveUser($user);

            if (! $resolvedUser) {
                Log::warning(
                    'Device token could not be saved because user was not found.'
                );

                return false;
            }

            $deviceToken = filled($deviceToken)
                ? trim((string) $deviceToken)
                : null;

            $resolvedUser->forceFill([
                'device_token' => $deviceToken,
            ])->save();

            return true;
        } catch (Throwable $e) {
            Log::error('Firebase device token save failed.', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Remove Invalid Tokens From Users Table
    |--------------------------------------------------------------------------
    */

    public function removeInvalidTokens(array $tokens): int
    {
        $tokens = collect($tokens)
            ->filter(fn ($token) => is_string($token))
            ->map(fn ($token) => trim($token))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($tokens === []) {
            return 0;
        }

        try {
            return User::query()
                ->whereIn('device_token', $tokens)
                ->update([
                    'device_token' => null,
                    'updated_at' => now(),
                ]);
        } catch (Throwable $e) {
            Log::error('Invalid Firebase token cleanup failed.', [
                'token_count' => count($tokens),
                'message' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Core HTTP v1 Sender
    |--------------------------------------------------------------------------
    */

    private function sendMessage(array $message): array
    {
        try {
            $this->validateFirebaseConfiguration();

            $accessToken = $this->getAccessToken();

            $url = sprintf(
                'https://fcm.googleapis.com/v1/projects/%s/messages:send',
                urlencode($this->projectId)
            );

            $response = $this->performSendRequest(
                url: $url,
                accessToken: $accessToken,
                payload: [
                    'message' => $this->removeEmptyValues($message),
                ]
            );

            if ($response->successful()) {
                return [
                    'status' => true,
                    'message' => 'Firebase notification sent successfully.',
                    'message_id' => $response->json('name'),
                    'invalid_token' => false,
                    'http_status' => $response->status(),
                    'response' => $response->json(),
                ];
            }

            /*
             * Access token could have expired or been revoked.
             * Clear cached token and retry once with a fresh token.
             */
            if ($response->status() === 401) {
                $this->clearCachedAccessToken();

                $freshAccessToken = $this->getAccessToken(
                    forceRefresh: true
                );

                $response = $this->performSendRequest(
                    url: $url,
                    accessToken: $freshAccessToken,
                    payload: [
                        'message' =>
                            $this->removeEmptyValues($message),
                    ]
                );

                if ($response->successful()) {
                    return [
                        'status' => true,
                        'message' =>
                            'Firebase notification sent successfully.',
                        'message_id' => $response->json('name'),
                        'invalid_token' => false,
                        'http_status' => $response->status(),
                        'response' => $response->json(),
                    ];
                }
            }

            return $this->firebaseErrorResponse($response);
        } catch (Throwable $e) {
            Log::error('Firebase HTTP v1 notification exception.', [
                'message' => $e->getMessage(),
                'project_id' => $this->projectId ?: null,
            ]);

            return $this->failureResponse(
                message: config('app.debug')
                    ? $e->getMessage()
                    : 'Firebase notification failed.',
                errorCode: 'FIREBASE_EXCEPTION'
            );
        }
    }

    private function performSendRequest(
        string $url,
        string $accessToken,
        array $payload
    ): Response {
        return Http::acceptJson()
            ->asJson()
            ->withToken($accessToken)
            ->timeout(self::DEFAULT_TIMEOUT)
            ->retry(
                times: self::MAX_RETRIES,
                sleepMilliseconds: 1000,
                when: function (
                    Throwable $exception,
                    $request
                ): bool {
                    return true;
                },
                throw: false
            )
            ->post($url, $payload);
    }

    /*
    |--------------------------------------------------------------------------
    | OAuth 2.0 Access Token
    |--------------------------------------------------------------------------
    */

    private function getAccessToken(
        bool $forceRefresh = false
    ): string {
        if (! $forceRefresh) {
            $cachedToken = Cache::get(
                self::ACCESS_TOKEN_CACHE_KEY
            );

            $cachedExpiry = (int) Cache::get(
                self::ACCESS_TOKEN_EXPIRY_CACHE_KEY,
                0
            );

            if (
                is_string($cachedToken) &&
                $cachedToken !== '' &&
                $cachedExpiry > now()->addMinutes(5)->timestamp
            ) {
                return $cachedToken;
            }
        }

        $credentials = $this->serviceAccountCredentials();

        $issuedAt = time();
        $expiresAt = $issuedAt + 3600;

        $jwtHeader = [
            'alg' => 'RS256',
            'typ' => 'JWT',
        ];

        $jwtClaims = [
            'iss' => $credentials['client_email'],
            'scope' => self::FCM_SCOPE,
            'aud' => self::OAUTH_TOKEN_URL,
            'iat' => $issuedAt,
            'exp' => $expiresAt,
        ];

        $unsignedToken =
            $this->base64UrlEncode(
                json_encode(
                    $jwtHeader,
                    JSON_UNESCAPED_SLASHES
                ) ?: '{}'
            ) .
            '.' .
            $this->base64UrlEncode(
                json_encode(
                    $jwtClaims,
                    JSON_UNESCAPED_SLASHES
                ) ?: '{}'
            );

        $signature = '';

        $signed = openssl_sign(
            $unsignedToken,
            $signature,
            $credentials['private_key'],
            OPENSSL_ALGO_SHA256
        );

        if (! $signed) {
            throw new RuntimeException(
                'Unable to sign Firebase OAuth JWT.'
            );
        }

        $assertion =
            $unsignedToken .
            '.' .
            $this->base64UrlEncode($signature);

        $response = Http::asForm()
            ->acceptJson()
            ->timeout(self::DEFAULT_TIMEOUT)
            ->post(self::OAUTH_TOKEN_URL, [
                'grant_type' =>
                    'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Firebase OAuth token request failed: ' .
                (
                    $response->json('error_description')
                    ?? $response->json('error')
                    ?? $response->body()
                )
            );
        }

        $accessToken = trim(
            (string) $response->json('access_token')
        );

        if ($accessToken === '') {
            throw new RuntimeException(
                'Firebase OAuth access token was empty.'
            );
        }

        $expiresIn = max(
            300,
            (int) $response->json('expires_in', 3600)
        );

        $cacheSeconds = max(
            60,
            $expiresIn - 300
        );

        Cache::put(
            self::ACCESS_TOKEN_CACHE_KEY,
            $accessToken,
            now()->addSeconds($cacheSeconds)
        );

        Cache::put(
            self::ACCESS_TOKEN_EXPIRY_CACHE_KEY,
            now()->addSeconds($expiresIn)->timestamp,
            now()->addSeconds($cacheSeconds)
        );

        return $accessToken;
    }

    private function serviceAccountCredentials(): array
    {
        if (! is_file($this->serviceAccountPath)) {
            throw new RuntimeException(
                'Firebase service account file was not found: ' .
                $this->serviceAccountPath
            );
        }

        $contents = file_get_contents(
            $this->serviceAccountPath
        );

        if ($contents === false) {
            throw new RuntimeException(
                'Firebase service account file could not be read.'
            );
        }

        $credentials = json_decode(
            $contents,
            true
        );

        if (! is_array($credentials)) {
            throw new RuntimeException(
                'Firebase service account JSON is invalid.'
            );
        }

        foreach (
            ['project_id', 'client_email', 'private_key']
            as $requiredKey
        ) {
            if (blank($credentials[$requiredKey] ?? null)) {
                throw new RuntimeException(
                    "Firebase credential '{$requiredKey}' is missing."
                );
            }
        }

        /*
         * Project ID from the service account is the safest fallback.
         */
        if ($this->projectId === '') {
            $this->projectId = trim(
                (string) $credentials['project_id']
            );
        }

        return $credentials;
    }

    /*
    |--------------------------------------------------------------------------
    | FCM Payload Configuration
    |--------------------------------------------------------------------------
    */

    private function notificationPayload(
        string $title,
        string $body,
        ?string $imageUrl = null
    ): array {
        return array_filter([
            'title' => trim($title),
            'body' => trim($body),
            'image' => filled($imageUrl)
                ? trim((string) $imageUrl)
                : null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function androidConfig(
        ?string $imageUrl = null
    ): array {
        return [
            'priority' => 'high',
            'notification' => array_filter([
                'channel_id' => config(
                    'services.firebase.android_channel_id',
                    'duracabs_high_importance_channel'
                ),
                'sound' => 'default',
                'default_sound' => true,
                'default_vibrate_timings' => true,
                'notification_priority' =>
                    'PRIORITY_HIGH',
                'image' => filled($imageUrl)
                    ? trim((string) $imageUrl)
                    : null,
            ], fn ($value) => $value !== null),
        ];
    }

    private function apnsConfig(
        ?string $imageUrl = null
    ): array {
        $aps = [
            'sound' => 'default',
            'badge' => 1,
            'content-available' => 1,
        ];

        if (filled($imageUrl)) {
            $aps['mutable-content'] = 1;
        }

        return [
            'headers' => [
                'apns-priority' => '10',
            ],
            'payload' => [
                'aps' => $aps,
            ],
            'fcm_options' => array_filter([
                'image' => filled($imageUrl)
                    ? trim((string) $imageUrl)
                    : null,
            ], fn ($value) => $value !== null),
        ];
    }

    private function normalizeData(array $data): array
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
                $normalized[$key] = $value
                    ? 'true'
                    : 'false';

                continue;
            }

            if (
                is_array($value) ||
                is_object($value)
            ) {
                $normalized[$key] = json_encode(
                    $value,
                    JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES
                ) ?: '';

                continue;
            }

            $normalized[$key] = (string) $value;
        }

        return $normalized;
    }

    /*
    |--------------------------------------------------------------------------
    | Firebase Error Handling
    |--------------------------------------------------------------------------
    */

    private function firebaseErrorResponse(
        Response $response
    ): array {
        $errorStatus = strtoupper(
            (string) data_get(
                $response->json(),
                'error.status',
                'UNKNOWN'
            )
        );

        $errorMessage = (string) data_get(
            $response->json(),
            'error.message',
            'Firebase notification failed.'
        );

        $fcmErrorCode = $this->extractFcmErrorCode(
            $response->json()
        );

        $invalidToken = $this->isInvalidTokenError(
            httpStatus: $response->status(),
            errorStatus: $errorStatus,
            fcmErrorCode: $fcmErrorCode,
            errorMessage: $errorMessage
        );

        Log::warning('Firebase HTTP v1 request failed.', [
            'http_status' => $response->status(),
            'error_status' => $errorStatus,
            'fcm_error_code' => $fcmErrorCode,
            'error_message' => $errorMessage,
            'invalid_token' => $invalidToken,
        ]);

        return [
            'status' => false,
            'message' => $errorMessage,
            'error_code' => $fcmErrorCode ?: $errorStatus,
            'error_status' => $errorStatus,
            'invalid_token' => $invalidToken,
            'retryable' => $this->isRetryableError(
                $response->status(),
                $errorStatus
            ),
            'http_status' => $response->status(),
            'response' => $response->json(),
        ];
    }

    private function extractFcmErrorCode(
        mixed $responseBody
    ): ?string {
        if (! is_array($responseBody)) {
            return null;
        }

        $details = data_get(
            $responseBody,
            'error.details',
            []
        );

        if (! is_array($details)) {
            return null;
        }

        foreach ($details as $detail) {
            if (! is_array($detail)) {
                continue;
            }

            if (isset($detail['errorCode'])) {
                return strtoupper(
                    (string) $detail['errorCode']
                );
            }
        }

        return null;
    }

    private function isInvalidTokenError(
        int $httpStatus,
        string $errorStatus,
        ?string $fcmErrorCode,
        string $errorMessage
    ): bool {
        if (
            in_array(
                $fcmErrorCode,
                [
                    'UNREGISTERED',
                    'REGISTRATION_TOKEN_NOT_REGISTERED',
                ],
                true
            )
        ) {
            return true;
        }

        if (
            $httpStatus === 404 &&
            $errorStatus === 'NOT_FOUND'
        ) {
            return true;
        }

        $lowerMessage = strtolower($errorMessage);

        return str_contains(
            $lowerMessage,
            'registration token is not a valid'
        ) ||
        str_contains(
            $lowerMessage,
            'requested entity was not found'
        ) ||
        str_contains(
            $lowerMessage,
            'not a valid fcm registration token'
        ) ||
        str_contains(
            $lowerMessage,
            'registration token is not registered'
        );
    }

    private function isRetryableError(
        int $httpStatus,
        string $errorStatus
    ): bool {
        return in_array(
            $httpStatus,
            [429, 500, 502, 503, 504],
            true
        ) ||
        in_array(
            $errorStatus,
            [
                'RESOURCE_EXHAUSTED',
                'INTERNAL',
                'UNAVAILABLE',
            ],
            true
        );
    }

    private function failureResponse(
        string $message,
        ?string $errorCode = null,
        bool $invalidToken = false
    ): array {
        return [
            'status' => false,
            'message' => $message,
            'error_code' => $errorCode,
            'invalid_token' => $invalidToken,
            'retryable' => false,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function validateFirebaseConfiguration(): void
    {
        if ($this->projectId === '') {
            /*
             * serviceAccountCredentials() can populate project ID from JSON.
             */
            $this->serviceAccountCredentials();
        }

        if ($this->projectId === '') {
            throw new RuntimeException(
                'FIREBASE_PROJECT_ID is not configured.'
            );
        }

        if ($this->serviceAccountPath === '') {
            throw new RuntimeException(
                'FIREBASE_CREDENTIALS is not configured.'
            );
        }
    }

    private function resolveServiceAccountPath(
        string $path
    ): string {
        $path = trim($path);

        if ($path === '') {
            return storage_path(
                'app/firebase/firebase-service-account.json'
            );
        }

        /*
         * Absolute Windows path: C:\folder\file.json
         */
        if (
            preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1
        ) {
            return $path;
        }

        /*
         * Absolute Linux/macOS path.
         */
        if (str_starts_with($path, '/')) {
            return $path;
        }

        return base_path($path);
    }

    private function normalizeTopic(string $topic): string
    {
        $topic = trim($topic);

        if (str_starts_with($topic, '/topics/')) {
            $topic = substr(
                $topic,
                strlen('/topics/')
            );
        }

        return trim($topic);
    }

    private function resolveUser(
        User|int|string $user
    ): ?User {
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

        return User::query()
            ->where('mobile', $value)
            ->orWhere('email', $value)
            ->first();
    }

    private function base64UrlEncode(
        string $value
    ): string {
        return rtrim(
            strtr(
                base64_encode($value),
                '+/',
                '-_'
            ),
            '='
        );
    }

    private function clearCachedAccessToken(): void
    {
        Cache::forget(
            self::ACCESS_TOKEN_CACHE_KEY
        );

        Cache::forget(
            self::ACCESS_TOKEN_EXPIRY_CACHE_KEY
        );
    }

    private function removeEmptyValues(
        array $value
    ): array {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $item = $this->removeEmptyValues($item);

                if ($item === []) {
                    unset($value[$key]);
                    continue;
                }

                $value[$key] = $item;
                continue;
            }

            if ($item === null) {
                unset($value[$key]);
            }
        }

        return $value;
    }

    private function maskToken(string $token): string
    {
        $length = strlen($token);

        if ($length <= 16) {
            return str_repeat('*', $length);
        }

        return substr($token, 0, 8) .
            '...' .
            substr($token, -8);
    }
}