<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\VerifyOtpRequest;
use App\Models\User;
use App\Services\ActivityTrackingService;
use App\Services\FirebaseService;
use App\Services\GoogleAuthService;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AuthController extends BaseApiController
{
    /*
    |--------------------------------------------------------------------------
    | OTP Authentication
    |--------------------------------------------------------------------------
    */

    /**
     * Send login OTP to customer.
     */
    public function sendOtp(
        LoginRequest $request,
        OtpService $otpService,
        ActivityTrackingService $activityTrackingService
    ) {
        $mobile = (string) $request->validated('mobile');

        $result = $otpService->sendLoginOtp($mobile);

        if (! ($result['status'] ?? false)) {
            return $this->error(
                $result['message'] ?? 'Unable to send OTP',
                422,
                $result
            );
        }

        $activityTrackingService->trackOtpRequested(
            mobile: $mobile,
            attributes: [
                'login_type' => 'otp',
                'source' => ActivityTrackingService::SOURCE_FLUTTER,
                'data' => [
                    'otp_channel' => Arr::get(
                        $result,
                        'channel',
                        'mobile'
                    ),
                ],
            ],
            request: $request
        );

        return $this->success(
            [
                // These channels accepted a send request; delivery receipts are separate.
                'accepted_channels' => array_values(array_intersect(
                    (array) ($result['delivered_on'] ?? []), ['sms', 'whatsapp', 'email']
                )),
                'channel' => ($result['channel'] ?? null) === 'review' ? 'review' : null,
                'expires_in' => (int) ($result['expires_in'] ?? 300),
                'resend_after' => (int) ($result['resend_after'] ?? 30),
            ],
            ($result['channel'] ?? null) === 'review'
                ? $result['message']
                : 'OTP request accepted. Check the available channel shown in the app.'
        );
    }

    /**
     * Verify customer OTP and return login token.
     */
    public function verifyOtp(
        VerifyOtpRequest $request,
        OtpService $otpService,
        ActivityTrackingService $activityTrackingService
    ) {
        $mobile = (string) $request->validated('mobile');

        $result = $otpService->verifyLoginOtp(
            $mobile,
            (string) $request->otp
        );

        if (! ($result['status'] ?? false)) {
            return $this->error(
                $result['message'] ?? 'Invalid OTP',
                401
            );
        }

        $user = $this->resolveUserFromResult($result);

        $activityTrackingService->trackOtpVerified(
            mobile: $mobile,
            attributes: [
                'login_type' => 'otp',
                'source' => ActivityTrackingService::SOURCE_FLUTTER,
            ],
            request: $request,
            user: $user
        );

        if ($user) {
            $activityTrackingService->trackLogin(
                user: $user,
                attributes: [
                    'login_type' => 'otp',
                    'source' =>
                        ActivityTrackingService::SOURCE_FLUTTER,
                ],
                request: $request
            );
        }

        return $this->success([
            'token' => $result['token'] ?? null,
            'user' => $result['user'] ?? $user,
        ], $result['message'] ?? 'OTP verified successfully');
    }

    /*
    |--------------------------------------------------------------------------
    | Google Authentication
    |--------------------------------------------------------------------------
    */

    /**
     * Login or register customer using Google.
     */
    public function googleLogin(
        Request $request,
        GoogleAuthService $googleAuthService,
        ActivityTrackingService $activityTrackingService
    ) {
        $validator = Validator::make($request->all(), [
            'id_token' => 'required|string|max:16384',
            'google_id' => 'nullable|string',
            'email' => 'nullable|email',
            'name' => 'nullable|string|max:255',
            'photo' => 'nullable|string',
            'device_token' => 'nullable|string',
            'device_id' => 'nullable|string|max:191',
            'session_id' => 'nullable|string|max:120',
            'platform' => 'nullable|string|max:30',
            'device_name' => 'nullable|string|max:255',
            'operating_system' => 'nullable|string|max:255',
            'app_version' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->error(
                $validator->errors()->first(),
                422,
                $validator->errors()
            );
        }

        $result = $googleAuthService->loginWithGoogle(
            $request->all()
        );

        if (! ($result['status'] ?? false)) {
            return $this->error(
                $result['message'] ?? 'Google login failed',
                422,
                $result['error'] ?? null
            );
        }

        $user = $this->resolveUserFromResult($result);

        if ($user) {
            $this->trackSocialLogin(
                activityTrackingService: $activityTrackingService,
                request: $request,
                user: $user,
                loginType: 'google'
            );
        }

        return $this->success(
            $result['data'] ?? null,
            $result['message'] ?? 'Google login successful'
        );
    }

    /**
     * Login or register customer using Firebase authentication.
     */
    public function firebaseLogin(
        Request $request,
        GoogleAuthService $googleAuthService,
        FirebaseService $firebaseService,
        ActivityTrackingService $activityTrackingService
    ) {
        $validator = Validator::make($request->all(), [
            'firebase_token' => 'required|string',
            'device_token' => 'nullable|string',
            'device_id' => 'nullable|string|max:191',
            'session_id' => 'nullable|string|max:120',
            'platform' => 'nullable|string|max:30',
            'device_name' => 'nullable|string|max:255',
            'operating_system' => 'nullable|string|max:255',
            'app_version' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->error(
                $validator->errors()->first(),
                422,
                $validator->errors()
            );
        }

        $result = $googleAuthService->loginWithFirebase(
            $request->all(),
            $firebaseService
        );

        if (! ($result['status'] ?? false)) {
            return $this->error(
                $result['message'] ?? 'Firebase login failed',
                422
            );
        }

        $user = $this->resolveUserFromResult($result);

        if ($user) {
            $this->trackSocialLogin(
                activityTrackingService: $activityTrackingService,
                request: $request,
                user: $user,
                loginType: 'firebase'
            );
        }

        return $this->success(
            $result['data'] ?? null,
            $result['message'] ?? 'Firebase login successful'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Email Authentication
    |--------------------------------------------------------------------------
    */

    /**
     * Login or register customer using email.
     */
    public function emailLogin(Request $request)
    {
        return $this->error('Email login is no longer available. Please update the app and use mobile OTP or Google.', 410);
    }

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Authentication
    |--------------------------------------------------------------------------
    */

    /**
     * Login or register customer using WhatsApp.
     */
    public function whatsappLogin(Request $request)
    {
        return $this->error('WhatsApp login requires OTP verification. Use send-otp and verify-otp.', 410);
    }

    /*
    |--------------------------------------------------------------------------
    | Facebook Authentication
    |--------------------------------------------------------------------------
    */

    /**
     * Login or register customer using Facebook.
     */
    public function facebookLogin(Request $request)
    {
        return $this->error('Facebook login is no longer available. Please update the app and use mobile OTP or Google.', 410);
    }

    /*
    |--------------------------------------------------------------------------
    | Apple Authentication
    |--------------------------------------------------------------------------
    */

    /**
     * Login or register customer using Apple.
     */
    public function appleLogin(Request $request)
    {
        return $this->error('Apple login is no longer available. Please update the app and use mobile OTP or Google.', 410);
    }

    /*
    |--------------------------------------------------------------------------
    | Guest Authentication
    |--------------------------------------------------------------------------
    */

    /**
     * Create or reuse a guest customer account.
     */
    public function guestLogin(
        Request $request,
        ActivityTrackingService $activityTrackingService
    ) {
        $validator = Validator::make($request->all(), [
            'guest_id' => 'nullable|string|max:191',
            'device_token' => 'nullable|string',
            'device_id' => 'nullable|string|max:191',
            'session_id' => 'nullable|string|max:120',
            'platform' => 'nullable|string|max:30',
            'device_name' => 'nullable|string|max:255',
            'operating_system' => 'nullable|string|max:255',
            'app_version' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->error(
                $validator->errors()->first(),
                422,
                $validator->errors()
            );
        }

        $guestId = $request->guest_id
            ?? $request->device_id
            ?? (string) Str::uuid();

        $email = 'guest_' . $guestId . '@duracabs.local';

        $user = User::firstOrCreate(
            ['email' => $email],
            $this->onlyUserColumns([
                'name' => 'Guest User',
                'email' => $email,
                'password' => Hash::make(Str::random(32)),
                'device_token' => $request->device_token,
                'login_type' => 'guest',
                'is_active' => true,
            ])
        );

        $isNewUser = $user->wasRecentlyCreated;

        $user->update($this->onlyUserColumns([
            'device_token' => $request->device_token,
            'login_type' => 'guest',
        ]));

        $this->trackRegistrationAndLogin(
            activityTrackingService: $activityTrackingService,
            request: $request,
            user: $user,
            loginType: 'guest',
            isNewUser: $isNewUser
        );

        return $this->loginResponse(
            $user,
            'Guest login successful'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Logout and Account Deletion
    |--------------------------------------------------------------------------
    */

    /**
     * Logout current authenticated customer.
     */
    public function logout(
        Request $request,
        ActivityTrackingService $activityTrackingService
    ) {
        $user = $request->user();

        if ($user instanceof User) {
            $activityTrackingService->trackLogout(
                user: $user,
                attributes: [
                    'login_type' =>
                        $user->login_type ?? 'unknown',
                    'source' =>
                        ActivityTrackingService::SOURCE_FLUTTER,
                ],
                request: $request
            );
        }

        if (
            $user &&
            method_exists($user, 'currentAccessToken')
        ) {
            $user->currentAccessToken()?->delete();
        }

        return $this->success(
            null,
            'Logged out successfully'
        );
    }

    /**
     * Delete current authenticated customer account.
     */
    public function deleteAccount(
        Request $request,
        ActivityTrackingService $activityTrackingService
    ) {
        $user = $request->user();

        if (! $user instanceof User) {
            return $this->error('Unauthenticated', 401);
        }

        $activityTrackingService->track(
            event: 'account_deleted',
            attributes: [
                'module' =>
                    ActivityTrackingService::MODULE_PROFILE,
                'stage' => 'deleted',
                'mobile' => $user->mobile,
                'customer_name' => $user->name,
                'login_type' =>
                    $user->login_type ?? 'unknown',
                'source' =>
                    ActivityTrackingService::SOURCE_FLUTTER,
                'data' => [
                    'deleted_user_id' => $user->id,
                    'deleted_email' => $user->email,
                ],
            ],
            request: $request,
            user: $user
        );

        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }

        $user->delete();

        return $this->success(
            null,
            'Account deleted successfully'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Private Authentication Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Create application token and login response.
     */
    private function loginResponse(
        User $user,
        string $message
    ) {
        $token = method_exists($user, 'createToken')
            ? $user
                ->createToken('dura_app_token')
                ->plainTextToken
            : 'duracabs_token_' . $user->id;

        return $this->success([
            'token' => $token,
            'user' => $user,
        ], $message);
    }

    /**
     * Track customer registration followed by login.
     */
    private function trackRegistrationAndLogin(
        ActivityTrackingService $activityTrackingService,
        Request $request,
        User $user,
        string $loginType,
        bool $isNewUser
    ): void {
        if ($isNewUser) {
            $activityTrackingService->trackUserRegistered(
                user: $user,
                attributes: [
                    'login_type' => $loginType,
                    'source' =>
                        ActivityTrackingService::SOURCE_FLUTTER,
                ],
                request: $request
            );
        }

        $activityTrackingService->trackLogin(
            user: $user,
            attributes: [
                'login_type' => $loginType,
                'source' =>
                    ActivityTrackingService::SOURCE_FLUTTER,
                'data' => [
                    'new_user' => $isNewUser,
                ],
            ],
            request: $request
        );
    }

    /**
     * Track login returned by an external authentication service.
     */
    private function trackSocialLogin(
        ActivityTrackingService $activityTrackingService,
        Request $request,
        User $user,
        string $loginType
    ): void {
        $isNewUser = $user->wasRecentlyCreated;

        $this->trackRegistrationAndLogin(
            activityTrackingService: $activityTrackingService,
            request: $request,
            user: $user,
            loginType: $loginType,
            isNewUser: $isNewUser
        );
    }

    /**
     * Resolve a user model from service response data.
     */
    private function resolveUserFromResult(
        array $result
    ): ?User {
        $possibleUser = Arr::get($result, 'user')
            ?? Arr::get($result, 'data.user')
            ?? Arr::get($result, 'data.customer');

        if ($possibleUser instanceof User) {
            return $possibleUser;
        }

        if (is_array($possibleUser)) {
            $userId = Arr::get($possibleUser, 'id');

            if ($userId) {
                return User::query()->find($userId);
            }

            $email = Arr::get($possibleUser, 'email');

            if ($email) {
                return User::query()
                    ->where('email', $email)
                    ->first();
            }

            $mobile = Arr::get($possibleUser, 'mobile');

            if ($mobile) {
                return User::query()
                    ->where(
                        'mobile',
                        $this->normalizeMobile($mobile)
                    )
                    ->first();
            }
        }

        $userId = Arr::get($result, 'user_id')
            ?? Arr::get($result, 'data.user_id');

        if ($userId) {
            return User::query()->find($userId);
        }

        return null;
    }

    /**
     * Keep only columns that currently exist in users table.
     */
    private function onlyUserColumns(array $data): array
    {
        return collect($data)
            ->filter(
                fn (mixed $value): bool =>
                    ! is_null($value)
            )
            ->filter(
                fn (
                    mixed $value,
                    string $key
                ): bool => Schema::hasColumn(
                    'users',
                    $key
                )
            )
            ->toArray();
    }

    /**
     * Normalize Indian or international mobile number.
     */
    private function normalizeMobile(
        string|int|null $mobile
    ): string {
        $digits = preg_replace(
            '/\D+/',
            '',
            (string) $mobile
        );

        if (
            strlen($digits) > 10 &&
            str_starts_with($digits, '91')
        ) {
            return substr($digits, -10);
        }

        return $digits;
    }
}