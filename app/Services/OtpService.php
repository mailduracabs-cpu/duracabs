<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class OtpService
{
    private const OTP_EXPIRY_MINUTES = 5;
    private const OTP_RESEND_SECONDS = 30;
    private const OTP_MAX_ATTEMPTS = 5;

    /*
    |--------------------------------------------------------------------------
    | Customer Login OTP
    |--------------------------------------------------------------------------
    */

    public function send(string $mobile): array
    {
        $mobile = $this->cleanMobile($mobile);

        if (strlen($mobile) !== 10) {
            return [
                'status' => false,
                'message' => 'Please enter valid 10 digit mobile number.',
            ];
        }

        $rateKey = 'otp_rate_' . $mobile;

        if (Cache::has($rateKey)) {
            return [
                'status' => false,
                'message' => 'Please wait before requesting another OTP.',
            ];
        }

        $otp = $this->generateOtp();
        $expireAt = now()->addMinutes(self::OTP_EXPIRY_MINUTES);

        Cache::put('otp_' . $mobile, $otp, $expireAt);
        Cache::put(
            $rateKey,
            true,
            now()->addSeconds(self::OTP_RESEND_SECONDS)
        );

        $user = $this->createOrUpdateUser(
            $mobile,
            $otp,
            $expireAt
        );

        $email = $this->getUserEmailForOtp($user, $mobile);

        $smsResult = $this->sendSmsOtp($mobile, $otp);
        $whatsAppResult = $this->sendWhatsAppOtp($mobile, $otp);
        $emailResult = $this->sendEmailOtp($email, $otp);

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

        Log::info('Dura Cabs OTP Delivery Summary', [
            'mobile' => $mobile,
            'email' => $email,
            'success_channels' => $successChannels,
            'failed_channels' => $failedChannels,
            'results' => $results,
        ]);

        if (count($successChannels) === 0) {
            Cache::forget('otp_' . $mobile);

            return [
                'status' => false,
                'message' => 'Unable to send OTP. Please try again.',
                'channels' => $results,
            ];
        }

        return [
            'status' => true,
            'message' =>
                'OTP sent successfully. Please check SMS, WhatsApp or Email.',
            'delivered_on' => $successChannels,
            'failed_channels' => $failedChannels,
            'expires_in' => self::OTP_EXPIRY_MINUTES * 60,
            'resend_after' => self::OTP_RESEND_SECONDS,
        ];
    }

    public function verify(string $mobile, string $otp): array
    {
        $mobile = $this->cleanMobile($mobile);

        if (strlen($mobile) !== 10) {
            return [
                'status' => false,
                'message' => 'Invalid mobile number.',
            ];
        }

        $otp = trim($otp);

        if (!preg_match('/^\d{4}$/', $otp)) {
            return [
                'status' => false,
                'message' => 'Please enter valid 4 digit OTP.',
            ];
        }

        $savedOtp = Cache::get('otp_' . $mobile);

        $user = User::query()
            ->where('mobile', $mobile)
            ->orWhere('email', $mobile . '@duracabs.local')
            ->first();

        if (!$user) {
            return [
                'status' => false,
                'message' => 'User not found. Please resend OTP.',
            ];
        }

        $dbOtp = Schema::hasColumn('users', 'otp')
            ? $user->otp
            : null;

        if (!$savedOtp && !$dbOtp) {
            return [
                'status' => false,
                'message' => 'OTP expired. Please resend OTP.',
            ];
        }

        $attemptKey = 'otp_attempt_' . $mobile;
        $attempts = (int) Cache::get($attemptKey, 0);

        if ($attempts >= self::OTP_MAX_ATTEMPTS) {
            $this->clearLoginOtp($mobile, $user);

            return [
                'status' => false,
                'message' =>
                    'Maximum OTP attempts exceeded. Please request a new OTP.',
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
                    : 'Invalid OTP. Maximum attempts exceeded.',
                'remaining_attempts' => $remainingAttempts,
            ];
        }

        $this->clearLoginOtp($mobile, $user);

        $user->update(
            $this->onlyUserColumns([
                'otp' => null,
                'otp_expire_at' => null,
                'login_type' => 'otp',
                'is_active' => true,
            ])
        );

        $token = method_exists($user, 'createToken')
            ? $user
                ->createToken('dura_app_token')
                ->plainTextToken
            : 'duracabs_live_token_' . $mobile;

        return [
            'status' => true,
            'message' => 'Login Successful',
            'token' => $token,
            'user' => $user->fresh(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Purpose-Based OTP
    |--------------------------------------------------------------------------
    |
    | Wallet recharge approval jaise sensitive operations ke liye.
    | Is flow me user login token create nahi hota.
    |
    */

    public function sendPurposeOtp(
        string $mobile,
        string $purpose,
        array $payload = []
    ): array {
        $mobile = $this->cleanMobile($mobile);
        $purpose = $this->cleanPurpose($purpose);

        if (strlen($mobile) !== 10) {
            return [
                'status' => false,
                'message' => 'Invalid OTP receiver mobile number.',
            ];
        }

        if ($purpose === '') {
            return [
                'status' => false,
                'message' => 'OTP purpose is required.',
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
            'message' => '4 digit OTP sent successfully.',
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
                'message' => 'Invalid OTP receiver mobile number.',
            ];
        }

        if ($purpose === '') {
            return [
                'status' => false,
                'message' => 'OTP purpose is required.',
            ];
        }

        if (!preg_match('/^\d{4}$/', $otp)) {
            return [
                'status' => false,
                'message' => 'Please enter valid 4 digit OTP.',
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
                    'OTP expired or already used. Please request a new OTP.',
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
                'message' => 'Invalid OTP verification request.',
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
                'message' => 'OTP verification request mismatch.',
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
                    'Maximum OTP attempts exceeded. Please request a new OTP.',
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
                    : 'Maximum OTP attempts exceeded. Please request a new OTP.',
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
        $user = User::firstOrCreate(
            ['email' => $mobile . '@duracabs.local'],
            $this->onlyUserColumns([
                'name' => 'Dura Cabs User',
                'email' => $mobile . '@duracabs.local',
                'mobile' => $mobile,
                'password' => Hash::make($mobile),
                'login_type' => 'otp',
                'is_active' => true,
            ])
        );

        $user->update(
            $this->onlyUserColumns([
                'mobile' => $mobile,
                'otp' => $otp,
                'otp_expire_at' => $expireAt,
                'login_type' => 'otp',
                'is_active' => true,
            ])
        );

        return $user;
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

    private function sendSmsOtp(
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
                    'message' => 'SMS HTTP error',
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
                        'SMS gateway rejected request',
                    'response' => $body,
                ];
            }

            return [
                'status' => true,
                'channel' => 'sms',
                'message' => 'SMS OTP sent',
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
                'message' => 'SMS service failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function sendWhatsAppOtp(
        string $mobile,
        string $otp
    ): array {
        try {
            if (!class_exists(WhatsAppService::class)) {
                return [
                    'status' => false,
                    'channel' => 'whatsapp',
                    'message' =>
                        'WhatsApp service not available',
                ];
            }

            $sent = WhatsAppService::sendOtp(
                $mobile,
                $otp
            );

            return [
                'status' => (bool) $sent,
                'channel' => 'whatsapp',
                'message' => $sent
                    ? 'WhatsApp OTP sent'
                    : 'WhatsApp OTP failed',
            ];
        } catch (Throwable $e) {
            Log::error('Dura Cabs WhatsApp OTP Failed', [
                'mobile' => $this->maskMobile($mobile),
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => false,
                'channel' => 'whatsapp',
                'message' => 'WhatsApp service failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function sendEmailOtp(
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
                        'Valid customer email not available',
                ];
            }

            if (!class_exists(EmailService::class)) {
                return [
                    'status' => false,
                    'channel' => 'email',
                    'message' =>
                        'Email service not available',
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
                    ? 'Email OTP sent'
                    : 'Email OTP failed',
            ];
        } catch (Throwable $e) {
            Log::error('Dura Cabs Email OTP Failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => false,
                'channel' => 'email',
                'message' => 'Email service failed',
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
                fn ($value) => !is_null($value)
            )
            ->filter(
                fn ($value, $key) =>
                    Schema::hasColumn('users', $key)
            )
            ->toArray();
    }
}