<?php

declare(strict_types=1);
namespace App\Services;

use Carbon\Carbon;

final class LiveBookingPickupTime
{
    /** Explicit dates are never moved. Undated clocks use their next Indian local occurrence. */
    public static function resolve(Carbon $candidate, Carbon $receipt, bool $hasExplicitDate, bool $hasExplicitClock): Carbon
    {
        $candidate=$candidate->copy()->timezone('Asia/Kolkata');
        $receipt=$receipt->copy()->timezone('Asia/Kolkata');
        if(!$hasExplicitDate&&$hasExplicitClock){
            $candidate=$receipt->copy()->setTime((int)$candidate->format('H'),(int)$candidate->format('i'),0);
            if($candidate->lt($receipt->copy()->startOfMinute()))$candidate->addDay();
        }
        return $candidate;
    }
}
