<?php

namespace App\Services;

use App\Models\SelfDriveBooking;
use App\Models\SelfDriveVendorPayout;
use App\Models\SelfDriveVendorPayoutItem;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SelfDriveVendorPayoutService
{
    /**
     * Vendor के eligible completed bookings निकालें.
     */
    public function eligibleBookings(
        int $transporterProfileId,
        Carbon|string $from,
        Carbon|string $to
    ): Collection {
        $from = Carbon::parse($from)->startOfDay();
        $to = Carbon::parse($to)->endOfDay();

        return SelfDriveBooking::query()
            ->with([
                'vehicle',
                'customer',
            ])
            ->where(
                'transporter_profile_id',
                $transporterProfileId
            )

            /*
             * केवल completed booking का vendor payout बनेगा.
             */
            ->where(function ($query) {
                $query
                    ->where(
                        'status',
                        SelfDriveBooking::STATUS_COMPLETED
                    )
                    ->orWhere(
                        'booking_status',
                        SelfDriveBooking::STATUS_COMPLETED
                    );
            })

            /*
             * जिस booking का payout पहले बन चुका है,
             * उसे दोबारा payout में शामिल न करें.
             */
            ->whereDoesntHave('vendorPayoutItem')

            /*
             * Period booking start date के आधार पर.
             */
            ->whereBetween(
                'start_datetime',
                [$from, $to]
            )

            ->orderBy('start_datetime')
            ->get();
    }

    /**
     * Generate payout preview without saving.
     */
    public function preview(
        int $transporterProfileId,
        Carbon|string $from,
        Carbon|string $to
    ): array {
        $bookings = $this->eligibleBookings(
            $transporterProfileId,
            $from,
            $to
        );

        $items = [];

        foreach ($bookings as $booking) {
            $calculation = $this->calculateBookingPayout(
                $booking
            );

            if ($calculation === null) {
                continue;
            }

            $items[] = $calculation;
        }

        return [
            'transporter_profile_id' =>
                $transporterProfileId,

            'period_from' =>
                Carbon::parse($from)->toDateString(),

            'period_to' =>
                Carbon::parse($to)->toDateString(),

            'booking_count' =>
                count($items),

            'total_booking_units' =>
                collect($items)->sum('booking_units'),

            'gross_booking_amount' =>
                round(
                    (float) collect($items)
                        ->sum('customer_booking_amount'),
                    2
                ),

            'payout_amount' =>
                round(
                    (float) collect($items)
                        ->sum('payout_amount'),
                    2
                ),

            'items' => $items,
        ];
    }

    /**
     * Actual payout DB में generate करें.
     */
    public function generate(
        int $transporterProfileId,
        Carbon|string $from,
        Carbon|string $to,
        ?string $notes = null
    ): SelfDriveVendorPayout {
        $from = Carbon::parse($from)->startOfDay();
        $to = Carbon::parse($to)->endOfDay();

        if ($to->lt($from)) {
            throw ValidationException::withMessages([
                'period_to' =>
                    'Period To must be after Period From.',
            ]);
        }

        return DB::transaction(function () use (
            $transporterProfileId,
            $from,
            $to,
            $notes
        ) {
            /*
             * Lock eligible bookings during payout generation
             * so same booking concurrent requests में duplicate
             * payout में न जा सके.
             */
            $bookings = SelfDriveBooking::query()
                ->with('vehicle')
                ->where(
                    'transporter_profile_id',
                    $transporterProfileId
                )
                ->where(function ($query) {
                    $query
                        ->where(
                            'status',
                            SelfDriveBooking::STATUS_COMPLETED
                        )
                        ->orWhere(
                            'booking_status',
                            SelfDriveBooking::STATUS_COMPLETED
                        );
                })
                ->whereDoesntHave('vendorPayoutItem')
                ->whereBetween(
                    'start_datetime',
                    [$from, $to]
                )
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($bookings->isEmpty()) {
                throw ValidationException::withMessages([
                    'payout' =>
                        'No unpaid completed bookings found for this vendor and date range.',
                ]);
            }

            /*
             * पहले header create.
             */
            $payout = SelfDriveVendorPayout::create([
                'transporter_profile_id' =>
                    $transporterProfileId,

                'period_from' =>
                    $from->toDateString(),

                'period_to' =>
                    $to->toDateString(),

                'total_booking_units' => 0,
                'gross_booking_amount' => 0,
                'payout_amount' => 0,
                'paid_amount' => 0,
                'remaining_amount' => 0,
                'status' =>
                    SelfDriveVendorPayout::STATUS_DRAFT,

                'notes' => $notes,
            ]);

            foreach ($bookings as $booking) {
                $calculation =
                    $this->calculateBookingPayout(
                        $booking
                    );

                /*
                 * Vehicle missing या invalid pricing होने पर
                 * booking skip करें.
                 */
                if ($calculation === null) {
                    continue;
                }

                SelfDriveVendorPayoutItem::create([
                    'self_drive_vendor_payout_id' =>
                        $payout->id,

                    'self_drive_booking_id' =>
                        $booking->id,

                    'vehicle_id' =>
                        $booking->vehicle_id,

                    'start_datetime' =>
                        $booking->start_datetime,

                    'end_datetime' =>
                        $booking->end_datetime,

                    'booked_hours' =>
                        $calculation['booked_hours'],

                    'booking_units' =>
                        $calculation['booking_units'],

                    'customer_daily_rate' =>
                        $calculation['customer_daily_rate'],

                    'commission_percentage' =>
                        $calculation['commission_percentage'],

                    'vendor_rate_per_24h' =>
                        $calculation['vendor_rate_per_24h'],

                    'customer_booking_amount' =>
                        $calculation['customer_booking_amount'],

                    'payout_amount' =>
                        $calculation['payout_amount'],
                ]);
            }

            if (! $payout->items()->exists()) {
                /*
                 * अगर किसी booking से valid payout item नहीं बना,
                 * empty payout header भी नहीं रखना.
                 */
                $payout->delete();

                throw ValidationException::withMessages([
                    'payout' =>
                        'Eligible bookings found, but payout could not be calculated.',
                ]);
            }

            /*
             * Item totals header में sync करें.
             */
            $payout->refreshTotals();

            return $payout->fresh([
                'transporter',
                'items.vehicle',
                'items.booking',
            ]);
        });
    }

    /**
     * Single booking payout calculation.
     */
    public function calculateBookingPayout(
        SelfDriveBooking $booking
    ): ?array {
        $booking->loadMissing('vehicle');

        $vehicle = $booking->vehicle;

        if (! $vehicle) {
            return null;
        }

        /*
         * 24H payout units.
         *
         * Prefer booked_hours क्योंकि यही exact duration है.
         * SelfDriveBooking already total_days =
         * CEIL(booked_hours / 24) calculate करता है.
         */
        $bookedHours = max(
            1,
            (int) ($booking->booked_hours ?? 0)
        );

        $bookingUnits = max(
            1,
            (int) ceil($bookedHours / 24)
        );

        /*
         * Booking-time customer daily rate.
         *
         * price_per_day booking में vehicle daily price
         * snapshot है, इसलिए current vehicle daily price
         * बदलने पर old payout प्रभावित नहीं होगा.
         */
        $customerDailyRate = max(
            0,
            (float) (
                $booking->price_per_day
                ?: $vehicle->daily_price
                ?: 0
            )
        );

        /*
         * Existing vehicle commission percentage.
         */
        $commissionPercentage = min(
            100,
            max(
                0,
                (float) (
                    $vehicle->commission_percentage
                    ?? 0
                )
            )
        );

        /*
         * Example:
         *
         * Customer 24H price = ₹2000
         * Commission = 30%
         *
         * Vendor 24H = ₹1400
         */
        $vendorRatePer24h =
            SelfDriveVendorPayoutItem
                ::calculateVendorRatePer24h(
                    $customerDailyRate,
                    $commissionPercentage
                );

        /*
         * Example:
         *
         * 49 hours = 3 payout units
         * ₹1400 × 3 = ₹4200 vendor payout.
         */
        $payoutAmount =
            SelfDriveVendorPayoutItem
                ::calculatePayoutAmount(
                    $bookingUnits,
                    $vendorRatePer24h
                );

        return [
            'booking_id' =>
                $booking->id,

            'booking_no' =>
                $booking->booking_no,

            'vehicle_id' =>
                $booking->vehicle_id,

            'vehicle_name' =>
                trim(
                    (string) (
                        $vehicle->car_company_name
                        ?? ''
                    )
                    . ' '
                    . (string) (
                        $vehicle->model_name
                        ?? ''
                    )
                ),

            'registration_number' =>
                $vehicle->registration_number
                ?? null,

            'start_datetime' =>
                $booking->start_datetime,

            'end_datetime' =>
                $booking->end_datetime,

            'booked_hours' =>
                $bookedHours,

            'booking_units' =>
                $bookingUnits,

            'customer_daily_rate' =>
                round($customerDailyRate, 2),

            'commission_percentage' =>
                round($commissionPercentage, 2),

            'vendor_rate_per_24h' =>
                $vendorRatePer24h,

            /*
             * केवल reporting के लिए.
             * Vendor payout इससे calculate नहीं होगा.
             */
            'customer_booking_amount' =>
                round(
                    $booking->effectiveRentalAmount(),
                    2
                ),

            'payout_amount' =>
                $payoutAmount,
        ];
    }

    /**
     * Vehicle-wise grouped summary.
     */
    public function vehicleSummary(
        SelfDriveVendorPayout $payout
    ): array {
        $payout->loadMissing([
            'items.vehicle',
            'items.booking',
        ]);

        return $payout->items
            ->groupBy('vehicle_id')
            ->map(function ($items) {
                $vehicle = $items->first()?->vehicle;

                return [
                    'vehicle_id' =>
                        $vehicle?->id,

                    'vehicle_name' =>
                        trim(
                            (string) (
                                $vehicle?->car_company_name
                                ?? ''
                            )
                            . ' '
                            . (string) (
                                $vehicle?->model_name
                                ?? ''
                            )
                        ),

                    'registration_number' =>
                        $vehicle?->registration_number,

                    /*
                     * Actual number of customer booking records.
                     */
                    'booking_count' =>
                        $items->count(),

                    /*
                     * 24-hour payout units.
                     *
                     * Example:
                     * booking 1 = 24h => 1
                     * booking 2 = 48h => 2
                     *
                     * Booking Count = 2
                     * Booking Units = 3
                     */
                    'booking_units' =>
                        (int) $items->sum(
                            'booking_units'
                        ),

                    'booked_hours' =>
                        (int) $items->sum(
                            'booked_hours'
                        ),

                    'customer_booking_amount' =>
                        round(
                            (float) $items->sum(
                                'customer_booking_amount'
                            ),
                            2
                        ),

                    'vendor_payout' =>
                        round(
                            (float) $items->sum(
                                'payout_amount'
                            ),
                            2
                        ),
                ];
            })
            ->values()
            ->all();
    }
}