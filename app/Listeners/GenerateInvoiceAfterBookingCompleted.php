<?php

namespace App\Listeners;

use App\Events\BookingCompleted;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateInvoiceAfterBookingCompleted
{
    public function __construct(
        private readonly InvoiceService $invoiceService
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
                    'GenerateInvoiceAfterBookingCompleted: Booking number missing.',
                    [
                        'search_activity_id' => $event->search()->id ?? null,
                    ]
                );

                return;
            }

            $invoice = $this->invoiceService->resolve($bookingNumber);

            if (!$invoice) {
                Log::warning(
                    'Invoice generation skipped. Booking not found.',
                    [
                        'booking_number' => $bookingNumber,
                    ]
                );

                return;
            }

            $shareUrl = $this->invoiceService
                ->createTemporaryShareUrl($bookingNumber);

            Log::info(
                'Invoice generated successfully.',
                [
                    'booking_number' => $bookingNumber,
                    'invoice_number' => $invoice['invoice_no'] ?? null,
                    'record_type' => $invoice['record_type'] ?? null,
                    'share_url' => $shareUrl,
                ]
            );

            /**
             * Future integrations:
             *
             * - Generate PDF
             * - Upload PDF to Storage/S3
             * - Email Invoice
             * - WhatsApp Invoice
             * - Push Notification
             * - GST Invoice
             * - Accounting Sync
             */
        } catch (Throwable $e) {
            Log::error(
                'GenerateInvoiceAfterBookingCompleted failed.',
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