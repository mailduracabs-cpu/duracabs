<?php

namespace App\Listeners;

use App\Events\BookingCompleted;
use App\Services\EmailService;
use App\Services\FirebaseService;
use App\Services\InvoiceService;
use App\Services\NotificationService;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotifyCustomerAfterBookingCompleted
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly WhatsAppService $whatsAppService,
        private readonly EmailService $emailService,
        private readonly FirebaseService $firebaseService,
        private readonly InvoiceService $invoiceService,
    ) {
    }

    /**
     * Handle the event.
     */
    public function handle(BookingCompleted $event): void
    {
        try {

            $booking = $event->booking();

            $bookingNumber = $booking['booking_no']
                ?? $booking['booking_number']
                ?? null;

            if (blank($bookingNumber)) {
                Log::warning(
                    'NotifyCustomerAfterBookingCompleted: Booking number missing.'
                );

                return;
            }

            $invoice = $this->invoiceService->resolve(
                $bookingNumber
            );

            $shareUrl = $this->invoiceService
                ->createTemporaryShareUrl(
                    $bookingNumber
                );

            $customer = [
                'name' => data_get($invoice, 'customer.name'),
                'mobile' => data_get($invoice, 'customer.mobile'),
                'email' => data_get($invoice, 'customer.email'),
            ];

            $notificationData = [
                'title' => 'Booking Completed',
                'message' =>
                    'Thank you for choosing Dura Cabs. Your trip has been completed successfully.',
                'booking_number' => $bookingNumber,
                'invoice_url' => $shareUrl,
            ];

            /*
            |--------------------------------------------------------------------------
            | In-App Notification
            |--------------------------------------------------------------------------
            */
            try {

                if (
                    method_exists(
                        $this->notificationService,
                        'send'
                    )
                ) {
                    $this->notificationService->send(
                        $customer,
                        $notificationData
                    );
                }

            } catch (Throwable $e) {
                Log::warning(
                    'In-app notification failed.',
                    [
                        'message' => $e->getMessage(),
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | WhatsApp
            |--------------------------------------------------------------------------
            */
            try {

                if (
                    method_exists(
                        $this->whatsAppService,
                        'sendBookingCompleted'
                    )
                ) {

                    $this->whatsAppService
                        ->sendBookingCompleted(
                            mobile: $customer['mobile'],
                            customerName: $customer['name'],
                            bookingNumber: $bookingNumber,
                            invoiceUrl: $shareUrl
                        );
                }

            } catch (Throwable $e) {

                Log::warning(
                    'WhatsApp notification failed.',
                    [
                        'message' => $e->getMessage(),
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Email
            |--------------------------------------------------------------------------
            */
            try {

                if (
                    method_exists(
                        $this->emailService,
                        'sendBookingCompleted'
                    )
                ) {

                    $this->emailService
                        ->sendBookingCompleted(
                            email: $customer['email'],
                            customerName: $customer['name'],
                            bookingNumber: $bookingNumber,
                            invoiceUrl: $shareUrl
                        );
                }

            } catch (Throwable $e) {

                Log::warning(
                    'Email notification failed.',
                    [
                        'message' => $e->getMessage(),
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Push Notification
            |--------------------------------------------------------------------------
            */
            try {

                if (
                    method_exists(
                        $this->firebaseService,
                        'sendBookingCompleted'
                    )
                ) {

                    $this->firebaseService
                        ->sendBookingCompleted(
                            $customer,
                            $notificationData
                        );
                }

            } catch (Throwable $e) {

                Log::warning(
                    'Push notification failed.',
                    [
                        'message' => $e->getMessage(),
                    ]
                );
            }

            Log::info(
                'Customer booking completion notifications processed.',
                [
                    'booking_number' => $bookingNumber,
                    'customer_mobile' => $customer['mobile'],
                    'customer_email' => $customer['email'],
                    'invoice_url' => $shareUrl,
                ]
            );

        } catch (Throwable $e) {

            Log::error(
                'NotifyCustomerAfterBookingCompleted failed.',
                [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            throw $e;
        }
    }
}