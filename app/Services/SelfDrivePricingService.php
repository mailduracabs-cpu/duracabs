<?php

namespace App\Services;

use App\Models\Vehicle;
use Carbon\Carbon;
use InvalidArgumentException;

class SelfDrivePricingService
{
    private const WEEKLY_DISCOUNT_PERCENT = 20.0;
    private const MONTHLY_DISCOUNT_PERCENT = 30.0;

    public function calculate(
        Vehicle $vehicle,
        Carbon|string $start,
        Carbon|string $end,
        array $options = []
    ): array {
        $startAt = $start instanceof Carbon ? $start->copy() : Carbon::parse($start);
        $endAt = $end instanceof Carbon ? $end->copy() : Carbon::parse($end);

        if ($endAt->lte($startAt)) {
            throw new InvalidArgumentException('End time must be after start time.');
        }

        $selectedHours = max(1, (int) ceil($startAt->diffInMinutes($endAt) / 60));
        $minimumHours = max(1, (int) ($vehicle->minimum_booking_hours ?? 1));
        $maximumHours = (int) ($vehicle->maximum_booking_hours ?? 0);

        if ($selectedHours < $minimumHours) {
            throw new InvalidArgumentException(
                "Minimum booking duration is {$minimumHours} hour(s)."
            );
        }

        if ($maximumHours > 0 && $selectedHours > $maximumHours) {
            throw new InvalidArgumentException(
                "Maximum booking duration is {$maximumHours} hour(s)."
            );
        }

        $chargeableHours = max($selectedHours, $minimumHours);
        $mode = $this->resolveMode($options, $chargeableHours);
        $rate = $this->resolveRate($vehicle, $mode);

        $baseAmount = $this->baseAmount(
            vehicle: $vehicle,
            mode: $mode,
            rate: $rate,
            chargeableHours: $chargeableHours
        );

        $weeklyDiscountPercent = $this->money(
            $options['weekly_discount_percent']
                ?? self::WEEKLY_DISCOUNT_PERCENT
        );
        $monthlyDiscountPercent = $this->money(
            $options['monthly_discount_percent']
                ?? self::MONTHLY_DISCOUNT_PERCENT
        );

        $discountPercent = match ($mode) {
            'weekly' => min(100, $weeklyDiscountPercent),
            'monthly' => min(100, $monthlyDiscountPercent),
            default => 0.0,
        };

        $discountAmount = round($baseAmount * ($discountPercent / 100), 2);
        $rentAfterDiscount = max(0, round($baseAmount - $discountAmount, 2));

        $extras = $this->extras($options);
        $extrasTotal = round(array_sum($extras), 2);

        $couponDiscount = min(
            $this->money($options['coupon_discount'] ?? 0),
            $rentAfterDiscount + $extrasTotal
        );

        $taxableAmount = max(
            0,
            round($rentAfterDiscount + $extrasTotal - $couponDiscount, 2)
        );

        $gstPercent = min(100, $this->money($options['gst_percent'] ?? 0));
        $gstAmount = round($taxableAmount * ($gstPercent / 100), 2);

        $securityDeposit = $this->money(
            $options['security_deposit']
                ?? $vehicle->security_deposit
                ?? 0
        );

        $payableAmount = round(
            $taxableAmount + $gstAmount + $securityDeposit,
            2
        );

        return [
            'mode' => $mode,
            'start_datetime' => $startAt->toDateTimeString(),
            'end_datetime' => $endAt->toDateTimeString(),
            'selected_hours' => $selectedHours,
            'chargeable_hours' => $chargeableHours,
            'minimum_hours' => $minimumHours,
            'maximum_hours' => $maximumHours > 0 ? $maximumHours : null,
            'total_days' => max(1, (int) ceil($chargeableHours / 24)),
            'total_weeks' => max(1, (int) ceil($chargeableHours / 168)),
            'total_months' => max(1, (int) ceil($chargeableHours / 720)),
            'rate' => $rate,
            'hourly_price' => $this->hourlyRate($vehicle),
            'daily_price' => $this->dailyRate($vehicle),
            'weekly_price' => $this->weeklyRate($vehicle),
            'monthly_price' => $this->monthlyRate($vehicle),
            'price_per_day' => $this->dailyRate($vehicle),
            'base_amount' => $baseAmount,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount,
            'rent' => $rentAfterDiscount,
            'extras' => $extras,
            'extras_total' => $extrasTotal,
            'coupon_discount' => $couponDiscount,
            'taxable_amount' => $taxableAmount,
            'gst_percent' => $gstPercent,
            'gst_amount' => $gstAmount,
            'security_deposit' => $securityDeposit,
            'payable_amount' => $payableAmount,
        ];
    }

