<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

/** Exact money arithmetic. Round once, at the final booking amount. */
class PartnerRentalPayoutCalculator
{
    public static function fixed(string $ratePer24h, int $durationSeconds): string
    {
        if ($durationSeconds <= 0 || $durationSeconds > 366 * 86400) {
            throw ValidationException::withMessages([
                'payout' => 'A positive booking duration of up to 366 days is required.',
            ]);
        }
        $rate = PartnerPayoutPaymentService::cents($ratePer24h);
        if ($rate <= 0 || $rate > 50000000) {
            throw ValidationException::withMessages([
                'payout' => 'Fixed vendor rate must be positive and at most 500000 per 24 hours.',
            ]);
        }
        // Do not round an hourly rate first: 900 / 24 * 30 = 1125.
        return self::money(self::roundedDivide($rate * $durationSeconds, 86400));
    }

    /** Validate a monthly contract amount; this is not a per-booking payout. */
    public static function monthly(string $amount): string
    {
        $paise = PartnerPayoutPaymentService::cents($amount);
        if ($paise <= 0 || $paise > 50000000) {
            throw ValidationException::withMessages([
                'partner_monthly_amount' => 'Monthly vendor amount must be positive and at most 500000.',
            ]);
        }

        return self::money($paise);
    }

    public static function percentage(string $rentalAmount, string $commissionPercentage): string
    {
        $rental = PartnerPayoutPaymentService::cents($rentalAmount);
        $commission = PartnerPayoutPaymentService::cents($commissionPercentage);
        if ($commission > 10000 || $rental > 999999999999) {
            throw ValidationException::withMessages([
                'payout' => 'Commission must be between 0 and 100 percent and rental must fit the money ledger.',
            ]);
        }
        // Rental excludes the refundable security deposit; caller supplies effective rental.
        return self::money(self::roundedDivide($rental * (10000 - $commission), 10000));
    }

    private static function roundedDivide(int $numerator, int $denominator): int
    {
        return intdiv($numerator + intdiv($denominator, 2), $denominator);
    }

    private static function money(int $paise): string
    {
        return intdiv($paise, 100) . '.' . str_pad((string) ($paise % 100), 2, '0', STR_PAD_LEFT);
    }
}
