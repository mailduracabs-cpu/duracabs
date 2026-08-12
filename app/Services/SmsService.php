<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SmsService
{
    private const API_URL = 'http://manage.sambsms.com/app/smsapi/index.php';

    /*
    |--------------------------------------------------------------------------
    | Approved DLT Template IDs
    |--------------------------------------------------------------------------
    */

    private const TEMPLATE_BOOKING = '1507165190476873732';

    private const TEMPLATE_BOOKING_CANCELLED = '1507166123320586283';

    private const TEMPLATE_VENDOR_ASSIGNED = '1507166123273276571';

    private const TEMPLATE_DRIVER_ADDED = '1507166123370883484';

    private const TEMPLATE_BOOKING_COMPLETED = '1507166201163806658';

    private const TEMPLATE_VENDOR_REGISTERED = '1507166123433027280';

    private const TEMPLATE_VENDOR_APPROVED = '1507166123382723718';

    private const TEMPLATE_CUSTOMER_INQUIRY = '1507166123473550073';

    private const TEMPLATE_ADMIN_OTP_ENQUIRY = '1707172688661879481';


    /*
    |--------------------------------------------------------------------------
    | Booking Created
    |--------------------------------------------------------------------------
    |
    | Approved template:
    |
    | Thank you for choosing us, we will update your booking on our Duracabs
    | portal shortly...
    |
    */

    public function bookingCreated(string $mobile): array
    {
        $message =
            'Thank you for choosing us, we will update your booking on our Duracabs portal shortly. '
            . 'For more information you can call us : +91-70888 73332 and you can chat with us on WhatsApp '
            . 'by clicking on the link. https://wa.me/message/5KH6UMHGGYGZB1 Thanks!';

        return $this->sendTemplate(
            mobile: $mobile,
            templateId: self::TEMPLATE_BOOKING,
            message: $message,
            context: 'booking_created'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Booking Cancelled
    |--------------------------------------------------------------------------
    */

    public function bookingCancelled(
        string $mobile,
        string $bookingNumber
    ): array {
        $bookingNumber = $this->sanitizeVariable($bookingNumber);

        $message =
            "Dear customer, we apologize for your booking number {$bookingNumber} canceled by Dura cabs "
            . 'Please keep login your Dura cabs Account and check Your Booking Status or Email '
            . 'More Info Call: +917088873331 '
            . 'Click For Chat: https://wa.me/917088873331 From Duracabs';

        return $this->sendTemplate(
            mobile: $mobile,
            templateId: self::TEMPLATE_BOOKING_CANCELLED,
            message: $message,
            context: 'booking_cancelled'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Vendor Assigned
    |--------------------------------------------------------------------------
    */

    public function vendorAssigned(
        string $mobile,
        string $bookingNumber
    ): array {
        $bookingNumber = $this->sanitizeVariable($bookingNumber);

        $message =
            "Dear Vendor, Congratulations you have received a new booking number {$bookingNumber} from Dura Cabs "
            . 'Please Keep login Your Vendor Account and Update all Details '
            . 'More Info Call: +917088873331 '
            . 'Click For Chat: https://wa.me/917088873331 From Duracabs';

        return $this->sendTemplate(
            mobile: $mobile,
            templateId: self::TEMPLATE_VENDOR_ASSIGNED,
            message: $message,
            context: 'vendor_assigned'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Driver Added / Updated
    |--------------------------------------------------------------------------
    */

    public function driverAdded(
        string $mobile,
        string $bookingNumber
    ): array {
        $bookingNumber = $this->sanitizeVariable($bookingNumber);

        $message =
            "Dear Vendor, Dura cabs updated Driver Details in your booking number {$bookingNumber} "
            . 'Please Keep login Your Vendor Account and Update all Details '
            . 'More Info Call: +917088873331 '
            . 'Click For Chat: https://wa.me/917088873331 From Duracabs';

        return $this->sendTemplate(
            mobile: $mobile,
            templateId: self::TEMPLATE_DRIVER_ADDED,
            message: $message,
            context: 'driver_added'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Booking / Ride Completed
    |--------------------------------------------------------------------------
    */

    public function bookingCompleted(string $mobile): array
    {
        $message =
            'Your ride is completed now thanks for choosing Dura cabs we hope that your journey was Superb '
            . 'please share your journey experience with us so that we can improve our customer service. '
            . 'More Info Call : +917088873331 '
            . 'Click For Chat: https://wa.me/917088873331 from Duracabs';

        return $this->sendTemplate(
            mobile: $mobile,
            templateId: self::TEMPLATE_BOOKING_COMPLETED,
            message: $message,
            context: 'booking_completed'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Vendor Registration Submitted
    |--------------------------------------------------------------------------
    */

    public function vendorRegistered(string $mobile): array
    {
        /*
         * The URL below intentionally follows the currently approved
         * DLT template text supplied for this template.
         */
        $message =
            'Thanks for showing your Interest in working with Dura cabs No. 1. a One-way cab service Provider '
            . 'in North India. We will update your Vendor Account Status Through Email '
            . 'Please Keep Check Your Email '
            . 'More Info Call : +917088873331 '
            . 'Click For Chat: https://https://wa.me/917088873331 From Duracabs';

        return $this->sendTemplate(
            mobile: $mobile,
            templateId: self::TEMPLATE_VENDOR_REGISTERED,
            message: $message,
            context: 'vendor_registered'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Vendor Approved By Admin
    |--------------------------------------------------------------------------
    */

    public function vendorApproved(
        string $mobile,
        string $accountName
    ): array {
        $accountName = $this->sanitizeVariable($accountName);

        /*
         * The URL below intentionally follows the currently approved
         * DLT template text supplied for this template.
         */
        $message =
            "Congratulations!! You are now approved Vendor account name {$accountName} with Dura cabs "
            . 'Please check your email for Vendor login details '
            . 'More Info Call: +917088873331 '
            . 'Click For Chat: https://https://wa.me/917088873331 From Duracabs';

        return $this->sendTemplate(
            mobile: $mobile,
            templateId: self::TEMPLATE_VENDOR_APPROVED,
            message: $message,
            context: 'vendor_approved'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Customer Inquiry
    |--------------------------------------------------------------------------
    */

    public function customerInquiry(string $mobile): array
    {
        $message =
            'We have received your inquiry on the Dura cabs portal our one of sales Executive will Call You '
            . 'within 1-2 hours and If you need instant services then '
            . 'Call: +917088873331 '
            . 'Click For Chat: https://wa.me/917088873331 From Duracabs';

        return $this->sendTemplate(
            mobile: $mobile,
            templateId: self::TEMPLATE_CUSTOMER_INQUIRY,
            message: $message,
            context: 'customer_inquiry'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Admin OTP Inquiry Notification
    |--------------------------------------------------------------------------
    |
    | 4 DLT variables:
    | 1. Mobile number
    | 2. Service / enquiry
    | 3. Location / page / source
    | 4. Date and time
    |
    */

    public function adminOtpEnquiry(
        string $adminMobile,
        string $customerMobile,
        string $service,
        string $source,
        string $dateTime
    ): array {
        $customerMobile = $this->sanitizeVariable($customerMobile);
        $service = $this->sanitizeVariable($service);
        $source = $this->sanitizeVariable($source);
        $dateTime = $this->sanitizeVariable($dateTime);

        $message =
            "Dear Admin,New OTP enquiry received with mobile number {$customerMobile}, "
            . "for {$service} ({$source}), on Date and Time {$dateTime}. Duracabs";

        return $this->sendTemplate(
            mobile: $adminMobile,
            templateId: self::TEMPLATE_ADMIN_OTP_ENQUIRY,
            message: $message,
            context: 'admin_otp_enquiry'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Generic Approved Template Sender
    |--------------------------------------------------------------------------
    */

    public function sendTemplate(
        string $mobile,
        string $templateId,
        string $message,
        string $context = 'sms'
    ): array {
        $mobile = $this->cleanMobile($mobile);
        $templateId = trim($templateId);
        $message = trim($message);

        if (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
            return [
                'status' => false,
                'channel' => 'sms',
                'message' => 'Invalid 10-digit Indian mobile number.',
            ];
        }

        if ($templateId === '') {
            return [
                'status' => false,
                'channel' => 'sms',
                'message' => 'DLT template ID is missing.',
            ];
        }

        if ($message === '') {
            return [
                'status' => false,
                'channel' => 'sms',
                'message' => 'SMS message is empty.',
            ];
        }

        $apiKey = trim((string) config(
            'services.sambsms.api_key'
        ));

        $entityId = trim((string) config(
            'services.sambsms.entity_id'
        ));

        $routeId = trim((string) config(
            'services.sambsms.route_id'
        ));

        $senderId = trim((string) config(
            'services.sambsms.sender_id'
        ));

        if (
            $apiKey === ''
            || $entityId === ''
            || $routeId === ''
            || $senderId === ''
        ) {
            Log::error('SAMB SMS configuration missing.', [
                'context' => $context,
                'mobile' => $this->maskMobile($mobile),
                'api_key_configured' => $apiKey !== '',
                'entity_id_configured' => $entityId !== '',
                'route_id_configured' => $routeId !== '',
                'sender_id_configured' => $senderId !== '',
            ]);

            return [
                'status' => false,
                'channel' => 'sms',
                'message' => 'SMS gateway configuration is incomplete.',
            ];
        }

        $params = [
            'key' => $apiKey,
            'entity' => $entityId,
            'tempid' => $templateId,
            'campaign' => 0,
            'routeid' => $routeId,
            'type' => 'text',
            'contacts' => $mobile,
            'senderid' => $senderId,
            'msg' => $message,
        ];

        try {
            $response = Http::timeout(15)
                ->get(self::API_URL, $params);

            $body = trim((string) $response->body());

            Log::info('Dura Cabs SMS Gateway Response', [
                'context' => $context,
                'mobile' => $this->maskMobile($mobile),
                'template_id' => $templateId,
                'http_code' => $response->status(),
                'response' => $body,
            ]);

            if (!$response->successful()) {
                return [
                    'status' => false,
                    'channel' => 'sms',
                    'message' => 'SMS gateway returned an HTTP error.',
                    'http_code' => $response->status(),
                    'response' => $body,
                ];
            }

            $upperBody = strtoupper($body);

            if (
                str_contains($upperBody, 'ERR')
                || str_contains($upperBody, 'ERROR')
                || str_contains($upperBody, 'INVALID')
                || str_contains($upperBody, 'FAILED')
                || str_contains($upperBody, 'FAILURE')
            ) {
                Log::warning('Dura Cabs SMS rejected by gateway.', [
                    'context' => $context,
                    'mobile' => $this->maskMobile($mobile),
                    'template_id' => $templateId,
                    'response' => $body,
                ]);

                return [
                    'status' => false,
                    'channel' => 'sms',
                    'message' => 'SMS gateway rejected the request.',
                    'response' => $body,
                ];
            }

            return [
                'status' => true,
                'channel' => 'sms',
                'message' => 'SMS sent successfully.',
                'template_id' => $templateId,
                'response' => $body,
            ];
        } catch (Throwable $exception) {
            Log::error('Dura Cabs SMS sending failed.', [
                'context' => $context,
                'mobile' => $this->maskMobile($mobile),
                'template_id' => $templateId,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return [
                'status' => false,
                'channel' => 'sms',
                'message' => 'SMS service failed.',
                'error' => $exception->getMessage(),
            ];
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function cleanMobile(string $mobile): string
    {
        $mobile = preg_replace('/\D+/', '', $mobile) ?? '';

        if (
            strlen($mobile) > 10
            && str_starts_with($mobile, '91')
        ) {
            $mobile = substr($mobile, -10);
        }

        return $mobile;
    }

    private function sanitizeVariable(string $value): string
    {
        $value = trim($value);

        $value = preg_replace(
            '/\s+/',
            ' ',
            $value
        ) ?? '';

        return $value !== ''
            ? $value
            : 'N/A';
    }

    private function maskMobile(string $mobile): string
    {
        $mobile = preg_replace('/\D+/', '', $mobile) ?? '';

        if (strlen($mobile) <= 4) {
            return $mobile;
        }

        return str_repeat(
            '*',
            strlen($mobile) - 4
        ) . substr($mobile, -4);
    }
}