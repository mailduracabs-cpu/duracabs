<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WhatsAppController extends BaseApiController
{
    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|string',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422, $validator->errors());
        }

        $sent = WhatsAppService::send($request->mobile, $request->message);

        return $this->success([
            'sent' => $sent,
            'mobile' => $request->mobile,
        ], $sent ? 'WhatsApp message sent successfully' : 'WhatsApp message request failed');
    }

    public function bookingConfirmation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|string',
            'customer_name' => 'nullable|string',
            'pickup' => 'required|string',
            'drop' => 'required|string',
            'date' => 'required|string',
            'time' => 'required|string',
            'car_type' => 'nullable|string',
            'amount' => 'nullable',
            'booking_id' => 'nullable',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422, $validator->errors());
        }

        $message =
            "Dear " . ($request->customer_name ?? 'Customer') . ",\n\n" .
            "Your Dura Cabs booking is confirmed.\n" .
            "Booking ID: " . ($request->booking_id ?? 'N/A') . "\n" .
            "Pickup: {$request->pickup}\n" .
            "Drop: {$request->drop}\n" .
            "Date: {$request->date}\n" .
            "Time: {$request->time}\n" .
            "Car: " . ($request->car_type ?? 'Cab') . "\n" .
            "Amount: ₹" . ($request->amount ?? 'As discussed') . "\n\n" .
            "Thank you for choosing Dura Cabs.";

        $sent = WhatsAppService::bookingConfirmation($request->mobile, $message);

        return $this->success([
            'sent' => $sent,
            'mobile' => $request->mobile,
            'message' => $message,
        ], $sent ? 'Booking confirmation sent successfully' : 'Booking confirmation sending failed');
    }

    public function driverDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|string',
            'driver_name' => 'required|string',
            'driver_mobile' => 'required|string',
            'car_number' => 'nullable|string',
            'car_name' => 'nullable|string',
            'booking_id' => 'nullable',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422, $validator->errors());
        }

        $message =
            "Dura Cabs Driver Details\n\n" .
            "Booking ID: " . ($request->booking_id ?? 'N/A') . "\n" .
            "Driver: {$request->driver_name}\n" .
            "Mobile: {$request->driver_mobile}\n" .
            "Car: " . ($request->car_name ?? 'Cab') . "\n" .
            "Car No: " . ($request->car_number ?? 'N/A') . "\n\n" .
            "Have a safe journey.";

        $sent = WhatsAppService::driverDetails($request->mobile, $message);

        return $this->success([
            'sent' => $sent,
            'mobile' => $request->mobile,
            'message' => $message,
        ], $sent ? 'Driver details sent successfully' : 'Driver details sending failed');
    }

    public function paymentReminder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|string',
            'amount' => 'required',
            'payment_link' => 'nullable|string',
            'booking_id' => 'nullable',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422, $validator->errors());
        }

        $message =
            "Dura Cabs Payment Reminder\n\n" .
            "Booking ID: " . ($request->booking_id ?? 'N/A') . "\n" .
            "Amount Due: ₹{$request->amount}\n" .
            "Payment Link: " . ($request->payment_link ?? 'Please contact support') . "\n\n" .
            "Thank you.";

        $sent = WhatsAppService::paymentReminder($request->mobile, $message);

        return $this->success([
            'sent' => $sent,
            'mobile' => $request->mobile,
            'message' => $message,
        ], $sent ? 'Payment reminder sent successfully' : 'Payment reminder sending failed');
    }

    public function offerMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|string',
            'title' => 'required|string',
            'offer' => 'required|string',
            'link' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422, $validator->errors());
        }

        $message =
            "{$request->title}\n\n" .
            "{$request->offer}\n\n" .
            "Book now: " . ($request->link ?? 'https://www.duracabs.com') . "\n\n" .
            "Dura Cabs";

        $sent = WhatsAppService::offer($request->mobile, $message);

        return $this->success([
            'sent' => $sent,
            'mobile' => $request->mobile,
            'message' => $message,
        ], $sent ? 'Offer message sent successfully' : 'Offer message sending failed');
    }

    public function templateMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|string',
            'template_name' => 'required|string',
            'parameters' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422, $validator->errors());
        }

        $message = $request->template_name;

        if (is_array($request->parameters) && count($request->parameters) > 0) {
            $message .= "\n\n" . implode("\n", $request->parameters);
        }

        $sent = WhatsAppService::send($request->mobile, $message);

        return $this->success([
            'sent' => $sent,
            'mobile' => $request->mobile,
            'template_name' => $request->template_name,
            'parameters' => $request->parameters ?? [],
        ], $sent ? 'Template message sent successfully' : 'Template message sending failed');
    }

    public function webhook(Request $request)
    {
        Log::info('WhatsApp Webhook Received', [
            'method' => $request->method(),
            'payload' => $request->all(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'WhatsApp webhook received',
        ]);
    }
}