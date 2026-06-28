<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class CouponService
{
    public function activeCoupons()
    {
        if (!Schema::hasTable('coupons')) {
            return [];
        }

        $today = Carbon::today()->toDateString();

        return DB::table('coupons')
            ->where('status', 'active')
            ->where(function ($query) use ($today) {
                $query->whereNull('from_date')->orWhere('from_date', '<=', $today);
            })
            ->where(function ($query) use ($today) {
                $query->whereNull('to_date')->orWhere('to_date', '>=', $today);
            })
            ->orderByDesc('id')
            ->get()
            ->map(fn ($coupon) => $this->formatCoupon($coupon));
    }

    public function apply(string $code, float $amount): array
    {
        if (!Schema::hasTable('coupons')) {
            return [
                'status' => false,
                'message' => 'Coupon service is not available.',
            ];
        }

        $today = Carbon::today()->toDateString();

        $coupon = DB::table('coupons')
            ->where('name', $code)
            ->where('status', 'active')
            ->where(function ($query) use ($today) {
                $query->whereNull('from_date')->orWhere('from_date', '<=', $today);
            })
            ->where(function ($query) use ($today) {
                $query->whereNull('to_date')->orWhere('to_date', '>=', $today);
            })
            ->first();

        if (!$coupon) {
            return [
                'status' => false,
                'message' => 'Invalid or expired coupon.',
            ];
        }

        $discount = min((float) ($coupon->value ?? 0), $amount);
        $payable = max(0, $amount - $discount);

        return [
            'status' => true,
            'message' => 'Coupon applied successfully',
            'data' => [
                'coupon' => $this->formatCoupon($coupon),
                'amount' => round($amount, 2),
                'discount' => round($discount, 2),
                'payable_amount' => round($payable, 2),
            ],
        ];
    }

    private function formatCoupon($coupon): array
    {
        return [
            'id' => $coupon->id,
            'name' => $coupon->name,
            'value' => (float) ($coupon->value ?? 0),
            'from_date' => $coupon->from_date,
            'to_date' => $coupon->to_date,
            'status' => $coupon->status,
        ];
    }
}
