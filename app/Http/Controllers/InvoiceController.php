<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\SelfDriveBooking;
use App\Services\BookingAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly BookingAccessService $bookingAccessService
    ) {
    }

    public function download(
        Request $request,
        string $booking
    ) {
        $invoice = $this->resolveInvoice($booking);

        abort_if(
            ! $invoice,
            Response::HTTP_NOT_FOUND,
            'Booking not found.'
        );

        $this->authorizeInvoice(
            $request,
            $invoice
        );

        return $this->downloadPdf($invoice);
    }

    public function shared(
        Request $request,
        string $booking
    ) {
        $invoice = $this->resolveInvoice($booking);

        abort_if(
            ! $invoice,
            Response::HTTP_NOT_FOUND,
            'Booking not found.'
        );

        return view(
            'invoices.booking',
            [
                'invoice' => $invoice,
                'isSharedView' => true,
            ]
        );
    }

    private function downloadPdf(array $invoice)
    {
        $fileName = sprintf(
            'Dura-Cabs-Invoice-%s.pdf',
            $invoice['booking_no']
        );

        if (
            class_exists(
                \Barryvdh\DomPDF\Facade\Pdf::class
            )
        ) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
                'invoices.booking',
                [
                    'invoice' => $invoice,
                    'isSharedView' => false,
                ]
            )->setPaper('a4');

            return $pdf->download($fileName);
        }

        return response()
            ->view(
                'invoices.booking',
                [
                    'invoice' => $invoice,
                    'isSharedView' => false,
                    'pdfPackageMissing' => true,
                ]
            )
            ->header(
                'Content-Disposition',
                'inline; filename="' . $fileName . '"'
            );
    }

    private function resolveInvoice(
        string $booking
    ): ?array {
        $booking = trim($booking);

        if ($booking === '') {
            return null;
        }

        $taxi = $this->findTaxiBooking($booking);

        if ($taxi) {
            return $this->taxiInvoice($taxi);
        }

        $selfDrive = $this->findSelfDriveBooking(
            $booking
        );

        if ($selfDrive) {
            return $this->selfDriveInvoice(
                $selfDrive
            );
        }

        return null;
    }

    private function findTaxiBooking(
        string $booking
    ): ?object {
        if (! Schema::hasTable('orders')) {
            return null;
        }

        if (
            Schema::hasColumn('orders', 'booking_no')
        ) {
            $record = DB::table('orders')
                ->where('booking_no', $booking)
                ->first();

            if ($record) {
                return $record;
            }
        }

        $id = $this->extractBookingId($booking);

        return $id
            ? DB::table('orders')
                ->where('id', $id)
                ->first()
            : null;
    }

    private function findSelfDriveBooking(
        string $booking
    ): ?object {
        if (
            ! Schema::hasTable(
                'self_drive_bookings'
            )
        ) {
            return null;
        }

        $query = DB::table(
            'self_drive_bookings'
        );

        if (
            Schema::hasColumn(
                'self_drive_bookings',
                'booking_no'
            )
        ) {
            $record = $query
                ->where('booking_no', $booking)
                ->first();

            if ($record) {
                return $record;
            }
        }

        $id = $this->extractBookingId($booking);

        return $id
            ? DB::table('self_drive_bookings')
                ->where('id', $id)
                ->first()
            : null;
    }

    private function taxiInvoice(
        object $order
    ): array {
        $extra = $this->decodeJson(
            $order->extraOptions ?? null
        );

        $address = Schema::hasTable('addresses')
            ? DB::table('addresses')
                ->where('order_id', $order->id)
                ->first()
            : null;

        $customer = Schema::hasTable('users')
            ? DB::table('users')
                ->where('id', $order->user_id)
                ->first()
            : null;

        $driver = (
            isset($order->driver_id)
            && $order->driver_id
            && Schema::hasTable('users')
        )
            ? DB::table('users')
                ->where('id', $order->driver_id)
                ->first()
            : null;

        $transporter = (
            isset($order->transporter_id)
            && $order->transporter_id
            && Schema::hasTable('users')
        )
            ? DB::table('users')
                ->where('id', $order->transporter_id)
                ->first()
            : null;

        $vehicle = (
            isset($order->vehicle_id)
            && $order->vehicle_id
            && Schema::hasTable('vehicles')
        )
            ? DB::table('vehicles')
                ->where('id', $order->vehicle_id)
                ->first()
            : null;

        $baseFare = (float) (
            $extra['base_fare']
            ?? $this->orderItemsTotal($order->id)
            ?? 0
        );

        $gst = (float) (
            $extra['gst_amount']
            ?? $order->tax
            ?? 0
        );

        return [
            'record_type' => 'taxi',
            'record_id' => (int) $order->id,
            'booking_no' => $this->bookingNumber(
                $order
            ),
            'invoice_no' => $this->invoiceNumber(
                'TX',
                (int) $order->id
            ),
            'invoice_date' => now()->format(
                'd M Y'
            ),
            'service_type' => $this->label(
                $extra['service_type']
                ?? $order->ride_type
                ?? 'taxi'
            ),
            'status' => $this->label(
                $order->status ?? 'pending'
            ),
            'customer' => [
                'name' => $customer->name
                    ?? $address->full_name
                    ?? 'Dura Cabs Customer',
                'mobile' => $customer->mobile
                    ?? $address->phone
                    ?? '',
                'email' => $customer->email
                    ?? $address->email
                    ?? '',
            ],
            'trip' => [
                'pickup' => $order->booking_from
                    ?? $address->pickup_address
                    ?? '',
                'drop' => $order->booking_to
                    ?? $address->drop_address
                    ?? '',
                'pickup_city' => $order->cityFrom
                    ?? '',
                'drop_city' => $order->cityTo
                    ?? '',
                'pickup_date' => $order->date
                    ?? '',
                'pickup_time' => $order->time
                    ?? '',
                'return_date' => $order->dateTo
                    ?? '',
                'return_time' => $order->endTime
                    ?? '',
                'vehicle_name' => $order->productName
                    ?? $order->taxi_type
                    ?? '',
                'vehicle_number' =>
                    $vehicle->vehicle_number
                    ?? '',
                'driver_name' => $driver->name
                    ?? '',
                'driver_mobile' => $driver->mobile
                    ?? '',
                'vendor_name' => $transporter->name
                    ?? '',
            ],
            'fare' => [
                'base_fare' => $baseFare,
                'toll_amount' => (float) (
                    $extra['toll_amount']
                    ?? 0
                ),
                'tax_amount' => (float) (
                    $extra['tax_amount']
                    ?? 0
                ),
                'special_request_total' =>
                    (float) (
                        $extra[
                            'special_request_total'
                        ]
                        ?? 0
                    ),
                'coupon_discount' => (float) (
                    $order->coupon_value
                    ?? 0
                ),
                'gst_percent' => (float) (
                    $extra['gst_percent']
                    ?? 5
                ),
                'gst_amount' => $gst,
                'security_deposit' => 0.0,
                'extra_km_amount' => 0.0,
                'extra_hour_amount' => 0.0,
                'damage_amount' => 0.0,
                'other_charges' => 0.0,
                'grand_total' => (float) (
                    $order->grand_total
                    ?? 0
                ),
                'paid_amount' =>
                    $this->paidAmount($order),
                'remaining_amount' =>
                    $this->remainingAmount(
                        $order
                    ),
            ],
            'payment' => [
                'method' => $order->payment_method
                    ?? '',
                'status' => $order->payment_status
                    ?? 'pending',
                'reference' =>
                    $extra[
                        'razorpay_payment_id'
                    ]
                    ?? $extra[
                        'payment_reference'
                    ]
                    ?? '',
            ],
            'notes' => $order->notes ?? '',
        ];
    }


    private function selfDriveInvoice(
        object $booking
    ): array {
        $customer = Schema::hasTable('users')
            ? DB::table('users')
                ->where(
                    'id',
                    $booking->customer_id
                )
                ->first()
            : null;

        $vehicle = Schema::hasTable('vehicles')
            ? DB::table('vehicles')
                ->where(
                    'id',
                    $booking->vehicle_id
                )
                ->first()
            : null;

        $transporter = (
            isset($booking->transporter_profile_id)
            && Schema::hasTable('transporter_profiles')
        )
            ? DB::table('transporter_profiles')
                ->where(
                    'id',
                    $booking->transporter_profile_id
                )
                ->first()
            : null;

        $pricingBreakdown = $this->selfDrivePricingBreakdown(
            $booking
        );

        $settlementCharges = $this->selfDriveSettlementCharges(
            $booking
        );

        $storedPayable = (float) (
            $pricingBreakdown['payable_amount']
            ?? $booking->final_amount
            ?? $booking->total_amount
            ?? 0
        );

        $grandTotal = (float) (
            $booking->final_amount
            ?? (
                $storedPayable
                + $settlementCharges['extra_km_amount']
                + $settlementCharges['extra_hour_amount']
                + $settlementCharges['damage_amount']
                + $settlementCharges['other_charges']
            )
        );

        $paidAmount = (float) (
            $booking->paid_amount
            ?? 0
        );

        $remainingAmount = (float) (
            $booking->remaining_amount
            ?? max(0, $grandTotal - $paidAmount)
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
            'status' => $this->label(
                $booking->booking_status
                ?? $booking->status
                ?? 'pending'
            ),
            'customer' => [
                'name' => $customer->name
                    ?? 'Dura Cabs Customer',
                'mobile' => $customer->mobile
                    ?? '',
                'email' => $customer->email
                    ?? '',
            ],
            'trip' => [
                'pickup' => $booking->pickup_location
                    ?? '',
                'drop' => '',
                'pickup_city' => '',
                'drop_city' => '',
                'pickup_date' => $booking->start_datetime
                    ?? '',
                'pickup_time' => '',
                'return_date' => $booking->end_datetime
                    ?? '',
                'return_time' => '',
                'rental_mode' => $this->label(
                    (string) (
                        $pricingBreakdown['mode']
                        ?? ''
                    )
                ),
                'rental_duration' =>
                    $this->selfDriveDurationLabel(
                        $pricingBreakdown
                    ),
                'vehicle_name' => trim(
                    ($vehicle->car_company_name ?? '')
                    . ' '
                    . ($vehicle->model_name ?? '')
                ),
                'vehicle_number' =>
                    $vehicle->vehicle_number
                    ?? '',
                'driver_name' => '',
                'driver_mobile' => '',
                'vendor_name' =>
                    $transporter->company_name
                    ?? $transporter->name
                    ?? '',
            ],
            'pricing_breakdown' => $pricingBreakdown,
            'fare' => [
                'pricing_breakdown' => $pricingBreakdown,
                'base_fare' => (float) (
                    $pricingBreakdown['base_amount']
                    ?? $pricingBreakdown['rent']
                    ?? $booking->total_amount
                    ?? 0
                ),
                'plan_discount' => (float) (
                    $pricingBreakdown['discount_amount']
                    ?? 0
                ),
                'toll_amount' => 0.0,
                'parking_amount' => 0.0,
                'tax_amount' => 0.0,
                'special_request_total' => (float) (
                    $pricingBreakdown['extras_total']
                    ?? 0
                ),
                'coupon_discount' => (float) (
                    $pricingBreakdown['coupon_discount']
                    ?? 0
                ),
                'gst_percent' => (float) (
                    $pricingBreakdown['gst_percent']
                    ?? 18
                ),
                'gst_amount' => (float) (
                    $pricingBreakdown['gst_amount']
                    ?? 0
                ),
                'security_deposit' => (float) (
                    $pricingBreakdown['security_deposit']
                    ?? $booking->security_deposit
                    ?? 0
                ),
                'extra_km_amount' =>
                    $settlementCharges['extra_km_amount'],
                'extra_hour_amount' =>
                    $settlementCharges['extra_hour_amount'],
                'damage_amount' =>
                    $settlementCharges['damage_amount'],
                'other_charges' =>
                    $settlementCharges['other_charges'],
                'grand_total' => $grandTotal,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
            ],
            'payment' => [
                'method' => $booking->payment_method
                    ?? '',
                'status' => $booking->payment_status
                    ?? 'pending',
                'reference' => $booking->payment_reference
                    ?? '',
            ],
            'notes' =>
                $booking->damage_note
                ?? $booking->other_charge_note
                ?? '',
        ];
    }

    private function selfDrivePricingBreakdown(
        object $booking
    ): array {
        $direct = $this->decodeArrayValue(
            $booking->pricing_breakdown
            ?? null
        );

        if ($direct !== []) {
            return $direct;
        }

        foreach (
            [
                $booking->extra_options ?? null,
                $booking->extraOptions ?? null,
                $booking->metadata ?? null,
                $booking->meta ?? null,
            ] as $payload
        ) {
            $decoded = $this->decodeArrayValue($payload);

            $pricing = $this->decodeArrayValue(
                $decoded['pricing_breakdown']
                ?? null
            );

            if ($pricing !== []) {
                return $pricing;
            }
        }

        return [
            'mode' => $booking->rental_mode
                ?? $booking->pricing_mode
                ?? 'daily',
            'base_amount' => (float) (
                $booking->total_amount
                ?? 0
            ),
            'discount_amount' => 0.0,
            'rent' => (float) (
                $booking->total_amount
                ?? 0
            ),
            'extras' => [],
            'extras_total' => 0.0,
            'coupon_discount' => 0.0,
            'taxable_amount' => (float) (
                $booking->total_amount
                ?? 0
            ),
            'gst_percent' => 18.0,
            'gst_amount' => 0.0,
            'security_deposit' => (float) (
                $booking->security_deposit
                ?? 0
            ),
            'payable_amount' => (float) (
                $booking->final_amount
                ?? $booking->total_amount
                ?? 0
            ),
        ];
    }

    private function selfDriveSettlementCharges(
        object $booking
    ): array {
        return [
            'extra_km_amount' => (float) (
                $booking->extra_km_amount
                ?? 0
            ),
            'extra_hour_amount' => (float) (
                $booking->extra_hour_amount
                ?? 0
            ),
            'damage_amount' => (float) (
                $booking->damage_amount
                ?? 0
            ),
            'other_charges' => (float) (
                ($booking->fuel_charge ?? 0)
                + ($booking->cleaning_charge ?? 0)
                + ($booking->late_return_charge ?? 0)
                + ($booking->other_charge ?? 0)
            ),
        ];
    }

    private function selfDriveDurationLabel(
        array $pricingBreakdown
    ): string {
        $mode = strtolower(
            (string) (
                $pricingBreakdown['mode']
                ?? ''
            )
        );

        $durationMap = [
            'hourly' => [
                'value' => $pricingBreakdown['chargeable_hours']
                    ?? $pricingBreakdown['selected_hours']
                    ?? null,
                'singular' => 'hour',
                'plural' => 'hours',
            ],
            'daily' => [
                'value' => $pricingBreakdown['total_days']
                    ?? null,
                'singular' => 'day',
                'plural' => 'days',
            ],
            'weekly' => [
                'value' => $pricingBreakdown['total_weeks']
                    ?? null,
                'singular' => 'week',
                'plural' => 'weeks',
            ],
            'monthly' => [
                'value' => $pricingBreakdown['total_months']
                    ?? null,
                'singular' => 'month',
                'plural' => 'months',
            ],
        ];

        $duration = $durationMap[$mode]
            ?? null;

        if (
            ! is_array($duration)
            || $duration['value'] === null
        ) {
            return '';
        }

        $value = (float) $duration['value'];
        $formatted = fmod($value, 1.0) === 0.0
            ? (string) (int) $value
            : rtrim(
                rtrim(
                    number_format($value, 2, '.', ''),
                    '0'
                ),
                '.'
            );

        return sprintf(
            '%s %s',
            $formatted,
            $value === 1.0
                ? $duration['singular']
                : $duration['plural']
        );
    }

    private function decodeArrayValue(
        mixed $value
    ): array {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return (array) $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded)
            ? $decoded
            : [];
    }

    private function authorizeInvoice(
        Request $request,
        array $invoice
    ): void {
        if ($invoice['record_type'] === 'taxi') {
            $order = Order::query()->find(
                $invoice['record_id']
            );

            abort_if(
                ! $order,
                Response::HTTP_NOT_FOUND,
                'Booking not found.'
            );

            $this->bookingAccessService->authorizeOrder(
                $request->user(),
                $order,
                'Please login to download this invoice.'
            );

            return;
        }

        if ($invoice['record_type'] === 'self_drive') {
            $booking = SelfDriveBooking::query()->find(
                $invoice['record_id']
            );

            abort_if(
                ! $booking,
                Response::HTTP_NOT_FOUND,
                'Booking not found.'
            );

            $this->bookingAccessService->authorizeSelfDriveBooking(
                $request->user(),
                $booking,
                'Please login to download this invoice.'
            );

            return;
        }

        abort(
            Response::HTTP_FORBIDDEN,
            'You are not allowed to download this invoice.'
        );
    }

    private function bookingNumber(
        object $order
    ): string {
        return filled(
            $order->booking_no ?? null
        )
            ? (string) $order->booking_no
            : $this->fallbackBookingNumber(
                (int) $order->id
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

    private function extractBookingId(
        string $value
    ): ?int {
        if (ctype_digit($value)) {
            return (int) $value;
        }

        if (
            preg_match(
                '/^DURA0*(\d+)$/i',
                $value,
                $matches
            ) === 1
        ) {
            return (int) $matches[1];
        }

        return null;
    }

    private function decodeJson(
        $value
    ): array {
        if (! is_string($value)) {
            return [];
        }

        $decoded = json_decode(
            $value,
            true
        );

        return is_array($decoded)
            ? $decoded
            : [];
    }

    private function orderItemsTotal(
        int $orderId
    ): float {
        if (
            ! Schema::hasTable(
                'order_items'
            )
        ) {
            return 0;
        }

        return (float) DB::table(
            'order_items'
        )
            ->where(
                'order_id',
                $orderId
            )
            ->sum('total_ammount');
    }

    private function paidAmount(
        object $order
    ): float {
        if (
            isset($order->paid_amount)
        ) {
            return (float) $order->paid_amount;
        }

        return (
            $order->payment_status
            ?? null
        ) === 'paid'
            ? (float) (
                $order->grand_total
                ?? 0
            )
            : 0.0;
    }

    private function remainingAmount(
        object $order
    ): float {
        if (
            isset($order->remaining_amount)
        ) {
            return (float) $order
                ->remaining_amount;
        }

        return max(
            0,
            (float) (
                $order->grand_total
                ?? 0
            )
            - $this->paidAmount($order)
        );
    }

    private function label(
        string $value
    ): string {
        return collect(
            explode(
                ' ',
                str_replace(
                    ['_', '-'],
                    ' ',
                    strtolower($value)
                )
            )
        )
            ->filter()
            ->map(
                fn (string $word): string =>
                    ucfirst($word)
            )
            ->implode(' ');
    }
}