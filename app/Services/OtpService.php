<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OtpService
{
    public function send(string $mobile): array
    {
        $otp = (string) random_int(1000, 9999);

        $message = "This is your 4-digit OTP {$otp} for Mobile Number Verification on Duracabs.com. Valid for 5 Minutes only. From DURA CABS";

        $params = [
            'key'      => config('services.sambsms.api_key'),
            'entity'   => config('services.sambsms.entity_id'),
            'tempid'   => config('services.sambsms.template_id'),
            'campaign' => 0,
            'routeid'  => config('services.sambsms.route_id'),
            'type'     => 'text',
            'contacts' => $mobile,
            'senderid' => config('services.sambsms.sender_id'),
            'msg'      => $message,
        ];

        $response = Http::timeout(20)->get('http://manage.sambsms.com/app/smsapi/index.php', $params);

        Log::info('V1 SambSMS OTP Response', [
            'mobile' => $mobile,
            'http_code' => $response->status(),
            'gateway_response' => $response->body(),
        ]);

        if (!$response->successful()) {
            return [
                'status' => false,
                'message' => 'Unable to send OTP. SMS gateway error.',
                'gateway_response' => $response->body(),
            ];
        }

        $body = trim($response->body());

        if (
            str_contains(strtoupper($body), 'ERR') ||
            str_contains(strtoupper($body), 'INVALID') ||
            str_contains(strtoupper($body), 'ERROR')
        ) {
            return [
                'status' => false,
                'message' => 'SMS gateway rejected request.',
                'gateway_response' => $body,
            ];
        }

        Cache::put('otp_' . $mobile, $otp, now()->addMinutes(5));

        return [
            'status' => true,
            'message' => 'OTP sent successfully',
            'gateway_response' => $body,
        ];
    }

    public function verify(string $mobile, string $otp): array
    {
        $savedOtp = Cache::get('otp_' . $mobile);

        if (!$savedOtp) {
            return [
                'status' => false,
                'message' => 'OTP expired. Please resend OTP.',
            ];
        }

        if ($savedOtp !== $otp) {
            return [
                'status' => false,
                'message' => 'Invalid OTP',
            ];
        }

        Cache::forget('otp_' . $mobile);

        return [
            'status' => true,
            'message' => 'Login Successful',
            'token' => 'duracabs_live_token_' . $mobile,
            'user' => [
                'id' => 1,
                'name' => 'Dura Cabs User',
                'mobile' => $mobile,
            ],
        ];
    }
}
