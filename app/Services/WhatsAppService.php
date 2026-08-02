<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsAppService
{
    /*
    |--------------------------------------------------------------------------
    | Basic text message
    |--------------------------------------------------------------------------
    */

    public static function send(string $number, string $message): bool
    {
        $result = self::sendMessage($number, $message);

        return (bool) ($result['status'] ?? false);
    }

    public static function sendMessage(string $number, string $message): array
    {
        $number = self::cleanNumber($number);
        $message = trim($message);

        if ($number === '') {
            return self::failed(
                message: 'WhatsApp mobile number is required.',
                error: 'missing_mobile'
            );
        }

        if ($message === '') {
            return self::failed(
                message: 'WhatsApp message is required.',
                error: 'missing_message'
            );
        }

        return self::sendPayload(
            payload: [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $number,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $message,
                ],
            ],
            number: $number,
            context: 'text'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Meta template message
    |--------------------------------------------------------------------------
    */

    public static function sendTemplate(
        string $number,
        string $templateName,
        string $languageCode = 'en',
        array $bodyParameters = [],
        array $headerParameters = [],
        array $buttonParameters = []
    ): array {
        $number = self::cleanNumber($number);
        $templateName = trim($templateName);
        $languageCode = trim($languageCode) ?: 'en';

        if ($number === '') {
            return self::failed(
                message: 'WhatsApp mobile number is required.',
                error: 'missing_mobile'
            );
        }

        if ($templateName === '') {
            return self::failed(
                message: 'WhatsApp template name is required.',
                error: 'missing_template_name'
            );
        }

        $components = [];

        if ($headerParameters !== []) {
            $components[] = [
                'type' => 'header',
                'parameters' => self::prepareParameters($headerParameters),
            ];
        }

        if ($bodyParameters !== []) {
            $components[] = [
                'type' => 'body',
                'parameters' => self::prepareParameters($bodyParameters),
            ];
        }

        foreach ($buttonParameters as $button) {
            if (!is_array($button)) {
                continue;
            }

            $value = $button['value'] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            $components[] = [
                'type' => 'button',
                'sub_type' => (string) ($button['sub_type'] ?? 'url'),
                'index' => (string) ($button['index'] ?? '0'),
                'parameters' => self::prepareParameters([$value]),
            ];
        }

        $template = [
            'name' => $templateName,
            'language' => [
                'code' => $languageCode,
            ],
        ];

        if ($components !== []) {
            $template['components'] = $components;
        }

        return self::sendPayload(
            payload: [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $number,
                'type' => 'template',
                'template' => $template,
            ],
            number: $number,
            context: 'template',
            logData: [
                'template_name' => $templateName,
                'language_code' => $languageCode,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | OTP
    |--------------------------------------------------------------------------
    */

    public static function sendOtp(string $number, string $otp): bool
    {
        $templateName = trim((string) config(
            'services.whatsapp.templates.otp',
            ''
        ));

        if ($templateName !== '') {
            $result = self::sendTemplate(
                number: $number,
                templateName: $templateName,
                languageCode: self::defaultLanguage(),
                bodyParameters: [$otp],
                buttonParameters: self::otpButtonParameters($otp)
            );

            return (bool) ($result['status'] ?? false);
        }

        $message =
            "Your Dura Cabs OTP is {$otp}. " .
            "Valid for 5 minutes. Do not share this OTP with anyone.";

        return self::send($number, $message);
    }

    /*
    |--------------------------------------------------------------------------
    | Booking received
    |--------------------------------------------------------------------------
    */

    public static function bookingReceived(
        string $number,
        array $data
    ): bool {
        $customerName = trim((string) ($data['customer_name'] ?? 'Customer')) ?: 'Customer';
        $bookingId = trim((string) ($data['booking_id'] ?? 'N/A')) ?: 'N/A';
        $service = trim((string) ($data['service'] ?? 'Cab Booking')) ?: 'Cab Booking';
        $route = trim((string) ($data['route'] ?? 'N/A')) ?: 'N/A';
        $travelDate = trim((string) (
            $data['travel_date']
            ?? $data['date']
            ?? $data['pickup_date']
            ?? 'N/A'
        )) ?: 'N/A';

        $totalAmount = $data['total_amount']
            ?? $data['amount']
            ?? $data['grand_total']
            ?? '0';

        $formattedAmount = is_numeric($totalAmount)
            ? number_format((float) $totalAmount, 2, '.', '')
            : trim((string) $totalAmount);

        if ($formattedAmount === '') {
            $formattedAmount = '0.00';
        }

        $message =
            "Hello {$customerName},\n\n" .
            "Your Dura Cabs booking request has been received successfully.\n\n" .
            "Booking ID: {$bookingId}\n" .
            "Service: {$service}\n" .
            "Route: {$route}\n" .
            "Travel Date: {$travelDate}\n" .
            "Total Amount: INR {$formattedAmount}\n\n" .
            "Our team will review your booking and update you shortly.\n\n" .
            "For assistance, call +91 70888 73331.\n\n" .
            "Dura Cabs";

        return self::sendConfiguredTemplateOrText(
            number: $number,
            templateConfigKey: 'booking_received',
            fallbackMessage: $message,
            parameters: [
                $customerName,
                $bookingId,
                $service,
                $route,
                $travelDate,
                $formattedAmount,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Booking confirmation
    |--------------------------------------------------------------------------
    */

    public static function bookingConfirmation(
        string $number,
        mixed $data
    ): bool {
        if (is_string($data)) {
            return self::sendConfiguredTemplateOrText(
                number: $number,
                templateConfigKey: 'booking_confirmation',
                fallbackMessage: $data,
                parameters: [$data]
            );
        }

        $data = is_array($data) ? $data : [];

        $customerName = $data['customer_name'] ?? 'Customer';
        $bookingId = $data['booking_id'] ?? 'N/A';
        $pickup = $data['pickup'] ?? $data['pickup_city'] ?? 'N/A';
        $drop = $data['drop'] ?? $data['drop_city'] ?? 'N/A';
        $date = $data['date'] ?? $data['pickup_date'] ?? 'N/A';
        $time = $data['time'] ?? $data['pickup_time'] ?? 'N/A';
        $amount = $data['amount'] ?? $data['grand_total'] ?? 'As discussed';

        $message =
            "Dear {$customerName},\n" .
            "Your Dura Cabs booking is confirmed.\n" .
            "Booking ID: {$bookingId}\n" .
            "Pickup: {$pickup}\n" .
            "Drop: {$drop}\n" .
            "Date: {$date}\n" .
            "Time: {$time}\n" .
            "Amount: Rs. {$amount}\n" .
            "Thank you for choosing Dura Cabs.";

        return self::sendConfiguredTemplateOrText(
            number: $number,
            templateConfigKey: 'booking_confirmation',
            fallbackMessage: $message,
            parameters: [
                $customerName,
                $bookingId,
                $pickup,
                $drop,
                $date,
                $time,
                (string) $amount,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Booking cancellation
    |--------------------------------------------------------------------------
    */

    public static function bookingCancellation(
        string $number,
        array $data
    ): bool {
        $customerName = $data['customer_name'] ?? 'Customer';
        $bookingId = $data['booking_id'] ?? 'N/A';
        $reason = $data['reason'] ?? 'As requested';

        $message =
            "Dear {$customerName},\n" .
            "Your Dura Cabs booking has been cancelled.\n" .
            "Booking ID: {$bookingId}\n" .
            "Reason: {$reason}\n" .
            "Regards, Dura Cabs";

        return self::sendConfiguredTemplateOrText(
            number: $number,
            templateConfigKey: 'booking_cancellation',
            fallbackMessage: $message,
            parameters: [
                $customerName,
                $bookingId,
                $reason,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Driver details
    |--------------------------------------------------------------------------
    */

    public static function driverDetails(
        string $number,
        mixed $data
    ): bool {
        if (is_string($data)) {
            return self::sendConfiguredTemplateOrText(
                number: $number,
                templateConfigKey: 'driver_details',
                fallbackMessage: $data,
                parameters: [$data]
            );
        }

        $data = is_array($data) ? $data : [];

        $bookingId = $data['booking_id'] ?? 'N/A';
        $driverName = $data['driver_name'] ?? 'N/A';
        $driverMobile = $data['driver_mobile'] ?? 'N/A';
        $vehicleName =
            $data['vehicle_name']
            ?? $data['car_name']
            ?? 'N/A';

        $vehicleNumber =
            $data['vehicle_number']
            ?? $data['car_number']
            ?? 'N/A';

        $message =
            "Dura Cabs Driver Details\n" .
            "Booking ID: {$bookingId}\n" .
            "Driver: {$driverName}\n" .
            "Mobile: {$driverMobile}\n" .
            "Vehicle: {$vehicleName}\n" .
            "Vehicle No: {$vehicleNumber}";

        return self::sendConfiguredTemplateOrText(
            number: $number,
            templateConfigKey: 'driver_details',
            fallbackMessage: $message,
            parameters: [
                $bookingId,
                $driverName,
                $driverMobile,
                $vehicleName,
                $vehicleNumber,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Car details
    |--------------------------------------------------------------------------
    */

    public static function carDetails(
        string $number,
        array $data
    ): bool {
        $bookingId = $data['booking_id'] ?? 'N/A';
        $vehicleName =
            $data['vehicle_name']
            ?? $data['car_name']
            ?? 'N/A';

        $vehicleNumber =
            $data['vehicle_number']
            ?? $data['car_number']
            ?? 'N/A';

        $fuelType = $data['fuel_type'] ?? 'N/A';

        $pickupLocation =
            $data['pickup_location']
            ?? $data['pickup']
            ?? 'N/A';

        $message =
            "Dura Cabs Vehicle Details\n" .
            "Booking ID: {$bookingId}\n" .
            "Vehicle: {$vehicleName}\n" .
            "Vehicle No: {$vehicleNumber}\n" .
            "Fuel: {$fuelType}\n" .
            "Pickup: {$pickupLocation}";

        return self::sendConfiguredTemplateOrText(
            number: $number,
            templateConfigKey: 'car_details',
            fallbackMessage: $message,
            parameters: [
                $bookingId,
                $vehicleName,
                $vehicleNumber,
                $fuelType,
                $pickupLocation,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Payment reminder
    |--------------------------------------------------------------------------
    */

    public static function paymentReminder(
        string $number,
        mixed $data
    ): bool {
        if (is_string($data)) {
            return self::sendConfiguredTemplateOrText(
                number: $number,
                templateConfigKey: 'payment_reminder',
                fallbackMessage: $data,
                parameters: [$data]
            );
        }

        $data = is_array($data) ? $data : [];

        $bookingId = $data['booking_id'] ?? 'N/A';
        $amount =
            $data['amount']
            ?? $data['pending_amount']
            ?? '0';

        $paymentLink =
            $data['payment_link']
            ?? config('app.frontend_url', 'https://www.duracabs.com');

        $message =
            "Dura Cabs Payment Reminder\n" .
            "Booking ID: {$bookingId}\n" .
            "Pending Amount: Rs. {$amount}\n" .
            "Payment Link: {$paymentLink}\n" .
            "Please complete your payment.";

        return self::sendConfiguredTemplateOrText(
            number: $number,
            templateConfigKey: 'payment_reminder',
            fallbackMessage: $message,
            parameters: [
                $bookingId,
                (string) $amount,
                $paymentLink,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Payment receipt
    |--------------------------------------------------------------------------
    */

    public static function paymentReceipt(
        string $number,
        array $data
    ): bool {
        $bookingId = $data['booking_id'] ?? 'N/A';
        $paymentId = $data['payment_id'] ?? 'N/A';
        $amount = $data['amount'] ?? '0';

        $message =
            "Dura Cabs Payment Received\n" .
            "Booking ID: {$bookingId}\n" .
            "Payment ID: {$paymentId}\n" .
            "Amount: Rs. {$amount}\n" .
            "Thank you.";

        return self::sendConfiguredTemplateOrText(
            number: $number,
            templateConfigKey: 'payment_receipt',
            fallbackMessage: $message,
            parameters: [
                $bookingId,
                $paymentId,
                (string) $amount,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Invoice
    |--------------------------------------------------------------------------
    */

    public static function invoice(
        string $number,
        array $data
    ): bool {
        $bookingId = $data['booking_id'] ?? 'N/A';
        $amount =
            $data['amount']
            ?? $data['grand_total']
            ?? '0';

        $invoiceUrl = $data['invoice_url'] ?? null;

        $message =
            "Dura Cabs Invoice\n" .
            "Booking ID: {$bookingId}\n" .
            "Amount: Rs. {$amount}\n" .
            "Invoice generated successfully.";

        if (filled($invoiceUrl)) {
            $result = self::sendDocument(
                number: $number,
                documentUrl: (string) $invoiceUrl,
                filename: (string) ($data['filename'] ?? "invoice-{$bookingId}.pdf"),
                caption: $message
            );

            return (bool) ($result['status'] ?? false);
        }

        return self::sendConfiguredTemplateOrText(
            number: $number,
            templateConfigKey: 'invoice',
            fallbackMessage: $message,
            parameters: [
                $bookingId,
                (string) $amount,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Refund
    |--------------------------------------------------------------------------
    */

    public static function refund(
        string $number,
        array $data
    ): bool {
        $bookingId = $data['booking_id'] ?? 'N/A';

        $refundAmount =
            $data['refund_amount']
            ?? $data['amount']
            ?? '0';

        $status = $data['status'] ?? 'Processing';

        $message =
            "Dura Cabs Refund Update\n" .
            "Booking ID: {$bookingId}\n" .
            "Refund Amount: Rs. {$refundAmount}\n" .
            "Status: {$status}";

        return self::sendConfiguredTemplateOrText(
            number: $number,
            templateConfigKey: 'refund',
            fallbackMessage: $message,
            parameters: [
                $bookingId,
                (string) $refundAmount,
                $status,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Offer
    |--------------------------------------------------------------------------
    */

    public static function offer(
        string $number,
        mixed $data
    ): bool {
        if (is_string($data)) {
            return self::sendConfiguredTemplateOrText(
                number: $number,
                templateConfigKey: 'offer',
                fallbackMessage: $data,
                parameters: [$data]
            );
        }

        $data = is_array($data) ? $data : [];

        $title = $data['title'] ?? 'Dura Cabs Offer';

        $description =
            $data['message']
            ?? $data['description']
            ?? 'Special offer from Dura Cabs';

        $code = $data['code'] ?? 'DURA';

        $link =
            $data['link']
            ?? config('app.frontend_url', 'https://www.duracabs.com');

        $message =
            "{$title}\n" .
            "{$description}\n" .
            "Code: {$code}\n" .
            "Book Now: {$link}";

        return self::sendConfiguredTemplateOrText(
            number: $number,
            templateConfigKey: 'offer',
            fallbackMessage: $message,
            parameters: [
                $title,
                $description,
                $code,
                $link,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Reminder
    |--------------------------------------------------------------------------
    */

    public static function reminder(
        string $number,
        array $data
    ): bool {
        $title = $data['title'] ?? 'Dura Cabs Reminder';

        $description =
            $data['message']
            ?? 'This is a reminder from Dura Cabs.';

        $bookingId = $data['booking_id'] ?? 'N/A';

        $message =
            "{$title}\n" .
            "{$description}\n" .
            "Booking ID: {$bookingId}";

        return self::sendConfiguredTemplateOrText(
            number: $number,
            templateConfigKey: 'reminder',
            fallbackMessage: $message,
            parameters: [
                $title,
                $description,
                $bookingId,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Review request
    |--------------------------------------------------------------------------
    */

    public static function reviewRequest(
        string $number,
        array $data
    ): bool {
        $bookingId = $data['booking_id'] ?? 'N/A';

        $reviewLink =
            $data['review_link']
            ?? config('app.frontend_url', 'https://www.duracabs.com');

        $message =
            "Thank you for choosing Dura Cabs.\n" .
            "Please share your feedback for booking {$bookingId}.\n" .
            "Review Link: {$reviewLink}\n" .
            "Your review helps us improve.";

        return self::sendConfiguredTemplateOrText(
            number: $number,
            templateConfigKey: 'review_request',
            fallbackMessage: $message,
            parameters: [
                $bookingId,
                $reviewLink,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Self-drive pickup
    |--------------------------------------------------------------------------
    */

    public static function selfDrivePickup(
        string $number,
        array $data
    ): bool {
        $bookingId = $data['booking_id'] ?? 'N/A';
        $vehicleName = $data['vehicle_name'] ?? 'N/A';
        $pickupKm = $data['pickup_km'] ?? 'N/A';
        $fuelLevel = $data['fuel_level'] ?? 'N/A';

        $message =
            "Self Drive Pickup Confirmed\n" .
            "Booking ID: {$bookingId}\n" .
            "Vehicle: {$vehicleName}\n" .
            "Pickup KM: {$pickupKm}\n" .
            "Fuel: {$fuelLevel}";

        return self::sendConfiguredTemplateOrText(
            number: $number,
            templateConfigKey: 'self_drive_pickup',
            fallbackMessage: $message,
            parameters: [
                $bookingId,
                $vehicleName,
                (string) $pickupKm,
                (string) $fuelLevel,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Self-drive drop
    |--------------------------------------------------------------------------
    */

    public static function selfDriveDrop(
        string $number,
        array $data
    ): bool {
        $bookingId = $data['booking_id'] ?? 'N/A';
        $vehicleName = $data['vehicle_name'] ?? 'N/A';
        $dropKm = $data['drop_km'] ?? 'N/A';
        $totalKm = $data['total_km'] ?? 'N/A';

        $message =
            "Self Drive Drop Recorded\n" .
            "Booking ID: {$bookingId}\n" .
            "Vehicle: {$vehicleName}\n" .
            "Drop KM: {$dropKm}\n" .
            "Total KM: {$totalKm}";

        return self::sendConfiguredTemplateOrText(
            number: $number,
            templateConfigKey: 'self_drive_drop',
            fallbackMessage: $message,
            parameters: [
                $bookingId,
                $vehicleName,
                (string) $dropKm,
                (string) $totalKm,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Media messages
    |--------------------------------------------------------------------------
    */

    public static function sendImage(
        string $number,
        string $imageUrl,
        ?string $caption = null
    ): array {
        $number = self::cleanNumber($number);
        $imageUrl = trim($imageUrl);

        if ($number === '' || $imageUrl === '') {
            return self::failed(
                message: 'Mobile number and image URL are required.',
                error: 'missing_image_data'
            );
        }

        $image = [
            'link' => $imageUrl,
        ];

        if (filled($caption)) {
            $image['caption'] = trim((string) $caption);
        }

        return self::sendPayload(
            payload: [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $number,
                'type' => 'image',
                'image' => $image,
            ],
            number: $number,
            context: 'image'
        );
    }

    public static function sendDocument(
        string $number,
        string $documentUrl,
        ?string $filename = null,
        ?string $caption = null
    ): array {
        $number = self::cleanNumber($number);
        $documentUrl = trim($documentUrl);

        if ($number === '' || $documentUrl === '') {
            return self::failed(
                message: 'Mobile number and document URL are required.',
                error: 'missing_document_data'
            );
        }

        $document = [
            'link' => $documentUrl,
        ];

        if (filled($filename)) {
            $document['filename'] = trim((string) $filename);
        }

        if (filled($caption)) {
            $document['caption'] = trim((string) $caption);
        }

        return self::sendPayload(
            payload: [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $number,
                'type' => 'document',
                'document' => $document,
            ],
            number: $number,
            context: 'document'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Mark message as read
    |--------------------------------------------------------------------------
    */

    public static function markAsRead(string $messageId): array
    {
        $messageId = trim($messageId);

        if ($messageId === '') {
            return self::failed(
                message: 'WhatsApp message ID is required.',
                error: 'missing_message_id'
            );
        }

        return self::sendPayload(
            payload: [
                'messaging_product' => 'whatsapp',
                'status' => 'read',
                'message_id' => $messageId,
            ],
            number: '',
            context: 'mark_as_read'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Test Meta connection
    |--------------------------------------------------------------------------
    */

    public static function testConnection(): array
    {
        if (!self::credentialsConfigured()) {
            return self::failed(
                message: 'Meta WhatsApp credentials are missing.',
                error: 'credentials_missing'
            );
        }

        try {
            $response = Http::acceptJson()
                ->withToken(self::accessToken())
                ->timeout(self::timeout())
                ->get(self::phoneNumberUrl(), [
                    'fields' => implode(',', [
                        'id',
                        'display_phone_number',
                        'verified_name',
                        'quality_rating',
                    ]),
                ]);

            $body = self::responseBody($response);

            if ($response->successful()) {
                return self::successful(
                    message: 'Meta WhatsApp connection successful.',
                    statusCode: $response->status(),
                    response: $body
                );
            }

            return self::failed(
                message: self::extractErrorMessage(
                    $body,
                    'Meta WhatsApp connection failed.'
                ),
                error: (string) (
                    data_get($body, 'error.code')
                    ?? 'connection_failed'
                ),
                statusCode: $response->status(),
                response: $body
            );
        } catch (Throwable $exception) {
            Log::error('Meta WhatsApp connection test failed.', [
                'error' => $exception->getMessage(),
            ]);

            return self::failed(
                message: 'Meta WhatsApp connection test failed.',
                error: $exception->getMessage()
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Internal API request
    |--------------------------------------------------------------------------
    */

    private static function sendPayload(
        array $payload,
        string $number,
        string $context,
        array $logData = []
    ): array {
        if (!self::credentialsConfigured()) {
            Log::warning('Meta WhatsApp credentials missing.', [
                'context' => $context,
                'number' => self::maskNumber($number),
                'phone_number_id_configured' => filled(self::phoneNumberId()),
                'access_token_configured' => filled(self::accessToken()),
            ]);

            return self::failed(
                message: 'Meta WhatsApp credentials are missing.',
                error: 'credentials_missing'
            );
        }

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withToken(self::accessToken())
                ->timeout(self::timeout())
                ->retry(
                    self::retryTimes(),
                    self::retryDelay(),
                    throw: false
                )
                ->post(self::messagesUrl(), $payload);

            $body = self::responseBody($response);

            if ($response->successful()) {
                $messageId = data_get($body, 'messages.0.id');

                Log::info(
                    'Meta WhatsApp message accepted.',
                    array_merge([
                        'context' => $context,
                        'number' => self::maskNumber($number),
                        'message_id' => $messageId,
                        'http_code' => $response->status(),
                    ], $logData)
                );

                return self::successful(
                    message: 'WhatsApp message accepted by Meta.',
                    messageId: $messageId,
                    statusCode: $response->status(),
                    response: $body
                );
            }

            Log::error(
                'Meta WhatsApp request failed.',
                array_merge([
                    'context' => $context,
                    'number' => self::maskNumber($number),
                    'http_code' => $response->status(),
                    'meta_error' => data_get($body, 'error'),
                ], $logData)
            );

            return self::failed(
                message: self::extractErrorMessage(
                    $body,
                    'Meta WhatsApp rejected the request.'
                ),
                error: (string) (
                    data_get($body, 'error.code')
                    ?? 'meta_request_failed'
                ),
                statusCode: $response->status(),
                response: $body
            );
        } catch (ConnectionException $exception) {
            Log::error('Meta WhatsApp connection failed.', [
                'context' => $context,
                'number' => self::maskNumber($number),
                'error' => $exception->getMessage(),
            ]);

            return self::failed(
                message: 'Could not connect to Meta WhatsApp API.',
                error: $exception->getMessage()
            );
        } catch (Throwable $exception) {
            Log::error('Meta WhatsApp service failed.', [
                'context' => $context,
                'number' => self::maskNumber($number),
                'error' => $exception->getMessage(),
            ]);

            return self::failed(
                message: 'WhatsApp service failed.',
                error: $exception->getMessage()
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Template fallback helper
    |--------------------------------------------------------------------------
    */

    private static function sendConfiguredTemplateOrText(
        string $number,
        string $templateConfigKey,
        string $fallbackMessage,
        array $parameters = []
    ): bool {
        $templateName = trim((string) config(
            "services.whatsapp.templates.{$templateConfigKey}",
            ''
        ));

        if ($templateName === '') {
            return self::send($number, $fallbackMessage);
        }

        $result = self::sendTemplate(
            number: $number,
            templateName: $templateName,
            languageCode: self::defaultLanguage(),
            bodyParameters: $parameters
        );

        return (bool) ($result['status'] ?? false);
    }

    private static function prepareParameters(array $parameters): array
    {
        $prepared = [];

        foreach ($parameters as $parameter) {
            if (
                is_array($parameter)
                && isset($parameter['type'])
            ) {
                $prepared[] = $parameter;
                continue;
            }

            $prepared[] = [
                'type' => 'text',
                'text' => (string) $parameter,
            ];
        }

        return $prepared;
    }

    private static function otpButtonParameters(string $otp): array
    {
        if (
            !(bool) config(
                'services.whatsapp.otp_copy_button',
                false
            )
        ) {
            return [];
        }

        return [
            [
                'sub_type' => 'url',
                'index' => '0',
                'value' => $otp,
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Configuration
    |--------------------------------------------------------------------------
    */

    private static function credentialsConfigured(): bool
    {
        return filled(self::accessToken())
            && filled(self::phoneNumberId());
    }

    private static function accessToken(): string
    {
        return trim((string) config(
            'services.whatsapp.access_token',
            ''
        ));
    }

    private static function phoneNumberId(): string
    {
        return trim((string) config(
            'services.whatsapp.phone_number_id',
            ''
        ));
    }

    private static function graphVersion(): string
    {
        $version = trim((string) config(
            'services.whatsapp.graph_version',
            'v23.0'
        ));

        if ($version === '') {
            $version = 'v23.0';
        }

        return str_starts_with($version, 'v')
            ? $version
            : "v{$version}";
    }

    private static function baseUrl(): string
    {
        return rtrim(
            (string) config(
                'services.whatsapp.base_url',
                'https://graph.facebook.com'
            ),
            '/'
        );
    }

    private static function messagesUrl(): string
    {
        return self::baseUrl()
            .'/'
            .self::graphVersion()
            .'/'
            .self::phoneNumberId()
            .'/messages';
    }

    private static function phoneNumberUrl(): string
    {
        return self::baseUrl()
            .'/'
            .self::graphVersion()
            .'/'
            .self::phoneNumberId();
    }

    private static function defaultLanguage(): string
    {
        return trim((string) config(
            'services.whatsapp.default_language',
            'en'
        )) ?: 'en';
    }

    private static function timeout(): int
    {
        return max(
            5,
            (int) config('services.whatsapp.timeout', 30)
        );
    }

    private static function retryTimes(): int
    {
        return max(
            0,
            (int) config('services.whatsapp.retry_times', 2)
        );
    }

    private static function retryDelay(): int
    {
        return max(
            100,
            (int) config('services.whatsapp.retry_delay', 500)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Mobile number
    |--------------------------------------------------------------------------
    */

    public static function cleanNumber(string $number): string
    {
        $number = preg_replace('/\D+/', '', trim($number)) ?? '';

        if ($number === '') {
            return '';
        }

        if (str_starts_with($number, '00')) {
            $number = substr($number, 2);
        }

        if (
            strlen($number) === 11
            && str_starts_with($number, '0')
        ) {
            $number = substr($number, 1);
        }

        $countryCode = preg_replace(
            '/\D+/',
            '',
            (string) config(
                'services.whatsapp.default_country_code',
                '91'
            )
        ) ?: '91';

        if (strlen($number) === 10) {
            return $countryCode.$number;
        }

        return $number;
    }

    /*
    |--------------------------------------------------------------------------
    | Response helpers
    |--------------------------------------------------------------------------
    */

    private static function successful(
        string $message,
        ?string $messageId = null,
        ?int $statusCode = null,
        ?array $response = null
    ): array {
        return [
            'status' => true,
            'success' => true,
            'channel' => 'whatsapp',
            'message' => $message,
            'message_id' => $messageId,
            'status_code' => $statusCode,
            'response' => $response,
            'error' => null,
        ];
    }

    private static function failed(
        string $message,
        ?string $error = null,
        ?int $statusCode = null,
        ?array $response = null
    ): array {
        return [
            'status' => false,
            'success' => false,
            'channel' => 'whatsapp',
            'message' => $message,
            'message_id' => null,
            'status_code' => $statusCode,
            'response' => $response,
            'error' => $error,
        ];
    }

    private static function responseBody(Response $response): array
    {
        $body = $response->json();

        if (is_array($body)) {
            return $body;
        }

        return [
            'raw' => $response->body(),
        ];
    }

    private static function extractErrorMessage(
        array $response,
        string $default
    ): string {
        return (string) (
            data_get($response, 'error.error_data.details')
            ?? data_get($response, 'error.message')
            ?? $default
        );
    }

    private static function maskNumber(string $number): string
    {
        if ($number === '') {
            return '';
        }

        if (strlen($number) <= 4) {
            return str_repeat('*', strlen($number));
        }

        return str_repeat('*', strlen($number) - 4)
            .substr($number, -4);
    }
}