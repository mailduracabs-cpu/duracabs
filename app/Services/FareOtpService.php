<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FareOtpService
{
    public function send(string $mobile, string $scope, ?string $ipAddress = null): array
    {
        $mobile = $this->normaliseMobile($mobile);

        if (! preg_match('/^[6-9]\d{9}$/', $mobile)) {
            return $this->failure('Valid 10 digit mobile number enter karein.');
        }

        $scope = $this->normaliseScope($scope);
        $rateKey = "fare-otp:{$scope}:send:{$mobile}:" . ($ipAddress ?: 'unknown');

        if ((int) Cache::get($rateKey, 0) >= 3) {
            return $this->failure('Bahut zyada OTP requests hui hain. 15 minute baad try karein.');
        }

        $otp = (string) random_int(1000, 9999);
        Cache::put($this->otpKey($scope, $mobile), [
            'hash' => hash('sha256', $otp),
            'attempts' => 0,
        ], now()->addMinutes(5));
        Cache::put($rateKey, (int) Cache::get($rateKey, 0) + 1, now()->addMinutes(15));

        $this->deliver($mobile, $otp, $scope);

        return [
            'success' => true,
            'mobile' => $mobile,
        ];
    }

    public function verify(string $mobile, string $otp, string $scope): array
    {
        $mobile = $this->normaliseMobile($mobile);
        $otp = preg_replace('/\D+/', '', $otp) ?: '';

        if (! preg_match('/^\d{4}$/', $otp)) {
            return $this->failure('Complete 4 digit OTP enter karein.');
        }

        $scope = $this->normaliseScope($scope);
        $key = $this->otpKey($scope, $mobile);
        $stored = Cache::get($key);

        if (! is_array($stored)) {
            return $this->failure('OTP expire ho gaya. Naya OTP bhejein.');
        }

        $attempts = (int) ($stored['attempts'] ?? 0);
        if ($attempts >= 5) {
            Cache::forget($key);
            return $this->failure('Maximum attempts complete. Naya OTP bhejein.');
        }

        if (! hash_equals((string) ($stored['hash'] ?? ''), hash('sha256', $otp))) {
            $stored['attempts'] = $attempts + 1;
            Cache::put($key, $stored, now()->addMinutes(5));
            return $this->failure('OTP galat hai. Dobara try karein.');
        }

        Cache::forget($key);
        $user = $this->findOrCreateUser($mobile);
        if ($user) {
            Auth::login($user, true);
        }

        return [
            'success' => true,
            'mobile' => $mobile,
            'user' => $user,
        ];
    }

    public function normaliseMobile(string $mobile): string
    {
        $mobile = preg_replace('/\D+/', '', $mobile) ?: '';
        if (strlen($mobile) === 12 && str_starts_with($mobile, '91')) {
            $mobile = substr($mobile, 2);
        }
        return $mobile;
    }

    private function deliver(string $mobile, string $otp, string $scope): void
    {
        $message = "Your DuraCabs OTP is {$otp}. It is valid for 5 minutes.";

        foreach (['App\\Services\\OtpService', 'App\\Services\\SMSService', 'App\\Services\\SmsService'] as $serviceClass) {
            if (! class_exists($serviceClass)) {
                continue;
            }

            try {
                $service = app($serviceClass);
                foreach (['sendOtp', 'send', 'sendSMS'] as $method) {
                    if (! method_exists($service, $method)) {
                        continue;
                    }
                    try {
                        $service->{$method}($mobile, $otp, $message);
                    } catch (\ArgumentCountError) {
                        try {
                            $service->{$method}($mobile, $message);
                        } catch (\ArgumentCountError) {
                            $service->{$method}($mobile, $otp);
                        }
                    }
                    return;
                }
            } catch (\Throwable $exception) {
                Log::warning('Fare OTP provider failed.', [
                    'scope' => $scope,
                    'mobile' => $mobile,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        Log::info('DuraCabs fare OTP', [
            'scope' => $scope,
            'mobile' => $mobile,
            'otp' => $otp,
        ]);
    }

    private function findOrCreateUser(string $mobile): ?User
    {
        try {
            $mobileColumn = collect(['mobile', 'phone', 'mobile_number'])
                ->first(fn (string $column): bool => Schema::hasColumn('users', $column));

            if (! $mobileColumn) {
                return null;
            }

            $user = User::query()->where($mobileColumn, $mobile)->first();
            if ($user) {
                return $user;
            }

            $user = new User();
            $user->forceFill([$mobileColumn => $mobile]);
            if (Schema::hasColumn('users', 'name')) {
                $user->name = 'DuraCabs Customer';
            }
            if (Schema::hasColumn('users', 'email')) {
                $user->email = 'guest-' . $mobile . '-' . Str::lower(Str::random(5)) . '@duracabs.local';
            }
            if (Schema::hasColumn('users', 'password')) {
                $user->password = bcrypt(Str::random(32));
            }
            if (Schema::hasColumn('users', 'mobile_verified_at')) {
                $user->mobile_verified_at = now();
            }
            $user->save();

            return $user;
        } catch (\Throwable $exception) {
            Log::warning('Fare OTP user authentication failed.', [
                'mobile' => $mobile,
                'error' => $exception->getMessage(),
            ]);
            return null;
        }
    }

    private function otpKey(string $scope, string $mobile): string
    {
        return "fare-otp:{$scope}:{$mobile}";
    }

    private function normaliseScope(string $scope): string
    {
        return preg_replace('/[^a-z0-9_-]+/i', '-', trim($scope)) ?: 'default';
    }

    private function failure(string $message): array
    {
        return ['success' => false, 'message' => $message];
    }
}
