<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use stdClass;

class CouponService
{
    private const ACTIVE_COUPONS_CACHE_KEY = 'coupons.active';
    private const ACTIVE_COUPONS_CACHE_MINUTES = 5;

    /**
     * Return coupons that are active for the current date.
     */
    public function activeCoupons(): Collection
    {
        if (!$this->couponsTableExists()) {
            return collect();
        }

        return Cache::remember(
            self::ACTIVE_COUPONS_CACHE_KEY,
            now()->addMinutes(self::ACTIVE_COUPONS_CACHE_MINUTES),
            fn (): Collection => $this->activeCouponQuery()
                ->orderByDesc('id')
                ->get()
                ->map(fn (stdClass $coupon): array => $this->formatCoupon($coupon))
                ->values()
        );
    }

    /**
     * Validate and apply a coupon to the supplied amount.
     */
    public function apply(string $code, float $amount): array
    {
        if (!$this->couponsTableExists()) {
            return $this->failure('Coupon service is not available.');
        }

        $code = trim($code);
        $amount = max(0, round($amount, 2));

        if ($code === '') {
            return $this->failure('Please enter a coupon code.');
        }

        if ($amount <= 0) {
            return $this->failure('Coupon cannot be applied to this amount.');
        }

        $coupon = $this->activeCouponQuery()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($code)])
            ->first();

        if (!$coupon) {
            return $this->failure('Invalid or expired coupon.');
        }

        /*
         * Existing project behaviour treats coupon "value" as a flat discount.
         * Keep that behaviour here to avoid changing booking totals.
         */
        $discount = min(max(0, (float) ($coupon->value ?? 0)), $amount);
        $payable = max(0, $amount - $discount);

        return [
            'status' => true,
            'message' => 'Coupon applied successfully.',
            'data' => [
                'coupon' => $this->formatCoupon($coupon),
                'amount' => round($amount, 2),
                'discount' => round($discount, 2),
                'payable_amount' => round($payable, 2),
            ],
        ];
    }

    /**
     * Clear cached coupon results after coupon create/update/delete operations.
     */
    public function clearCache(): void
    {
        Cache::forget(self::ACTIVE_COUPONS_CACHE_KEY);
    }

    private function activeCouponQuery()
    {
        $today = today()->toDateString();

        return DB::table('coupons')
            ->select([
                'id',
                'name',
                'value',
                'from_date',
                'to_date',
                'status',
            ])
            ->where('status', 'active')
            ->where(function ($query) use ($today): void {
                $query
                    ->whereNull('from_date')
                    ->orWhereDate('from_date', '<=', $today);
            })
            ->where(function ($query) use ($today): void {
                $query
                    ->whereNull('to_date')
                    ->orWhereDate('to_date', '>=', $today);
            });
    }

    private function couponsTableExists(): bool
    {
        return Cache::remember(
            'schema.table_exists.coupons',
            now()->addMinutes(10),
            fn (): bool => Schema::hasTable('coupons')
        );
    }

    private function failure(string $message): array
    {
        return [
            'status' => false,
            'message' => $message,
        ];
    }

    private function formatCoupon(stdClass $coupon): array
    {
        return [
            'id' => (int) $coupon->id,
            'name' => (string) $coupon->name,
            'value' => round((float) ($coupon->value ?? 0), 2),
            'from_date' => $coupon->from_date,
            'to_date' => $coupon->to_date,
            'status' => (string) $coupon->status,
        ];
    }
}
