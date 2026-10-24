<?php

namespace App\Services;

use App\Models\Coupons;
use InvalidArgumentException;

/** Same active/date/percentage rules used by the website and one-way API. */
final class CustomerCouponDiscount
{
    public static function calculate(string $code, float $amount): array
    {
        $code = trim($code);
        if ($code === '') return ['code' => '', 'percentage' => 0.0, 'discount' => 0.0];
        $coupon = Coupons::query()->select(['name', 'value'])
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($code)])
            ->where('status', 'active')->whereDate('from_date', '<=', today())
            ->whereDate('to_date', '>=', today())->first();
        if (! $coupon) throw new InvalidArgumentException('Coupon is invalid or expired.');
        $percentage = max(0, min(100, (float) $coupon->value));
        return ['code' => (string) $coupon->name, 'percentage' => $percentage,
            'discount' => round(max(0, $amount) * $percentage / 100, 2)];
    }
}
