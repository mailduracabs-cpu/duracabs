<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    public static function send(string $number, string $message): bool
    {
        $result = self::sendMessage($number, $message);

        return $result['status'] === true;
    }

    public static function sendMessage(string $number, string $message): array
    {
        $apiKey = env('WHATSAPP_APIKEY');
        $sender = env('WHATSAPP_SENDER');

        $cleanNumber = self::cleanNumber($number);

        if (!$apiKey || !$sender) {
            Log::warning('Dura Cabs WhatsApp credentials missing', [
                'number' => $cleanNumber,
            ]);

            return [
                'status' => false,
                'channel' => 'whatsapp',
                'message' => 'WhatsApp credentials missing',
            ];
        }

        try {
            $response = Http::withoutVerifying()->timeout(20)->post(
                'https://whatsapp.sambcart.com/send-message',
                [
                    'api_key' => $apiKey,
                    'sender' => $sender,
                    'number' => $cleanNumber,
                    'message' => $message,
                ]
            );

            $body = trim($response->body());

            Log::info('Dura Cabs WhatsApp Response', [
                'number' => $cleanNumber,
                'http_code' => $response->status(),
                'response' => $body,
            ]);

            if (!$response->successful()) {
                return [
                    'status' => false,
                    'channel' => 'whatsapp',
                    'message' => 'WhatsApp HTTP error',
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
                    'channel' => 'whatsapp',
                    'message' => 'WhatsApp gateway rejected request',
                    'response' => $body,
                ];
            }

            return [
                'status' => true,
                'channel' => 'whatsapp',
                'message' => 'WhatsApp sent successfully',
                'response' => $body,
            ];
        } catch (\Throwable $e) {
            Log::error('Dura Cabs WhatsApp send failed', [
                'number' => $cleanNumber,
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

    public static function sendOtp(string $number, string $otp): bool
    {
        $message =
            "Your Dura Cabs OTP is {$otp}. " .
            "Valid for 5 minutes. Do not share this OTP with anyone.";

        return self::send($number, $message);
    }

    public static function bookingConfirmation(string $number, $data): bool
    {
        if (is_string($data)) {
            return self::send($number, $data);
        }

        $message =
            "Dear " . ($data['customer_name'] ?? 'Customer') . ",\n" .
            "Your Dura Cabs booking is confirmed.\n" .
            "Booking ID: " . ($data['booking_id'] ?? 'N/A') . "\n" .
            "Pickup: " . ($data['pickup'] ?? $data['pickup_city'] ?? 'N/A') . "\n" .
            "Drop: " . ($data['drop'] ?? $data['drop_city'] ?? 'N/A') . "\n" .
            "Date: " . ($data['date'] ?? $data['pickup_date'] ?? 'N/A') . "\n" .
            "Time: " . ($data['time'] ?? $data['pickup_time'] ?? 'N/A') . "\n" .
            "Amount: Rs. " . ($data['amount'] ?? $data['grand_total'] ?? 'As discussed') . "\n" .
            "Thank you for choosing Dura Cabs.";

        return self::send($number, $message);
    }

    public static function bookingCancellation(string $number, array $data): bool
    {
        $message =
            "Dear " . ($data['customer_name'] ?? 'Customer') . ",\n" .
            "Your Dura Cabs booking has been cancelled.\n" .
            "Booking ID: " . ($data['booking_id'] ?? 'N/A') . "\n" .
            "Reason: " . ($data['reason'] ?? 'As requested') . "\n" .
            "Regards, Dura Cabs";

        return self::send($number, $message);
    }

    public static function driverDetails(string $number, $data): bool
    {
        if (is_string($data)) {
            return self::send($number, $data);
        }

        $message =
            "Dura Cabs Driver Details\n" .
            "Booking ID: " . ($data['booking_id'] ?? 'N/A') . "\n" .
            "Driver: " . ($data['driver_name'] ?? 'N/A') . "\n" .
            "Mobile: " . ($data['driver_mobile'] ?? 'N/A') . "\n" .
            "Vehicle: " . ($data['vehicle_name'] ?? $data['car_name'] ?? 'N/A') . "\n" .
            "Vehicle No: " . ($data['vehicle_number'] ?? 'N/A');

        return self::send($number, $message);
    }

    public static function carDetails(string $number, array $data): bool
    {
        $message =
            "Dura Cabs Vehicle Details\n" .
            "Booking ID: " . ($data['booking_id'] ?? 'N/A') . "\n" .
            "Vehicle: " . ($data['vehicle_name'] ?? $data['car_name'] ?? 'N/A') . "\n" .
            "Vehicle No: " . ($data['vehicle_number'] ?? 'N/A') . "\n" .
            "Fuel: " . ($data['fuel_type'] ?? 'N/A') . "\n" .
            "Pickup: " . ($data['pickup_location'] ?? $data['pickup'] ?? 'N/A');

        return self::send($number, $message);
    }

    public static function paymentReminder(string $number, $data): bool
    {
        if (is_string($data)) {
            return self::send($number, $data);
        }

        $message =
            "Dura Cabs Payment Reminder\n" .
            "Booking ID: " . ($data['booking_id'] ?? 'N/A') . "\n" .
            "Pending Amount: Rs. " . ($data['amount'] ?? $data['pending_amount'] ?? '0') . "\n" .
            "Please complete your payment.";

        return self::send($number, $message);
    }

    public static function paymentReceipt(string $number, array $data): bool
    {
        $message =
            "Dura Cabs Payment Received\n" .
            "Booking ID: " . ($data['booking_id'] ?? 'N/A') . "\n" .
            "Payment ID: " . ($data['payment_id'] ?? 'N/A') . "\n" .
            "Amount: Rs. " . ($data['amount'] ?? '0') . "\n" .
            "Thank you.";

        return self::send($number, $message);
    }

    public static function invoice(string $number, array $data): bool
    {
        $message =
            "Dura Cabs Invoice\n" .
            "Booking ID: " . ($data['booking_id'] ?? 'N/A') . "\n" .
            "Amount: Rs. " . ($data['amount'] ?? $data['grand_total'] ?? '0') . "\n" .
            "Invoice generated successfully.";

        return self::send($number, $message);
    }

    public static function refund(string $number, array $data): bool
    {
        $message =
            "Dura Cabs Refund Update\n" .
            "Booking ID: " . ($data['booking_id'] ?? 'N/A') . "\n" .
            "Refund Amount: Rs. " . ($data['refund_amount'] ?? $data['amount'] ?? '0') . "\n" .
            "Status: " . ($data['status'] ?? 'Processing');

        return self::send($number, $message);
    }

    public static function offer(string $number, $data): bool
    {
        if (is_string($data)) {
            return self::send($number, $data);
        }

        $message =
            ($data['title'] ?? 'Dura Cabs Offer') . "\n" .
            ($data['message'] ?? $data['description'] ?? 'Special offer from Dura Cabs') . "\n" .
            "Code: " . ($data['code'] ?? 'DURA') . "\n" .
            "Book Now: " . ($data['link'] ?? 'https://www.duracabs.com');

        return self::send($number, $message);
    }

    public static function reminder(string $number, array $data): bool
    {
        $message =
            ($data['title'] ?? 'Dura Cabs Reminder') . "\n" .
            ($data['message'] ?? 'This is a reminder from Dura Cabs.') . "\n" .
            "Booking ID: " . ($data['booking_id'] ?? 'N/A');

        return self::send($number, $message);
    }

    public static function reviewRequest(string $number, array $data): bool
    {
        $message =
            "Thank you for choosing Dura Cabs.\n" .
            "Please share your feedback for booking " . ($data['booking_id'] ?? '') . ".\n" .
            "Your review helps us improve.";

        return self::send($number, $message);
    }

    public static function selfDrivePickup(string $number, array $data): bool
    {
        $message =
            "Self Drive Pickup Confirmed\n" .
            "Booking ID: " . ($data['booking_id'] ?? 'N/A') . "\n" .
            "Vehicle: " . ($data['vehicle_name'] ?? 'N/A') . "\n" .
            "Pickup KM: " . ($data['pickup_km'] ?? 'N/A') . "\n" .
            "Fuel: " . ($data['fuel_level'] ?? 'N/A');

        return self::send($number, $message);
    }

    public static function selfDriveDrop(string $number, array $data): bool
    {
        $message =
            "Self Drive Drop Recorded\n" .
            "Booking ID: " . ($data['booking_id'] ?? 'N/A') . "\n" .
            "Vehicle: " . ($data['vehicle_name'] ?? 'N/A') . "\n" .
            "Drop KM: " . ($data['drop_km'] ?? 'N/A') . "\n" .
            "Total KM: " . ($data['total_km'] ?? 'N/A');

        return self::send($number, $message);
    }

    private static function cleanNumber(string $number): string
    {
        $number = preg_replace('/\D+/', '', $number);

        if (strlen($number) === 10) {
            return '91' . $number;
        }

        if (strlen($number) > 10 && str_starts_with($number, '91')) {
            return $number;
        }

        return $number;
    }
}