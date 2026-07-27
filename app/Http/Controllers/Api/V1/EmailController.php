<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmailController extends BaseApiController
{
    public function bookingConfirmation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
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
            return $this->error(
                $validator->errors()->first(),
                422,
                $validator->errors()
            );
        }

        $sent = EmailService::bookingConfirmation(
            $request->email,
            $request->all()
        );

        return $this->success([
            'sent' => $sent,
            'email' => $request->email,
        ], $sent
            ? 'Booking confirmation email sent successfully.'
            : 'Unable to send booking confirmation email.');
    }

    public function invoice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'booking_id' => 'nullable',
            'amount' => 'required',
            'customer_name' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error(
                $validator->errors()->first(),
                422,
                $validator->errors()
            );
        }

        $sent = EmailService::invoice(
            $request->email,
            $request->all()
        );

        return $this->success([
            'sent' => $sent,
            'email' => $request->email,
        ], $sent
            ? 'Invoice email sent successfully.'
            : 'Unable to send invoice email.');
    }

    public function paymentReceipt(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'booking_id' => 'nullable',
            'payment_id' => 'nullable|string',
            'amount' => 'required',
            'customer_name' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error(
                $validator->errors()->first(),
                422,
                $validator->errors()
            );
        }

        $sent = EmailService::paymentReceipt(
            $request->email,
            $request->all()
        );

        return $this->success([
            'sent' => $sent,
            'email' => $request->email,
        ], $sent
            ? 'Payment receipt email sent successfully.'
            : 'Unable to send payment receipt email.');
    }

    public function cancellation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'booking_id' => 'nullable',
            'reason' => 'nullable|string',
            'customer_name' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error(
                $validator->errors()->first(),
                422,
                $validator->errors()
            );
        }

        $sent = EmailService::cancellation(
            $request->email,
            $request->all()
        );

        return $this->success([
            'sent' => $sent,
            'email' => $request->email,
        ], $sent
            ? 'Cancellation email sent successfully.'
            : 'Unable to send cancellation email.');
    }

    public function offerNewsletter(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'title' => 'required|string',
            'message' => 'required|string',
            'link' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error(
                $validator->errors()->first(),
                422,
                $validator->errors()
            );
        }

        $sent = EmailService::offerNewsletter(
            $request->email,
            $request->all()
        );

        return $this->success([
            'sent' => $sent,
            'email' => $request->email,
        ], $sent
            ? 'Newsletter sent successfully.'
            : 'Unable to send newsletter.');
    }
}