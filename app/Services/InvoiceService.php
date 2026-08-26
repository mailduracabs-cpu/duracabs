<?php

namespace App\Services;

use App\Models\Order;
use App\Models\SelfDriveBooking;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class InvoiceService
{
    public function __construct(
        private readonly FinalBillingService $finalBillingService
    ) {
    }

    public function resolve(string $bookingNumber): ?array
    {
        $bookingNumber = trim($bookingNumber);

        if ($bookingNumber === '') {
            return null;
        }

        $taxiBooking = $this->findTaxiBooking($bookingNumber);

        if ($taxiBooking) {
            return $this->buildTaxiInvoice($taxiBooking);
        }

        $selfDriveBooking = $this->findSelfDriveBooking(
            $bookingNumber
        );

        if ($selfDriveBooking) {
            return $this->buildSelfDriveInvoice(
                $selfDriveBooking
            );
        }

        return null;
    }

    public function createTemporaryShareUrl(
        string $bookingNumber,
        int $hours = 24
    ): ?string {
        if (! URL::hasValidSignature(request())) {
            $hours = max(1, min($hours, 168));
        }

        if (! \Illuminate\Support\Facades\Route::has('invoice.shared')) {
            return null;
        }

        return URL::temporarySignedRoute(
            'invoice.shared',
            now()->addHours($hours),
            [
                'booking' => $bookingNumber,
            ]
        );
    }

    public function invoiceFileName(array $invoice): string
    {
        $bookingNumber = preg_replace(
            '/[^A-Za-z0-9\-]/',
            '',
            (string) ($invoice['booking_no'] ?? 'Booking')
        );

        return 'Dura-Cabs-Invoice-' . $bookingNumber . '.pdf';
    }

    private function findTaxiBooking(
        string $bookingNumber
    ): ?Order {
        if (! Schema::hasTable('orders')) {
            return null;
        }

        $query = Order::query();

        if (Schema::hasColumn('orders', 'booking_no')) {
            $record = $query
                ->where('booking_no', $bookingNumber)
                ->first();

            if ($record) {
                return $record;
            }
        }

        $id = $this->extractId($bookingNumber);

        return $id
            ? Order::query()->find($id)
            : null;
    }

    private function findSelfDriveBooking(
        string $bookingNumber
    ): ?SelfDriveBooking {
        if (! Schema::hasTable('self_drive_bookings')) {
            return null;
        }

        $query = SelfDriveBooking::query();

        if (
            Schema::hasColumn(
                'self_drive_bookings',
                'booking_no'
            )
        ) {
            $record = $query
                ->where('booking_no', $bookingNumber)
                ->first();

            if ($record) {
                return $record;
            }
        }

        $id = $this->extractId($bookingNumber);

        return $id
            ? SelfDriveBooking::query()->find($id)
            : null;
    }

    private function buildTaxiInvoice(
        Order $order
    ): array {
        $order->loadMissing([
            'user',
            'vehicle',
            'items',
            'address',
        ]);

        $extra = is_array($order->extraOptions)
            ? $order->extraOptions
            : [];

        $baseFare = (float) (
            $extra['base_fare']
            ?? $order->items->sum('total_ammount')
            ?? 0
        );

        $specialRequests = is_array(
            $extra['special_requests'] ?? null
        )
            ? $extra['special_requests']
            : [];

        $billing = $this->finalBillingService->calculate([
            'service_type' => 'with_driver',
            'base_fare' => $baseFare,
            'special_request_total' =>
                $extra['special_request_total'] ?? 0,
            'extra_hour_amount' =>
                $extra['extra_hour_amount'] ?? 0,
            'extra_km_amount' =>
                $extra['extra_km_amount'] ?? 0,
            'toll_amount' =>
                $extra['toll_amount'] ?? 0,
            'parking_amount' =>
                $extra['parking_amount'] ?? 0,
            'government_tax_amount' =>
                $extra['government_tax_amount']
                ?? $extra['tax_amount']
                ?? 0,
            'coupon_discount' =>
                $order->coupon_value ?? 0,
            'payment_method' =>
                $order->payment_method ?? 'cash',
            'paid_amount' =>
                $order->paid_amount ?? 0,
        ]);

        return [
            'record_type' => 'taxi',
            'record_id' => (int) $order->id,
            'booking_no' => $order->booking_number,
            'invoice_no' => $this->invoiceNumber(
                'TX',
                (int) $order->id
            ),
            'invoice_date' => now()->format('d M Y'),
            'service_type' => $this->rideTypeLabel(
                (string) ($order->ride_type ?? 'one_way')
            ),
            'status' => $this->statusLabel(
                (string) ($order->status ?? 'new')
            ),
            'customer' => [
                'name' => $order->address?->full_name
                    ?? $order->user?->name
                    ?? 'Dura Cabs Customer',
                'mobile' => $order->address?->phone
                    ?? $order->user?->mobile
                    ?? '',
                'email' => $order->address?->email
                    ?? $order->user?->email
                    ?? '',
            ],
            'trip' => [
                'pickup' => $order->address?->pickup_address
                    ?? $order->booking_from
                    ?? '',
                'drop' => $order->address?->drop_address
                    ?? $order->booking_to
                    ?? '',
                'pickup_city' => $order->cityFrom ?? '',
                'drop_city' => $order->cityTo ?? '',
                'pickup_date' => $order->date ?? '',
                'pickup_time' => $order->time ?? '',
                'return_date' => $order->dateTo ?? '',
                'return_time' => $order->endTime ?? '',
                'vehicle_name' => $order->productName
                    ?? $order->taxi_type
                    ?? '',
                'vehicle_number' =>
                    $order->vehicle?->vehicle_number ?? '',
                'driver_name' => $this->userName(
                    $order->driver_id
                ),
                'driver_mobile' => $this->userMobile(
                    $order->driver_id
                ),
                'vendor_name' => $this->userName(
                    $order->transporter_id
                ),
            ],
            'extra_services' => $specialRequests,
            'fare' => array_merge(
                $billing,
                [
                    'parking_amount' =>
                        (float) (
                            $extra['parking_amount']
                            ?? 0
                        ),
                ]
            ),
            'payment' => [
                'method' => $order->payment_method
                    ?? '',
                'status' => $order->payment_status
                    ?? 'pending',
                'reference' =>
                    $extra['razorpay_payment_id']
                    ?? $extra['payment_reference']
                    ?? '',
            ],
            'notes' => $order->notes ?? '',
        ];
    }

    private function buildSelfDriveInvoice(
        SelfDriveBooking $booking
    ): array {
        $booking->loadMissing([
            'customer',
            'vehicle',
            'transporter',
        ]);

        $billing = $booking->finalBilling();

        $extraServices = $this->selfDriveExtraServices(
            $booking
        );

        return [
            'record_type' => 'self_drive',
            'record_id' => (int) $booking->id,
            'booking_no' => $booking->booking_no
                ?: $this->fallbackBookingNumber(
                    (int) $booking->id
                ),
            'invoice_no' => $this->invoiceNumber(
                'SD',
                (int) $booking->id
            ),
            'invoice_date' => now()->format('d M Y'),
            'service_type' => 'Self Drive',
            'status' => $this->statusLabel(
                (string) (
                    $booking->booking_status
                    ?? $booking->status
                    ?? 'pending'
                )
            ),
            'customer' => [
                'name' => $booking->customer?->name
                    ?? 'Dura Cabs Customer',
                'mobile' => $booking->customer?->mobile
                    ?? '',
                'email' => $booking->customer?->email
                    ?? '',
            ],
            'trip' => [
                'pickup' => $booking->pickup_location
                    ?? '',
                'drop' => '',
                'pickup_city' => '',
                'drop_city' => '',
                'pickup_date' =>
                    $booking->start_datetime?->format(
                        'd M Y'
                    ) ?? '',
                'pickup_time' =>
                    $booking->start_datetime?->format(
                        'h:i A'
                    ) ?? '',
                'return_date' =>
                    $booking->end_datetime?->format(
                        'd M Y'
                    ) ?? '',
                'return_time' =>
                    $booking->end_datetime?->format(
                        'h:i A'
                    ) ?? '',
                'vehicle_name' => trim(
                    ($booking->vehicle?->car_company_name
                        ?? '')
                    . ' '
                    . ($booking->vehicle?->model_name
                        ?? '')
                ),
                'vehicle_number' =>
                    $booking->vehicle?->vehicle_number
                    ?? '',
                'driver_name' => '',
                'driver_mobile' => '',
                'vendor_name' =>
                    $booking->transporter?->company_name
                    ?? $booking->transporter?->name
                    ?? '',
            ],
            'agreement' => [
                'booking_no' => $booking->booking_no
                    ?: $this->fallbackBookingNumber((int) $booking->id),
                'renter_name' => $booking->customer?->name ?? '',
                'id_number' => $booking->customer?->aadhar_number ?? '',
                'address' => $booking->customer?->office_address ?? '',
                'hotel' => $booking->getAttribute('hotel_name') ?? '',
                'room_no' => $booking->getAttribute('room_no') ?? '',
                'mobile' => $booking->customer?->mobile ?? '',
                'secondary_mobile' => $booking->getAttribute('secondary_mobile') ?? '',
                'car_number' => $booking->vehicle?->vehicle_number ?? '',
                'car_name' => trim(
                    ($booking->vehicle?->car_company_name ?? '')
                    . ' '
                    . ($booking->vehicle?->model_name ?? '')
                ),
                'car_color' => $booking->vehicle?->color
                    ?? $booking->vehicle?->vehicle_color
                    ?? '',
                'trip_plan' => $booking->getAttribute('trip_plan')
                    ?? $booking->pickup_location
                    ?? '',
                'start_date' => $booking->start_datetime?->format('d M Y') ?? '',
                'start_time' => $booking->start_datetime?->format('h:i A') ?? '',
                'end_date' => $booking->end_datetime?->format('d M Y') ?? '',
                'end_time' => $booking->end_datetime?->format('h:i A') ?? '',
                // Single source of truth: manual price overrides DB price.
                'booking_amount' => $booking->effectiveRentalAmount(),
                'security_deposit' => (float) ($booking->security_deposit ?? 0),
                'primary_operator' => $booking->customer?->name ?? '',
                'operator_mobile' => $booking->customer?->mobile ?? '',
                'driving_licence_number' =>
                    $booking->customer?->driving_licence_number ?? '',
                'pickup_date' => $booking->trip_start_datetime?->format('d M Y')
                    ?? $booking->start_datetime?->format('d M Y')
                    ?? '',
                'pickup_time' => $booking->trip_start_datetime?->format('h:i A')
                    ?? $booking->start_datetime?->format('h:i A')
                    ?? '',
                'return_date' => $booking->trip_end_datetime?->format('d M Y')
                    ?? $booking->end_datetime?->format('d M Y')
                    ?? '',
                'return_time' => $booking->trip_end_datetime?->format('h:i A')
                    ?? $booking->end_datetime?->format('h:i A')
                    ?? '',
            ],
            'extra_services' => $extraServices,
            'fare' => array_merge(
                $billing,
                [
                    // Central pricing source shared with Admin/payment/messages.
                    'base_fare' => $booking->effectiveRentalAmount(),
                    'manual_price' => (float) ($booking->manual_price ?? 0),
                    'taxable_amount' => $booking->taxableRentalAmount(),
                    'gst_amount' => $booking->includedGstAmount(),
                    'rental_total' => $booking->effectiveRentalAmount(),
                    'security_deposit' => (float) ($booking->security_deposit ?? 0),
                    'grand_total' => $booking->payableAmount(),
                    'paid_amount' => (float) ($booking->paid_amount ?? 0),
                    'remaining_amount' => max(
                        0,
                        round(
                            $booking->payableAmount()
                            - (float) ($booking->paid_amount ?? 0),
                            2
                        )
                    ),
                    'parking_amount' =>
                        (float) (
                            $booking->parking_amount
                            ?? 0
                        ),
                ]
            ),
            'payment' => [
                'method' => $booking->payment_method
                    ?? '',
                'status' => $booking->payment_status
                    ?? 'pending',
                'reference' =>
                    $booking->payment_reference
                    ?? '',
            ],
            'notes' => $booking->damage_note
                ?? $booking->other_charge_note
                ?? '',
        ];
    }

    private function selfDriveExtraServices(
        SelfDriveBooking $booking
    ): array {
        $services = [];

        if ($booking->hasUnlimitedKms()) {
            $services[] = [
                'name' => 'Unlimited Kilometres',
                'price' => (float) (
                    $booking->extra_service_amount
                    ?? $booking->special_request_total
                    ?? 0
                ),
            ];
        }

        $raw = $booking->getAttribute('extra_services');

        if (is_array($raw)) {
            foreach ($raw as $service) {
                $service = is_array($service)
                    ? $service
                    : (array) $service;

                $services[] = [
                    'name' => $service['name']
                        ?? $service['title']
                        ?? 'Extra Service',
                    'price' => (float) (
                        $service['price']
                        ?? $service['amount']
                        ?? 0
                    ),
                ];
            }
        }

        return $services;
    }

    private function userName(
        mixed $userId
    ): string {
        if (! $userId || ! Schema::hasTable('users')) {
            return '';
        }

        return (string) (
            DB::table('users')
                ->where('id', $userId)
                ->value('name')
            ?? ''
        );
    }

    private function userMobile(
        mixed $userId
    ): string {
        if (! $userId || ! Schema::hasTable('users')) {
            return '';
        }

        return (string) (
            DB::table('users')
                ->where('id', $userId)
                ->value('mobile')
            ?? ''
        );
    }

    private function invoiceNumber(
        string $prefix,
        int $id
    ): string {
        return sprintf(
            'INV-%s-%s',
            $prefix,
            str_pad(
                (string) $id,
                6,
                '0',
                STR_PAD_LEFT
            )
        );
    }

    private function fallbackBookingNumber(
        int $id
    ): string {
        return 'DURA' . str_pad(
            (string) $id,
            6,
            '0',
            STR_PAD_LEFT
        );
    }

    private function extractId(
        string $value
    ): ?int {
        if (ctype_digit($value)) {
            return (int) $value;
        }

        if (
            preg_match(
                '/^(?:DURA|SD)0*(\d+)$/i',
                $value,
                $matches
            ) === 1
        ) {
            return (int) $matches[1];
        }

        return null;
    }

    private function rideTypeLabel(
        string $value
    ): string {
        return match (strtolower($value)) {
            'one_way' => 'One Way',
            'return', 'round_trip' => 'Round Trip',
            'local' => 'Local',
            'airport' => 'Airport',
            'self_drive' => 'Self Drive',
            default => ucfirst(
                str_replace('_', ' ', $value)
            ),
        };
    }

    private function statusLabel(
        string $value
    ): string {
        return ucwords(
            str_replace(
                ['_', '-'],
                ' ',
                strtolower($value)
            )
        );
    }
}