    private function resolveMode(array $options, int $chargeableHours): string
    {
        $requested = strtolower(trim((string) (
            $options['rental_mode']
                ?? $options['plan_type']
                ?? $options['mode']
                ?? ''
        )));

        if (in_array($requested, ['hourly', 'daily', 'weekly', 'monthly'], true)) {
            return $requested;
        }

        return match (true) {
            $chargeableHours >= 720 => 'monthly',
            $chargeableHours >= 168 => 'weekly',
            $chargeableHours >= 24 => 'daily',
            default => 'hourly',
        };
    }

    private function resolveRate(Vehicle $vehicle, string $mode): float
    {
        $rate = match ($mode) {
            'monthly' => $this->monthlyRate($vehicle),
            'weekly' => $this->weeklyRate($vehicle),
            'daily' => $this->dailyRate($vehicle),
            default => $this->hourlyRate($vehicle),
        };

        if ($rate <= 0) {
            throw new InvalidArgumentException(
                ucfirst($mode) . ' price is not configured for this vehicle.'
            );
        }

        return $rate;
    }

    private function baseAmount(
        Vehicle $vehicle,
        string $mode,
        float $rate,
        int $chargeableHours
    ): float {
        return match ($mode) {
            'monthly' => round(max(1, (int) ceil($chargeableHours / 720)) * $rate, 2),
            'weekly' => round(max(1, (int) ceil($chargeableHours / 168)) * $rate, 2),
            'daily' => round(max(1, (int) ceil($chargeableHours / 24)) * $rate, 2),
            default => round($chargeableHours * $rate, 2),
        };
    }

    private function hourlyRate(Vehicle $vehicle): float
    {
        $hourly = $this->money($vehicle->hourly_price ?? 0);

        if ($hourly > 0) {
            return $hourly;
        }

        $daily = $this->money($vehicle->daily_price ?? 0);

        return $daily > 0 ? round($daily / 24, 2) : 0.0;
    }

    private function dailyRate(Vehicle $vehicle): float
    {
        $daily = $this->money($vehicle->daily_price ?? 0);

        if ($daily > 0) {
            return $daily;
        }

        $hourly = $this->hourlyRate($vehicle);

        return $hourly > 0 ? round($hourly * 24, 2) : 0.0;
    }

    private function weeklyRate(Vehicle $vehicle): float
    {
        $weekly = $this->money($vehicle->weekly_price ?? 0);

        if ($weekly > 0) {
            return $weekly;
        }

        $daily = $this->dailyRate($vehicle);

        return $daily > 0 ? round($daily * 7, 2) : 0.0;
    }

    private function monthlyRate(Vehicle $vehicle): float
    {
        $monthly = $this->money($vehicle->monthly_price ?? 0);

        if ($monthly > 0) {
            return $monthly;
        }

        $daily = $this->dailyRate($vehicle);

        return $daily > 0 ? round($daily * 30, 2) : 0.0;
    }

    private function extras(array $options): array
    {
        return [
            'special_request_total' => $this->money(
                $options['special_request_total'] ?? 0
            ),
            'extra_service_amount' => $this->money(
                $options['extra_service_amount'] ?? 0
            ),
            'toll_amount' => $this->money($options['toll_amount'] ?? 0),
            'parking_amount' => $this->money($options['parking_amount'] ?? 0),
            'government_tax_amount' => $this->money(
                $options['government_tax_amount'] ?? 0
            ),
            'permit_tax_amount' => $this->money(
                $options['permit_tax_amount'] ?? 0
            ),
        ];
    }

    private function money(mixed $value): float
    {
        return round(max(0, (float) $value), 2);
    }
}
