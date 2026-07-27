<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    public static function send(
        string $email,
        string $subject,
        string $body,
        bool $isHtml = false
    ): bool {
        try {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return false;
            }

            if ($isHtml) {
                Mail::html($body, function ($message) use ($email, $subject) {
                    $message->to($email)->subject($subject);
                });
            } else {
                Mail::raw($body, function ($message) use ($email, $subject) {
                    $message->to($email)->subject($subject);
                });
            }

            Log::info('Dura Cabs Email Sent', [
                'email' => $email,
                'subject' => $subject,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Dura Cabs Email Failed', [
                'email' => $email,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public static function sendOtp(string $email, string $otp): bool
    {
        $subject = 'Your Dura Cabs OTP';

        $body =
            "Dear Customer,\n\n" .
            "Your Dura Cabs OTP is: {$otp}\n\n" .
            "This OTP is valid for 5 minutes.\n" .
            "Please do not share this OTP with anyone.\n\n" .
            "Regards,\nDura Cabs";

        return self::send($email, $subject, $body);
    }

    public static function bookingConfirmation(string $email, array $data): bool
    {
        $subject = 'Dura Cabs Booking Confirmation';

        $body =
            "Dear " . ($data['customer_name'] ?? 'Customer') . ",\n\n" .
            "Your Dura Cabs booking is confirmed.\n\n" .
            "Booking ID: " . ($data['booking_id'] ?? 'N/A') . "\n" .
            "Service: " . ($data['service_type'] ?? 'Taxi') . "\n" .
            "Pickup: " . ($data['pickup'] ?? $data['pickup_city'] ?? 'N/A') . "\n" .
            "Drop: " . ($data['drop'] ?? $data['drop_city'] ?? 'N/A') . "\n" .
            "Date: " . ($data['date'] ?? $data['pickup_date'] ?? 'N/A') . "\n" .
            "Time: " . ($data['time'] ?? $data['pickup_time'] ?? 'N/A') . "\n" .
            "Vehicle: " . ($data['car_type'] ?? $data['vehicle_type'] ?? 'Cab') . "\n" .
            "Amount: ₹" . ($data['amount'] ?? $data['grand_total'] ?? 'As discussed') . "\n\n" .
            "Thank you for choosing Dura Cabs.\n\n" .
            "Regards,\nDura Cabs";

        return self::send($email, $subject, $body);
    }

    public static function bookingCancellation(string $email, array $data): bool
    {
        $subject = 'Dura Cabs Booking Cancelled';

        $body =
            "Dear " . ($data['customer_name'] ?? 'Customer') . ",\n\n" .
            "Your Dura Cabs booking has been cancelled.\n\n" .
            "Booking ID: " . ($data['booking_id'] ?? 'N/A') . "\n" .
            "Reason: " . ($data['reason'] ?? 'As requested') . "\n\n" .
            "Regards,\nDura Cabs";

        return self::send($email, $subject, $body);
    }

    public static function driverDetails(string $email, array $data): bool
    {
        $subject = 'Dura Cabs Driver Details';

        $body =
            "Dear " . ($data['customer_name'] ?? 'Customer') . ",\n\n" .
            "Your driver details are below:\n\n" .
            "Booking ID: " . ($data['booking_id'] ?? 'N/A') . "\n" .
            "Driver Name: " . ($data['driver_name'] ?? 'N/A') . "\n" .
            "Driver Mobile: " . ($data['driver_mobile'] ?? 'N/A') . "\n" .
            "Vehicle: " . ($data['vehicle_name'] ?? $data['car_name'] ?? 'N/A') . "\n" .
            "Vehicle Number: " . ($data['vehicle_number'] ?? 'N/A') . "\n\n" .
            "Regards,\nDura Cabs";

        return self::send($email, $subject, $body);
    }

    public static function carDetails(string $email, array $data): bool
    {
        $subject = 'Dura Cabs Vehicle Details';

        $body =
            "Dear " . ($data['customer_name'] ?? 'Customer') . ",\n\n" .
            "Your vehicle details are below:\n\n" .
            "Booking ID: " . ($data['booking_id'] ?? 'N/A') . "\n" .
            "Vehicle: " . ($data['vehicle_name'] ?? $data['car_name'] ?? 'N/A') . "\n" .
            "Vehicle Number: " . ($data['vehicle_number'] ?? 'N/A') . "\n" .
            "Fuel Type: " . ($data['fuel_type'] ?? 'N/A') . "\n" .
            "Pickup Location: " . ($data['pickup_location'] ?? $data['pickup'] ?? 'N/A') . "\n\n" .
            "Regards,\nDura Cabs";

        return self::send($email, $subject, $body);
    }

    public static function paymentReceipt(string $email, array $data): bool
    {
        $subject = 'Dura Cabs Payment Receipt';

        $body =
            "Dear " . ($data['customer_name'] ?? 'Customer') . ",\n\n" .
            "Payment received successfully.\n\n" .
            "Booking ID: " . ($data['booking_id'] ?? 'N/A') . "\n" .
            "Payment ID: " . ($data['payment_id'] ?? 'N/A') . "\n" .
            "Amount Paid: ₹" . ($data['amount'] ?? '0') . "\n\n" .
            "Regards,\nDura Cabs";

        return self::send($email, $subject, $body);
    }

    public static function invoice(string $email, array $data): bool
    {
        $subject = 'Dura Cabs Invoice';

        $body =
            "Dear " . ($data['customer_name'] ?? 'Customer') . ",\n\n" .
            "Invoice Details:\n\n" .
            "Booking ID: " . ($data['booking_id'] ?? 'N/A') . "\n" .
            "Amount: ₹" . ($data['amount'] ?? $data['grand_total'] ?? '0') . "\n\n" .
            "Regards,\nDura Cabs";

        return self::send($email, $subject, $body);
    }

    public static function refund(string $email, array $data): bool
    {
        $subject = 'Dura Cabs Refund Update';

        $body =
            "Dear " . ($data['customer_name'] ?? 'Customer') . ",\n\n" .
            "Your refund has been processed/updated.\n\n" .
            "Booking ID: " . ($data['booking_id'] ?? 'N/A') . "\n" .
            "Refund Amount: ₹" . ($data['refund_amount'] ?? $data['amount'] ?? '0') . "\n" .
            "Status: " . ($data['status'] ?? 'Processing') . "\n\n" .
            "Regards,\nDura Cabs";

        return self::send($email, $subject, $body);
    }

    public static function reminder(string $email, array $data): bool
    {
        $subject = $data['subject'] ?? 'Dura Cabs Reminder';

        $body =
            "Dear " . ($data['customer_name'] ?? 'Customer') . ",\n\n" .
            ($data['message'] ?? 'This is a reminder from Dura Cabs.') . "\n\n" .
            "Booking ID: " . ($data['booking_id'] ?? 'N/A') . "\n\n" .
            "Regards,\nDura Cabs";

        return self::send($email, $subject, $body);
    }

    public static function offerNewsletter(string $email, array $data): bool
    {
        $subject = $data['title'] ?? 'Dura Cabs Offer';

        $body =
            ($data['message'] ?? $data['description'] ?? 'Special offer from Dura Cabs') . "\n\n" .
            "Offer Code: " . ($data['code'] ?? 'DURA') . "\n" .
            "Book Now: " . ($data['link'] ?? 'https://www.duracabs.com') . "\n\n" .
            "Regards,\nDura Cabs";

        return self::send($email, $subject, $body);
    }

    public static function reviewRequest(string $email, array $data): bool
    {
        $subject = 'How was your Dura Cabs experience?';

        $body =
            "Dear " . ($data['customer_name'] ?? 'Customer') . ",\n\n" .
            "Thank you for choosing Dura Cabs.\n" .
            "Please share your feedback for booking " . ($data['booking_id'] ?? '') . ".\n\n" .
            "Regards,\nDura Cabs";

        return self::send($email, $subject, $body);
    }

    public static function selfDrivePickup(string $email, array $data): bool
    {
        $subject = 'Self Drive Pickup Confirmation';

        $body =
            "Dear " . ($data['customer_name'] ?? 'Customer') . ",\n\n" .
            "Your self drive vehicle pickup is confirmed.\n\n" .
            "Booking ID: " . ($data['booking_id'] ?? 'N/A') . "\n" .
            "Vehicle: " . ($data['vehicle_name'] ?? 'N/A') . "\n" .
            "Pickup KM: " . ($data['pickup_km'] ?? 'N/A') . "\n" .
            "Fuel Level: " . ($data['fuel_level'] ?? 'N/A') . "\n\n" .
            "Regards,\nDura Cabs";

        return self::send($email, $subject, $body);
    }

    public static function selfDriveDrop(string $email, array $data): bool
    {
        $subject = 'Self Drive Drop Confirmation';

        $body =
            "Dear " . ($data['customer_name'] ?? 'Customer') . ",\n\n" .
            "Your self drive vehicle drop has been recorded.\n\n" .
            "Booking ID: " . ($data['booking_id'] ?? 'N/A') . "\n" .
            "Vehicle: " . ($data['vehicle_name'] ?? 'N/A') . "\n" .
            "Drop KM: " . ($data['drop_km'] ?? 'N/A') . "\n" .
            "Total KM: " . ($data['total_km'] ?? 'N/A') . "\n\n" .
            "Regards,\nDura Cabs";

        return self::send($email, $subject, $body);
    }
}