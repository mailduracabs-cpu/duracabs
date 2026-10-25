<?php

namespace App\Services;

use Carbon\CarbonImmutable;

/** Date-only agreement rules use the business timezone, independent of server/cron timezone. */
class VendorLifecycleClock
{
    public static function today(): CarbonImmutable
    {
        return CarbonImmutable::today((string) config('vendor_lifecycle.timezone', 'Asia/Kolkata'));
    }

    public static function date(string $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date, (string) config('vendor_lifecycle.timezone', 'Asia/Kolkata'))->startOfDay();
    }
}
