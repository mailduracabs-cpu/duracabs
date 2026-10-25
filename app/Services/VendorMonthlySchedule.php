<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;

class VendorMonthlySchedule
{
    /** Period end is inclusive. Next billing date is the next period's first day. */
    public static function forDate(string $joiningDate, ?string $asOf = null): array
    {
        $asOf ??= VendorLifecycleClock::today()->toDateString();
        Validator::make(
            ['partner_joining_date' => $joiningDate, 'as_of' => $asOf],
            ['partner_joining_date' => ['required', 'date_format:Y-m-d'], 'as_of' => ['required', 'date_format:Y-m-d']]
        )->validate();

        $anchor = VendorLifecycleClock::date($joiningDate);
        $date = VendorLifecycleClock::date($asOf);
        $months = max(0, ($date->year - $anchor->year) * 12 + $date->month - $anchor->month);
        $start = $anchor->addMonthsNoOverflow($months);
        if ($months > 0 && $start->gt($date)) {
            $months--;
            $start = $anchor->addMonthsNoOverflow($months);
        }
        // Always calculate from the original day: Jan 31 -> Feb 28 -> Mar 31.
        $next = $anchor->addMonthsNoOverflow($months + 1);

        return [
            'joining_date' => $anchor->toDateString(),
            'period_start' => $start->toDateString(),
            'period_end' => $next->subDay()->toDateString(),
            'next_billing_date' => $next->toDateString(),
            'started' => $date->gte($anchor),
        ];
    }
    public static function forBillingDate(string $joiningDate, string $basis, int $day = 15, ?string $asOf = null): array
    {
        $anniversary = self::forDate($joiningDate, $asOf);
        if ($basis === 'joining_date') return $anniversary;
        if ($basis !== 'fixed_day' || $day < 1 || $day > 28) {
            throw \Illuminate\Validation\ValidationException::withMessages(['billing' => 'Choose a valid billing basis and day.']);
        }
        $joining = VendorLifecycleClock::date($joiningDate);
        $today = VendorLifecycleClock::date($asOf ?? VendorLifecycleClock::today()->toDateString());
        $reference = $today->max($joining);
        $nominal = $reference->startOfMonth()->day($day);
        if ($nominal->gt($reference)) $nominal = $nominal->subMonthNoOverflow();
        $next = $nominal->addMonthNoOverflow();
        return [
            'joining_date' => $joining->toDateString(), 'period_start' => $nominal->max($joining)->toDateString(),
            'period_end' => $next->subDay()->toDateString(), 'next_billing_date' => $next->toDateString(),
            'started' => $today->gte($joining),
        ];
    }
}
