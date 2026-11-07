<?php

declare(strict_types=1);
namespace App\Services;

final class LiveBookingIntakeTiming
{
    public static function reason(int $timestamp, int $now): ?string
    {
        if($timestamp<=0)return 'invalid_message_timestamp';
        if($timestamp>$now+30)return 'message_time_in_future';
        if($timestamp<$now-300)return 'message_time_too_old';
        return null;
    }
}
