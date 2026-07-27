<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class OtpCustomerAuthService
{
    public function authenticate(string $mobileNumber): User
    {
        $mobileNumber = trim($mobileNumber);

        if ($mobileNumber === '') {
            throw new \InvalidArgumentException('Mobile number is required.');
        }

        $user = User::where('mobile', $mobileNumber)->first();
        $isNewUser = $user === null;

        if ($isNewUser) {
            $user = User::create([
                'mobile' => $mobileNumber,
                'name' => $mobileNumber,
                'email' => $this->uniquePlaceholderEmail($mobileNumber),
                'password' => Hash::make($mobileNumber),
            ]);
        }

        Auth::login($user);

        if ($isNewUser) {
            $this->sendRegistrationMessages($user, $mobileNumber);
        }

        return $user;
    }

    private function uniquePlaceholderEmail(string $mobileNumber): string
    {
        $base = preg_replace('/\D+/', '', $mobileNumber) ?: 'customer';
        $email = $base . '@gmail.com';

        if (! User::where('email', $email)->exists()) {
            return $email;
        }

        return $base . '+' . now()->format('YmdHis') . '@gmail.com';
    }

    private function sendRegistrationMessages(User $user, string $mobileNumber): void
    {
        $loginUrl = route('login');

        $customerMessage = implode("\n", [
            'Dear User,',
            '',
            'We are happy to inform that your registration request has been approved.',
            '',
            'User ID: ' . $user->email,
            'Password: ' . $mobileNumber,
            '',
            'Click and login: ' . $loginUrl,
        ]);

        $this->sendWhatsAppSafely($mobileNumber, $customerMessage, 'customer');

        $adminMobile = trim((string) config('services.admin.mobile', env('ADMIN_MOBILE')));

        if ($adminMobile === '') {
            return;
        }

        $adminMessage = implode("\n", [
            'Dear Duracabs,',
            '',
            'A new customer account registration has been completed.',
            '',
            'Name: ' . $user->name,
            'Mobile Number: ' . $user->mobile,
            'User ID: ' . $user->email,
            '',
            'Login: ' . $loginUrl,
        ]);

        $this->sendWhatsAppSafely($adminMobile, $adminMessage, 'admin');
    }

    private function sendWhatsAppSafely(string $mobile, string $message, string $recipient): void
    {
        try {
            WhatsAppService::send($mobile, $message);
        } catch (\Throwable $exception) {
            Log::warning('OTP registration WhatsApp message failed.', [
                'recipient' => $recipient,
                'mobile' => $mobile,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
