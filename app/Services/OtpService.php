<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class OtpService
{
    private const OTP_EXPIRY_MINUTES = 5;
    private const OTP_RESEND_SECONDS = 30;
    private const OTP_MAX_ATTEMPTS = 5;

    /** Fixed-code access is bound to one provisioned, customer-only account. */
    private function reviewLoginUser(): ?User
    {
        if (config('play_review.enabled') !== true
            || (int) config('play_review.user_id', 0) < 1
            || !is_string(config('play_review.code_hash'))
            || config('play_review.code_hash') === '') {
            return null;
        }

        $user = User::query()->find((int) config('play_review.user_id'));
        if (!$user || !$user->is_active
            || (string) $user->mobile !== '0000000001'
            || (string) $user->email !== 'play-review@duracabs.local') {
            return null;
        }

        $roles = $user->roles()->get();
        if ($roles->count() !== 1
            || $roles->first()->name !== User::ROLE_CUSTOMER
            || $roles->first()->guard_name !== 'web'
            || $user->permissions()->exists()) {
            return null;
        }

        return $user;
    }

    private function sendReviewLoginOtp(): array
    {
        if (!$this->reviewLoginUser()) {
            return ['status' => false, 'message' => 'Review login is unavailable.'];
        }

        // No OTP generation, database OTP, or SMS/WhatsApp/email delivery.
        return [
            'status' => true,
            'message' => 'Enter the review code provided in the app access instructions.',
            'channel' => 'review',
            'delivered_on' => [],
            'resend_after' => 30,
        ];
    }

    private function verifyReviewLoginOtp(string $otp): array
    {
        $user = $this->reviewLoginUser();
        if (!$user) {
            return ['status' => false, 'message' => 'Review login is unavailable.'];
        }

        $accountKey = 'play_review_verify_account_' . $user->getKey();
        $ipKey = 'play_review_verify_ip_' . hash('sha256', (string) request()->ip());
        if (RateLimiter::tooManyAttempts($accountKey, 20)
            || RateLimiter::tooManyAttempts($ipKey, 5)) {
            return ['status' => false, 'message' => 'Too many attempts. Please try again in 15 minutes.'];
        }

        // Count every attempt before checking, including malformed submissions
        // reaching this service. Send/resend never resets these counters.
        RateLimiter::hit($accountKey, 900);
        RateLimiter::hit($ipKey, 900);
        $otp = trim($otp);
        if (!preg_match('/^\d{4}$/', $otp)
            || !Hash::check($otp, (string) config('play_review.code_hash'))) {
            return ['status' => false, 'message' => 'Invalid review code.'];
        }

        RateLimiter::clear($accountKey);
        RateLimiter::clear($ipKey);

        // Customer role is checked above; no web/admin session is opened.
        $token = $user->createToken('dura_app_review_token')->plainTextToken;

        return [
            'status' => true,
            'message' => 'Login successful.',
            'token' => $token,
            'user' => $user->fresh(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Customer Login OTP
    |--------------------------------------------------------------------------
    */

    public function send(
        string $mobile,
        ?string $scope = null,
        ?string $ipAddress = null
    ): array {
        if ($scope !== null && trim($scope) !== '') {
            return $this->sendFareOtp($mobile, $scope, $ipAddress);
        }

        return $this->sendLoginOtp($mobile);
    }

    public function sendLoginOtp(string $mobile): array
    {
        try {
            $mobile = CustomerPhoneNumber::normalize($mobile);
        } catch (\InvalidArgumentException $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
        if ($mobile === '0000000002') {
            return ['status' => false, 'message' => 'Use the Partner App for partner review login.'];
        }

        // Reserved synthetic identifier: never send a real login OTP.
        if ($mobile === '0000000001') {
            return $this->sendReviewLoginOtp();
        }

        $rateKey = 'otp_rate_' . $mobile;

        if (! Cache::add($rateKey, true, now()->addSeconds(self::OTP_RESEND_SECONDS))) {
            return [
                'status' => false,
                'message' => 'Please wait before requesting another OTP.',
            ];
        }

        $otp = $this->generateOtp();
        $expireAt = now()->addMinutes(self::OTP_EXPIRY_MINUTES);

        Cache::put('otp_' . $mobile, $otp, $expireAt);

        try {
            $user = $this->createOrUpdateUser($mobile, $otp, $expireAt);
        } catch (\RuntimeException $e) {
            Cache::forget('otp_' . $mobile);
            return ['status' => false, 'message' => 'This account requires support assistance before login.'];
        }

        $email = $this->getUserEmailForOtp($user, $mobile);

        $indian = CustomerPhoneNumber::isIndian($mobile);
        $smsResult = $indian ? $this->sendSmsOtp($mobile, $otp)
            : ['status' => false, 'channel' => 'sms', 'skipped' => true];
        $whatsAppResult = $this->sendWhatsAppOtp($mobile, $otp, authenticationOnly: true);
        // International login must succeed through WhatsApp, without depending on SMS/email.
        $emailResult = $indian ? $this->sendEmailOtp($email, $otp)
            : ['status' => false, 'channel' => 'email', 'skipped' => true];

        $results = [
            'sms' => $smsResult,
            'whatsapp' => $whatsAppResult,
            'email' => $emailResult,
        ];

        $successChannels = collect($results)
            ->filter(
                fn ($item) =>
                    ($item['status'] ?? false) === true
            )
            ->keys()
            ->values()
            ->toArray();

        $failedChannels = collect($results)
            ->filter(
                fn ($item) =>
                    ($item['status'] ?? false) !== true && empty($item['skipped'])
            )
            ->keys()
            ->values()
            ->toArray();

        Log::info('Dura Cabs OTP Delivery Summary', [
            'mobile' => $this->maskMobile($mobile),
            'success_channels' => $successChannels,
            'failed_channels' => $failedChannels,
        ]);

        if (count($successChannels) === 0) {
            $this->clearLoginOtp($mobile, $user);

            return [
                'status' => false,
                'message' => $indian ? 'Unable to send OTP. Please try again.'
                    : 'Unable to send WhatsApp OTP. Check that this number uses WhatsApp and try again.',
            ];
        }

        Cache::forget('otp_attempt_' . $mobile);

        return [
            'status' => true,
            'message' =>
                'OTP sent successfully. Please check your SMS, WhatsApp, or email.',
            'delivered_on' => $successChannels,
            'failed_channels' => $failedChannels,
            'expires_in' => self::OTP_EXPIRY_MINUTES * 60,
            'resend_after' => self::OTP_RESEND_SECONDS,
        ];
    }

    public function verify(
        string $mobile,
        string $otp,
        ?string $scope = null
    ): array {
        if ($scope !== null && trim($scope) !== '') {
            return $this->verifyFareOtp($mobile, $otp, $scope);
        }

        return $this->verifyLoginOtp($mobile, $otp);
    }

    public function verifyLoginOtp(string $mobile, string $otp): array
    {
        try {
            $mobile = CustomerPhoneNumber::normalize($mobile);
        } catch (\InvalidArgumentException $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }

        // Reserved synthetic identifier: never send a real login OTP.
        if ($mobile === '0000000001') {
            return $this->verifyReviewLoginOtp($otp);
        }

        $otp = trim($otp);

        if (!preg_match('/^\d{4}$/', $otp)) {
            return [
                'status' => false,
                'message' => 'Please enter a valid 4-digit OTP.',
            ];
        }

        $savedOtp = Cache::get('otp_' . $mobile);

        try {
            $user = $this->findLoginUserByMobile($mobile);
        } catch (\RuntimeException) {
            return ['status' => false, 'message' => 'This account requires support assistance before login.'];
        }

        if (!$user) {
            return [
                'status' => false,
                'message' => 'User account not found. Please request a new OTP.',
            ];
        }

        if (! $user->is_active) {
            $this->clearLoginOtp($mobile, $user);
            return ['status' => false, 'message' => 'This account is inactive. Please contact support.'];
        }
        $dbOtp = Schema::hasColumn('users', 'otp') && $user->otp_expire_at?->isFuture()
            ? $user->otp : null;

        if (!$savedOtp && !$dbOtp) {
            return [
                'status' => false,
                'message' => 'The OTP has expired. Please request a new OTP.',
            ];
        }

        $attemptKey = 'otp_attempt_' . $mobile;
        $attempts = (int) Cache::get($attemptKey, 0);

        if ($attempts >= self::OTP_MAX_ATTEMPTS) {
            $this->clearLoginOtp($mobile, $user);

            return [
                'status' => false,
                'message' =>
                    'The maximum number of OTP verification attempts has been exceeded. Please request a new OTP.',
            ];
        }

        $isValid = false;

        if (
            $savedOtp &&
            hash_equals((string) $savedOtp, $otp)
        ) {
            $isValid = true;
        }

        if (
            $dbOtp &&
            hash_equals((string) $dbOtp, $otp)
        ) {
            $isValid = true;
        }

        if (!$isValid) {
            $attempts++;

            Cache::put(
                $attemptKey,
                $attempts,
                now()->addMinutes(self::OTP_EXPIRY_MINUTES)
            );

            $remainingAttempts = max(
                0,
                self::OTP_MAX_ATTEMPTS - $attempts
            );

            return [
                'status' => false,
                'message' => $remainingAttempts > 0
                    ? "Invalid OTP. {$remainingAttempts} attempt(s) remaining."
                    : 'Invalid OTP. The maximum number of verification attempts has been exceeded.',
                'remaining_attempts' => $remainingAttempts,
            ];
        }

        $this->clearLoginOtp($mobile, $user);

        $user->forceFill(
            $this->onlyUserColumns([
                'otp' => null,
                'otp_expire_at' => null,
                'login_type' => 'otp',
                'mobile_verified_at' => now(),
            ])
        )->save();

        Auth::login($user, true);

        $token = $user->createToken('dura_app_token')->plainTextToken;

        RegistrationOrigin::completeUser($user, 'customer');

        return [
            'status' => true,
            'message' => 'Login successful.',
            'token' => $token,
            'user' => $user->fresh(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Fare / Booking OTP
    |--------------------------------------------------------------------------
    */

    public function sendFareOtp(
        string $mobile,
        string $scope,
        ?string $ipAddress = null
    ): array {
        $mobile = $this->cleanMobile($mobile);

        if (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
            return $this->fareFailure(
                'Please enter a valid 10-digit mobile number.'
            );
        }

        $scope = $this->cleanPurpose($scope) ?: 'default';
        $rateKey = "fare-otp:{$scope}:send:{$mobile}:" .
            ($ipAddress ?: 'unknown');

        if ((int) Cache::get($rateKey, 0) >= 3) {
            return $this->fareFailure(
                'Too many OTP requests have been made. Please try again after 15 minutes.'
            );
        }

        $otp = $this->generateOtp();
        $expiresAt = now()->addMinutes(self::OTP_EXPIRY_MINUTES);

        Cache::put($this->fareOtpKey($scope, $mobile), [
            'otp_hash' => Hash::make($otp),
            'attempts' => 0,
        ], $expiresAt);

        Cache::put(
            $rateKey,
            (int) Cache::get($rateKey, 0) + 1,
            now()->addMinutes(15)
        );

        $results = [
            'sms' => $this->sendSmsOtp($mobile, $otp),
            'whatsapp' => $this->sendWhatsAppOtp($mobile, $otp),
        ];

        $successChannels = collect($results)
            ->filter(fn ($result) => ($result['status'] ?? false) === true)
            ->keys()
            ->values()
            ->toArray();

        if ($successChannels === []) {
            Cache::forget($this->fareOtpKey($scope, $mobile));

            return $this->fareFailure(
                'Unable to send the OTP. Please try again.',
                ['channels' => $results]
            );
        }

        return [
            'success' => true,
            'status' => true,
            'message' => 'OTP sent successfully.',
            'mobile' => $mobile,
            'delivered_on' => $successChannels,
            'expires_in' => self::OTP_EXPIRY_MINUTES * 60,
        ];
    }

    public function verifyFareOtp(
        string $mobile,
        string $otp,
        string $scope
    ): array {
        $mobile = $this->cleanMobile($mobile);
        $otp = preg_replace('/\D+/', '', $otp) ?: '';
        $scope = $this->cleanPurpose($scope) ?: 'default';

        if (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
            return $this->fareFailure('Please enter a valid mobile number.');
        }

        if (!preg_match('/^\d{4}$/', $otp)) {
            return $this->fareFailure('Please enter the complete 4-digit OTP.');
        }

        $key = $this->fareOtpKey($scope, $mobile);
        $stored = Cache::get($key);

        if (!is_array($stored) || empty($stored['otp_hash'])) {
            return $this->fareFailure(
                'The OTP has expired. Please request a new OTP.'
            );
        }

        $attempts = (int) ($stored['attempts'] ?? 0);

        if ($attempts >= self::OTP_MAX_ATTEMPTS) {
            Cache::forget($key);

            return $this->fareFailure(
                'The maximum number of verification attempts has been reached. Please request a new OTP.'
            );
        }

        if (!Hash::check($otp, (string) $stored['otp_hash'])) {
            $stored['attempts'] = $attempts + 1;
            Cache::put(
                $key,
                $stored,
                now()->addMinutes(self::OTP_EXPIRY_MINUTES)
            );

            return $this->fareFailure(
                'The OTP is incorrect. Please try again.',
                [
                    'remaining_attempts' => max(
                        0,
                        self::OTP_MAX_ATTEMPTS - $stored['attempts']
                    ),
                ]
            );
        }

        Cache::forget($key);
        $user = $this->authenticate($mobile, true, false);

        return [
            'success' => true,
            'status' => true,
            'message' => 'OTP verified successfully.',
            'mobile' => $mobile,
            'user' => $user->fresh(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Customer Authentication
    |--------------------------------------------------------------------------
    */

    public function authenticate(
        string $mobileNumber,
        bool $remember = true,
        bool $sendRegistrationMessage = true
    ): User {
        $mobileNumber = $this->cleanMobile($mobileNumber);

        if (!preg_match('/^[6-9]\d{9}$/', $mobileNumber)) {
            throw new \InvalidArgumentException(
                'A valid mobile number is required.'
            );
        }

        $user = $this->findUserByMobile($mobileNumber);
        $isNewUser = $user === null;

        if (!$user) {
            $user = $this->createCustomer($mobileNumber);
        }

        $user->forceFill($this->onlyUserColumns([
            'login_type' => 'otp',
            'is_active' => true,
            'mobile_verified_at' => now(),
        ]));
        $user->save();

        Auth::login($user, $remember);
        RegistrationOrigin::completeUser($user, 'customer');

        if ($isNewUser && $sendRegistrationMessage) {
            $this->sendRegistrationMessages($user, $mobileNumber);
        }

        return $user;
    }

    /*
    |--------------------------------------------------------------------------
    | Purpose-Based OTP
    |--------------------------------------------------------------------------
    |
    | Used for sensitive operations such as wallet recharge approval.
    | This flow verifies the OTP without creating a user login token.
    |
    */

    public function sendPurposeOtp(
        string $mobile,
        string $purpose,
        array $payload = []
    ): array {
        $mobile = $this->cleanMobile($mobile);
        $purpose = $this->cleanPurpose($purpose);
        if ($mobile === '0000000002') {
            return ['status' => false, 'message' => 'Partner review login is available only through the Partner App login endpoint.'];
        }

        if (strlen($mobile) !== 10) {
            return [
                'status' => false,
                'message' => 'Please enter a valid mobile number for OTP delivery.',
            ];
        }

        if ($purpose === '') {
            return [
                'status' => false,
                'message' => 'An OTP purpose is required.',
            ];
        }

        $keys = $this->purposeKeys($mobile, $purpose);

        if (Cache::has($keys['rate'])) {
            return [
                'status' => false,
                'message' =>
                    'Please wait before requesting another OTP.',
                'resend_after' => self::OTP_RESEND_SECONDS,
            ];
        }

        $otp = $this->generateOtp();
        $verificationId = (string) Str::uuid();
        $expiresAt = now()->addMinutes(
            self::OTP_EXPIRY_MINUTES
        );

        $otpData = [
            'otp_hash' => Hash::make($otp),
            'verification_id' => $verificationId,
            'mobile' => $mobile,
            'purpose' => $purpose,
            'payload' => $payload,
            'created_at' => now()->toISOString(),
            'expires_at' => $expiresAt->toISOString(),
        ];

        Cache::put(
            $keys['otp'],
            $otpData,
            $expiresAt
        );

        Cache::put(
            $keys['rate'],
            true,
            now()->addSeconds(self::OTP_RESEND_SECONDS)
        );

        Cache::forget($keys['attempts']);

        $user = User::query()
            ->where('mobile', $mobile)
            ->first();

        $email = $user
            ? $this->getUserEmailForOtp($user, $mobile)
            : '';

        $smsResult = $this->sendSmsOtp($mobile, $otp);
        $whatsAppResult = $this->sendWhatsAppOtp(
            $mobile,
            $otp
        );
        $emailResult = $this->sendEmailOtp(
            $email,
            $otp
        );

        $results = [
            'sms' => $smsResult,
            'whatsapp' => $whatsAppResult,
            'email' => $emailResult,
        ];

        $successChannels = collect($results)
            ->filter(
                fn ($item) =>
                    ($item['status'] ?? false) === true
            )
            ->keys()
            ->values()
            ->toArray();

        $failedChannels = collect($results)
            ->filter(
                fn ($item) =>
                    ($item['status'] ?? false) !== true
            )
            ->keys()
            ->values()
            ->toArray();

        Log::info('Purpose OTP Delivery Summary', [
            'mobile' => $this->maskMobile($mobile),
            'purpose' => $purpose,
            'verification_id' => $verificationId,
            'success_channels' => $successChannels,
            'failed_channels' => $failedChannels,
        ]);

        if (count($successChannels) === 0) {
            $this->clearPurposeOtp($mobile, $purpose);

            return [
                'status' => false,
                'message' =>
                    'Unable to send OTP. Please try again.',
                'channels' => $results,
            ];
        }

        return [
            'status' => true,
            'message' => 'A 4-digit OTP was sent successfully.',
            'verification_id' => $verificationId,
            'mobile' => $this->maskMobile($mobile),
            'purpose' => $purpose,
            'delivered_on' => $successChannels,
            'failed_channels' => $failedChannels,
            'expires_in' => self::OTP_EXPIRY_MINUTES * 60,
            'resend_after' => self::OTP_RESEND_SECONDS,
        ];
    }

    public function verifyPurposeOtp(
        string $mobile,
        string $purpose,
        string $otp,
        ?string $verificationId = null
    ): array {
        $mobile = $this->cleanMobile($mobile);
        $purpose = $this->cleanPurpose($purpose);
        $otp = trim($otp);

        if (strlen($mobile) !== 10) {
            return [
                'status' => false,
                'message' => 'Please enter a valid mobile number for OTP delivery.',
            ];
        }

        if ($purpose === '') {
            return [
                'status' => false,
                'message' => 'An OTP purpose is required.',
            ];
        }

        if (!preg_match('/^\d{4}$/', $otp)) {
            return [
                'status' => false,
                'message' => 'Please enter a valid 4-digit OTP.',
            ];
        }

        $keys = $this->purposeKeys($mobile, $purpose);
        $otpData = Cache::get($keys['otp']);

        if (
            !is_array($otpData) ||
            empty($otpData['otp_hash'])
        ) {
            return [
                'status' => false,
                'message' =>
                    'The OTP has expired or has already been used. Please request a new OTP.',
            ];
        }

        if (
            $verificationId !== null &&
            $verificationId !== '' &&
            !hash_equals(
                (string) ($otpData['verification_id'] ?? ''),
                trim($verificationId)
            )
        ) {
            return [
                'status' => false,
                'message' => 'The OTP verification request is invalid.',
            ];
        }

        if (
            !hash_equals(
                (string) ($otpData['mobile'] ?? ''),
                $mobile
            ) ||
            !hash_equals(
                (string) ($otpData['purpose'] ?? ''),
                $purpose
            )
        ) {
            $this->clearPurposeOtp($mobile, $purpose);

            return [
                'status' => false,
                'message' => 'The OTP verification request details do not match.',
            ];
        }

        $attempts = (int) Cache::get(
            $keys['attempts'],
            0
        );

        if ($attempts >= self::OTP_MAX_ATTEMPTS) {
            $this->clearPurposeOtp($mobile, $purpose);

            return [
                'status' => false,
                'message' =>
                    'The maximum number of OTP verification attempts has been exceeded. Please request a new OTP.',
                'remaining_attempts' => 0,
            ];
        }

        if (!Hash::check($otp, $otpData['otp_hash'])) {
            $attempts++;

            Cache::put(
                $keys['attempts'],
                $attempts,
                now()->addMinutes(self::OTP_EXPIRY_MINUTES)
            );

            $remainingAttempts = max(
                0,
                self::OTP_MAX_ATTEMPTS - $attempts
            );

            if ($remainingAttempts === 0) {
                $this->clearPurposeOtp($mobile, $purpose);
            }

            Log::warning('Purpose OTP verification failed', [
                'mobile' => $this->maskMobile($mobile),
                'purpose' => $purpose,
                'attempts' => $attempts,
            ]);

            return [
                'status' => false,
                'message' => $remainingAttempts > 0
                    ? "Invalid OTP. {$remainingAttempts} attempt(s) remaining."
                    : 'The maximum number of OTP verification attempts has been exceeded. Please request a new OTP.',
                'remaining_attempts' => $remainingAttempts,
            ];
        }

        $payload = is_array($otpData['payload'] ?? null)
            ? $otpData['payload']
            : [];

        $verifiedData = [
            'verification_id' =>
                $otpData['verification_id'] ?? null,
            'mobile' => $mobile,
            'purpose' => $purpose,
            'payload' => $payload,
            'verified_at' => now()->toISOString(),
        ];

        $this->clearPurposeOtp($mobile, $purpose);

        Log::info('Purpose OTP verified successfully', [
            'mobile' => $this->maskMobile($mobile),
            'purpose' => $purpose,
            'verification_id' =>
                $verifiedData['verification_id'],
        ]);

        return [
            'status' => true,
            'message' => 'OTP verified successfully.',
            'data' => $verifiedData,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | User Helpers
    |--------------------------------------------------------------------------
    */

    private function createOrUpdateUser(
        string $mobile,
        string $otp,
        $expireAt
    ): User {
        $user = $this->findLoginUserByMobile($mobile);

        if (!$user) {
            $user = $this->createCustomer($mobile, verified: false);
        }

        if (! $user->is_active) {
            throw new \RuntimeException('Inactive login account.');
        }

        $user->forceFill($this->onlyUserColumns([
            'mobile' => $mobile,
            'otp' => $otp,
            'otp_expire_at' => $expireAt,
            'login_type' => 'otp',
        ]));
        $user->save();
        $user->refresh();
        if ((string) $user->mobile !== $mobile) {
            throw new \RuntimeException('The users.mobile column cannot preserve the full number.');
        }

        return $user;
    }

    private function findLoginUserByMobile(string $mobile): ?User
    {
        $aliases = CustomerPhoneNumber::aliases($mobile);
        $columns = array_values(array_filter(['mobile', 'phone', 'mobile_number'],
            fn ($column) => Schema::hasColumn('users', $column)));
        $users = User::query()->where(function ($query) use ($columns, $aliases): void {
            foreach ($columns as $column) {
                $query->orWhereIn($column, $aliases);
            }
            // Only synthetic addresses are aliases; never claim a real Gmail account.
            $query->orWhereIn('email', array_map(fn ($value) => $value . '@duracabs.local', $aliases));
        })->limit(2)->get();
        if ($users->count() > 1) {
            throw new \RuntimeException('Ambiguous phone account aliases.');
        }
        return $users->first();
    }

    private function findUserByMobile(string $mobile): ?User
    {
        $query = User::query();
        $hasCondition = false;

        foreach (['mobile', 'phone', 'mobile_number'] as $column) {
            if (!Schema::hasColumn('users', $column)) {
                continue;
            }

            $hasCondition
                ? $query->orWhere($column, $mobile)
                : $query->where($column, $mobile);
            $hasCondition = true;
        }

        if (Schema::hasColumn('users', 'email')) {
            $emails = [
                $mobile . '@duracabs.local',
                $mobile . '@gmail.com',
            ];

            foreach ($emails as $email) {
                $hasCondition
                    ? $query->orWhere('email', $email)
                    : $query->where('email', $email);
                $hasCondition = true;
            }
        }

        return $hasCondition ? $query->first() : null;
    }

    private function createCustomer(string $mobile, bool $verified = true): User
    {
        $data = $this->onlyUserColumns([
            'mobile' => $mobile,
            'name' => $mobile,
            'email' => $this->uniquePlaceholderEmail($mobile),
            'password' => Hash::make(Str::random(32)),
            'login_type' => 'otp',
            'is_active' => true,
            'mobile_verified_at' => $verified ? now() : null,
        ]);

        $user = new User();
        $user->forceFill($data);
        $user->save();

        return $user;
    }

    private function uniquePlaceholderEmail(string $mobile): string
    {
        $email = $mobile . '@duracabs.local';

        if (!Schema::hasColumn('users', 'email')) {
            return $email;
        }

        if (!User::query()->where('email', $email)->exists()) {
            return $email;
        }

        return 'guest-' . $mobile . '-' .
            Str::lower(Str::random(6)) . '@duracabs.local';
    }

    private function sendRegistrationMessages(
        User $user,
        string $mobile
    ): void {
        try {
            $loginUrl = function_exists('route')
                ? route('login')
                : url('/login');
        } catch (Throwable) {
            $loginUrl = url('/login');
        }

        $customerMessage = implode("\n", [
            'Dear User,',
            '',
            'Your Dura Cabs registration has been completed successfully.',
            '',
            'User ID: ' . ($user->email ?? $mobile),
            'Mobile Number: ' . $mobile,
            '',
            'Login: ' . $loginUrl,
        ]);

        $this->sendWhatsAppMessageSafely(
            $mobile,
            $customerMessage,
            'customer'
        );

        // The admin notice is handled once by RegistrationOrigin after successful
        // verification. Keep this legacy helper for the customer's welcome only.
    }

    private function sendWhatsAppMessageSafely(
        string $mobile,
        string $message,
        string $recipient
    ): void {
        try {
            if (class_exists(WhatsAppService::class)) {
                WhatsAppService::send($mobile, $message);
            }
        } catch (Throwable $exception) {
            Log::warning('OTP registration WhatsApp message failed.', [
                'recipient' => $recipient,
                'mobile' => $this->maskMobile($mobile),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function getUserEmailForOtp(
        User $user,
        string $mobile
    ): string {
        $email = trim((string) ($user->email ?? ''));

        if (
            $email === '' ||
            str_contains($email, '@duracabs.local')
        ) {
            return '';
        }

        return $email;
    }

    /*
    |--------------------------------------------------------------------------
    | OTP Delivery
    |--------------------------------------------------------------------------
    */

    protected function sendSmsOtp(
        string $mobile,
        string $otp
    ): array {
        $message =
            "This is your 4-digit OTP {$otp} for Mobile Number Verification on Duracabs.com. Valid for 5 Minutes only. From DURA CABS";

        $params = [
            'key' => config('services.sambsms.api_key'),
            'entity' => config('services.sambsms.entity_id'),
            'tempid' => config('services.sambsms.template_id'),
            'campaign' => 0,
            'routeid' => config('services.sambsms.route_id'),
            'type' => 'text',
            'contacts' => $mobile,
            'senderid' => config('services.sambsms.sender_id'),
            'msg' => $message,
        ];

        try {
            $response = Http::timeout(15)
                ->get(
                    'http://manage.sambsms.com/app/smsapi/index.php',
                    $params
                );

            $body = trim($response->body());

            Log::info('Dura Cabs SMS OTP Response', [
                'mobile' => $this->maskMobile($mobile),
                'http_code' => $response->status(),
                'response' => $body,
            ]);

            if (!$response->successful()) {
                return [
                    'status' => false,
                    'channel' => 'sms',
                    'message' => 'The SMS gateway returned an HTTP error.',
                    'response' => $body,
                ];
            }

            $upperBody = strtoupper($body);

            if (
                str_contains($upperBody, 'ERR') ||
                str_contains($upperBody, 'ERROR') ||
                str_contains($upperBody, 'INVALID') ||
                str_contains($upperBody, 'FAILED')
            ) {
                return [
                    'status' => false,
                    'channel' => 'sms',
                    'message' =>
                        'The SMS gateway rejected the request.',
                    'response' => $body,
                ];
            }

            return [
                'status' => true,
                'channel' => 'sms',
                'message' => 'SMS OTP sent successfully.',
                'response' => $body,
            ];
        } catch (Throwable $e) {
            Log::error('Dura Cabs SMS OTP Failed', [
                'mobile' => $this->maskMobile($mobile),
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => false,
                'channel' => 'sms',
                'message' => 'The SMS service failed to send the OTP.',
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function sendWhatsAppOtp(
        string $mobile,
        string $otp,
        bool $authenticationOnly = false
    ): array {
        try {
            if (!class_exists(WhatsAppService::class)) {
                return [
                    'status' => false,
                    'channel' => 'whatsapp',
                    'message' =>
                        'The WhatsApp service is not available.',
                ];
            }

            if ($authenticationOnly) {
                $result = WhatsAppService::sendAuthenticationOtp($mobile, $otp);
                return ['status' => ($result['status'] ?? false) === true,
                    'channel' => 'whatsapp'];
            }

            $sent = WhatsAppService::sendOtp(
                $mobile,
                $otp
            );

            return [
                'status' => (bool) $sent,
                'channel' => 'whatsapp',
                'message' => $sent
                    ? 'WhatsApp OTP sent successfully.'
                    : 'The WhatsApp service failed to send the OTP.',
            ];
        } catch (Throwable $e) {
            Log::error('Dura Cabs WhatsApp OTP Failed', [
                'mobile' => $this->maskMobile($mobile),
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => false,
                'channel' => 'whatsapp',
                'message' => 'The WhatsApp service encountered an error.',
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function sendEmailOtp(
        string $email,
        string $otp
    ): array {
        try {
            if (
                $email === '' ||
                str_contains($email, '@duracabs.local')
            ) {
                return [
                    'status' => false,
                    'channel' => 'email',
                    'message' =>
                        'A valid customer email address is not available.',
                ];
            }

            if (!class_exists(EmailService::class)) {
                return [
                    'status' => false,
                    'channel' => 'email',
                    'message' =>
                        'The email service is not available.',
                ];
            }

            $sent = EmailService::sendOtp(
                $email,
                $otp
            );

            return [
                'status' => (bool) $sent,
                'channel' => 'email',
                'message' => $sent
                    ? 'Email OTP sent successfully.'
                    : 'The email service failed to send the OTP.',
            ];
        } catch (Throwable $e) {
            Log::error('Dura Cabs Email OTP Failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => false,
                'channel' => 'email',
                'message' => 'The email service encountered an error.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Internal Helpers
    |--------------------------------------------------------------------------
    */

    private function generateOtp(): string
    {
        return (string) random_int(1000, 9999);
    }

    private function cleanMobile(string $mobile): string
    {
        $mobile = preg_replace(
            '/\D+/',
            '',
            $mobile
        ) ?? '';

        if (
            strlen($mobile) > 10 &&
            str_starts_with($mobile, '91')
        ) {
            return substr($mobile, -10);
        }

        return $mobile;
    }

    private function cleanPurpose(string $purpose): string
    {
        $purpose = strtolower(trim($purpose));

        return preg_replace(
            '/[^a-z0-9_\-]/',
            '_',
            $purpose
        ) ?? '';
    }

    private function purposeKeys(
        string $mobile,
        string $purpose
    ): array {
        $base = "purpose_otp:{$purpose}:{$mobile}";

        return [
            'otp' => $base,
            'rate' => $base . ':rate',
            'attempts' => $base . ':attempts',
        ];
    }

    private function fareOtpKey(string $scope, string $mobile): string
    {
        return "fare-otp:{$scope}:{$mobile}";
    }

    private function fareFailure(
        string $message,
        array $extra = []
    ): array {
        return array_merge([
            'success' => false,
            'status' => false,
            'message' => $message,
        ], $extra);
    }

    private function clearPurposeOtp(
        string $mobile,
        string $purpose
    ): void {
        $keys = $this->purposeKeys(
            $mobile,
            $purpose
        );

        Cache::forget($keys['otp']);
        Cache::forget($keys['attempts']);
    }

    private function clearLoginOtp(
        string $mobile,
        ?User $user = null
    ): void {
        Cache::forget('otp_' . $mobile);
        Cache::forget('otp_attempt_' . $mobile);

        if ($user) {
            $user->update(
                $this->onlyUserColumns([
                    'otp' => null,
                    'otp_expire_at' => null,
                ])
            );
        }
    }

    private function maskMobile(string $mobile): string
    {
        if (strlen($mobile) < 4) {
            return $mobile;
        }

        return str_repeat(
            '*',
            max(0, strlen($mobile) - 4)
        ) . substr($mobile, -4);
    }

    private function onlyUserColumns(
        array $data
    ): array {
        return collect($data)
            ->filter(
                fn ($value, $key) =>
                    Schema::hasColumn('users', $key)
            )
            ->toArray();
    }
}
