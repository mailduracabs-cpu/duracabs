<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SelfDriveVendorPayoutItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'self_drive_vendor_payout_id',
        'self_drive_booking_id',
        'vehicle_id',

        'start_datetime',
        'end_datetime',

        'booked_hours',
        'booking_units',

        'customer_daily_rate',
        'commission_percentage',
        'vendor_rate_per_24h',

        'customer_booking_amount',
        'payout_amount',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',

        'booked_hours' => 'integer',
        'booking_units' => 'integer',

        'customer_daily_rate' => 'decimal:2',
        'commission_percentage' => 'decimal:2',
        'vendor_rate_per_24h' => 'decimal:2',

        'customer_booking_amount' => 'decimal:2',
        'payout_amount' => 'decimal:2',
    ];

    public function payout(): BelongsTo
    {
        return $this->belongsTo(
            SelfDriveVendorPayout::class,
            'self_drive_vendor_payout_id'
        );
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(
            SelfDriveBooking::class,
            'self_drive_booking_id'
        );
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(
            Vehicle::class,
            'vehicle_id'
        );
    }

    public static function calculateBookingUnits(
        SelfDriveBooking $booking
    ): int {
        $hours = max(
            1,
            (int) ($booking->booked_hours ?? 0)
        );

        return max(
            1,
            (int) ceil($hours / 24)
        );
    }

    public static function calculateVendorRatePer24h(
        float $dailyPrice,
        float $commissionPercentage
    ): float {
        $dailyPrice = max(0, $dailyPrice);

        $commissionPercentage = min(
            100,
            max(0, $commissionPercentage)
        );

        return round(
            $dailyPrice * (
                1 - ($commissionPercentage / 100)
            ),
            2
        );
    }

    public static function calculatePayoutAmount(
        int $bookingUnits,
        float $vendorRatePer24h
    ): float {
        return round(
            max(1, $bookingUnits)
            * max(0, $vendorRatePer24h),
            2
        );
    }
}