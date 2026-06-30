<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BookingService
{
    public function create(array $data): array
    {
        if (!Schema::hasTable('orders')) {
            return [
                'status' => false,
                'message' => 'Orders table not found.',
                'code' => 500,
            ];
        }

        $mobile = $data['mobile'] ?? null;

        $lockKey = 'booking_lock_' . md5(json_encode([
            'mobile' => $mobile,
            'product_id' => $data['product_id'] ?? null,
            'vehicle_id' => $data['vehicle_id'] ?? null,
            'pickup_date' => $data['pickup_date'] ?? null,
            'pickup_time' => $data['pickup_time'] ?? null,
            'pickup_address' => $data['pickup_address'] ?? null,
            'drop_address' => $data['drop_address'] ?? null,
            'ride_type' => $data['ride_type'] ?? null,
        ]));

        if (!Cache::add($lockKey, true, now()->addSeconds(60))) {
            return [
                'status' => false,
                'message' => 'Duplicate booking request detected. Please wait.',
                'code' => 429,
            ];
        }

        try {
            return DB::transaction(function () use ($data, $lockKey) {
                $userId = $this->findOrCreateUser($data);
                $product = $this->getProduct($data['product_id'] ?? null);
                $category = $this->getCategory($data['category_id'] ?? ($product->category_id ?? null));

                $amount = $this->resolveSafeAmount($data, $product);

                if ($amount <= 0) {
                    Cache::forget($lockKey);

                    return [
                        'status' => false,
                        'message' => 'Invalid booking amount. Please select a valid route or vehicle.',
                        'code' => 422,
                    ];
                }

                $couponValue = $this->resolveCouponValue($data, $amount);
                $tax = $this->resolveTax($data, $amount, $couponValue);
                $grandTotal = max(0, round($amount + $tax - $couponValue, 2));

                if ($grandTotal <= 0) {
                    Cache::forget($lockKey);

                    return [
                        'status' => false,
                        'message' => 'Invalid final amount. Booking cannot be created with zero amount.',
                        'code' => 422,
                    ];
                }

                $paymentMethod = $data['payment_method'] ?? 'cash';
                $isOnline = in_array(strtolower($paymentMethod), ['online', 'razorpay', 'razorpay_payment', 'card', 'upi'], true);

                $orderStatus = $isOnline ? 'pending_payment' : 'confirmed';
                $paymentStatus = $isOnline ? 'pending' : ($data['payment_status'] ?? 'pending');

                $recentDuplicate = $this->findRecentDuplicate($data);

                if ($recentDuplicate) {
                    Cache::forget($lockKey);

                    return [
                        'status' => true,
                        'message' => 'Booking already submitted.',
                        'data' => $this->detail($recentDuplicate->id)['data'],
                    ];
                }

                $orderId = DB::table('orders')->insertGetId([
                    'user_id' => $userId,
                    'vehicle_id' => $data['vehicle_id'] ?? null,
                    'grand_total' => $grandTotal,
                    'coupon_value' => $couponValue,
                    'coupon_name' => $data['coupon_name'] ?? null,
                    'tax' => $tax,
                    'payment_method' => $paymentMethod,
                    'payment_status' => $paymentStatus,
                    'total_km' => $data['total_km'] ?? ($product->km_limit ?? null),
                    'date' => $data['pickup_date'] ?? null,
                    'dateTo' => $data['return_date'] ?? null,
                    'time' => $data['pickup_time'] ?? null,
                    'endTime' => $data['return_time'] ?? null,
                    'booking_from' => $data['pickup_address'] ?? null,
                    'booking_to' => $data['drop_address'] ?? null,
                    'status' => $orderStatus,
                    'plan' => $this->validPlan($data['plan'] ?? ($product->plan ?? 'none')),
                    'currency' => 'INR',
                    'extraOptions' => isset($data['extra_options']) ? json_encode($data['extra_options']) : null,
                    'cityFrom' => $data['pickup_city'] ?? null,
                    'cityTo' => $data['drop_city'] ?? null,
                    'productName' => $product->name ?? ($category->name ?? ($data['taxi_type'] ?? 'Taxi Booking')),
                    'image' => $this->resolveImage($product),
                    'shipping_ammount' => 0,
                    'taxi_type' => $data['taxi_type'] ?? ($category->name ?? null),
                    'notes' => $data['notes'] ?? 'Booking created from Dura Cabs mobile app.',
                    'ride_type' => $data['ride_type'] ?? ($product->ride_type ?? 'one_way'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if (Schema::hasTable('order_items')) {
                    DB::table('order_items')->insert([
                        'order_id' => $orderId,
                        'product_id' => $product->id ?? ($data['product_id'] ?? 0),
                        'quantity' => 1,
                        'unit_ammount' => $amount,
                        'total_ammount' => $amount,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                if (Schema::hasTable('addresses')) {
                    DB::table('addresses')->insert([
                        'order_id' => $orderId,
                        'user_id' => $userId,
                        'email' => $data['email'] ?? null,
                        'full_name' => $data['name'] ?? 'Dura Cabs Customer',
                        'phone' => $data['mobile'],
                        'state' => $data['state'] ?? null,
                        'city' => $data['pickup_city'] ?? null,
                        'pickup_address' => $data['pickup_address'] ?? null,
                        'drop_address' => $data['drop_address'] ?? null,
                        'number_travellers' => $data['number_travellers'] ?? null,
                        'number_luggage' => $data['number_luggage'] ?? null,
                        'comments' => $data['notes'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                return [
                    'status' => true,
                    'message' => $isOnline
                        ? 'Booking created. Please complete payment.'
                        : 'Booking created successfully',
                    'data' => $this->detail($orderId)['data'],
                ];
            });
        } catch (\Throwable $e) {
            Cache::forget($lockKey);

            Log::error('V1 Booking Create Error', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return [
                'status' => false,
                'message' => 'Unable to create booking. Please try again.',
                'code' => 500,
                'errors' => config('app.debug') ? $e->getMessage() : null,
            ];
        }
    }

    public function myBookings(?string $mobile = null, ?int $userId = null, int $limit = 20)
    {
        if (!Schema::hasTable('orders')) {
            return [];
        }

        $query = DB::table('orders')->orderByDesc('id');

        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($mobile && Schema::hasTable('addresses')) {
            $orderIds = DB::table('addresses')->where('phone', $mobile)->pluck('order_id')->filter()->toArray();

            $query->where(function ($q) use ($mobile, $orderIds) {
                if (!empty($orderIds)) {
                    $q->whereIn('id', $orderIds);
                }

                $q->orWhere('booking_from', 'like', '%' . $mobile . '%');
            });
        }

        return $query->limit(min(max($limit, 1), 100))->get()->map(fn ($order) => $this->formatOrder($order));
    }

    public function detail($bookingId): array
    {
        if (!Schema::hasTable('orders')) {
            return [
                'status' => false,
                'message' => 'Orders table not found.',
            ];
        }

        $order = DB::table('orders')->where('id', $bookingId)->first();

        if (!$order) {
            return [
                'status' => false,
                'message' => 'Booking not found.',
            ];
        }

        $items = Schema::hasTable('order_items')
            ? DB::table('order_items')->where('order_id', $bookingId)->get()
            : collect();

        $address = Schema::hasTable('addresses')
            ? DB::table('addresses')->where('order_id', $bookingId)->first()
            : null;

        return [
            'status' => true,
            'data' => [
                'booking' => $this->formatOrder($order),
                'items' => $items,
                'address' => $address,
            ],
        ];
    }

    public function cancel(array $data): array
    {
        if (!Schema::hasTable('orders')) {
            return [
                'status' => false,
                'message' => 'Orders table not found.',
                'code' => 500,
            ];
        }

        $order = DB::table('orders')->where('id', $data['booking_id'])->first();

        if (!$order) {
            return [
                'status' => false,
                'message' => 'Booking not found.',
                'code' => 404,
            ];
        }

        if (in_array($order->status, ['cancelled', 'closed'], true)) {
            return [
                'status' => false,
                'message' => 'Booking cannot be cancelled.',
                'code' => 422,
            ];
        }

        DB::table('orders')->where('id', $data['booking_id'])->update([
            'status' => 'cancelled',
            'notes' => trim(($order->notes ?? '') . "\nCancel reason: " . ($data['reason'] ?? 'Cancelled by customer')),
            'updated_at' => now(),
        ]);

        return [
            'status' => true,
            'message' => 'Booking cancelled successfully',
            'data' => $this->detail($data['booking_id'])['data'],
        ];
    }

    private function findRecentDuplicate(array $data)
    {
        if (!Schema::hasTable('addresses')) {
            return null;
        }

        $mobile = $data['mobile'] ?? null;

        if (!$mobile) {
            return null;
        }

        $orderIds = DB::table('addresses')
            ->where('phone', $mobile)
            ->where('created_at', '>=', now()->subSeconds(60))
            ->pluck('order_id')
            ->filter()
            ->toArray();

        if (empty($orderIds)) {
            return null;
        }

        return DB::table('orders')
            ->whereIn('id', $orderIds)
            ->where('date', $data['pickup_date'] ?? null)
            ->where('time', $data['pickup_time'] ?? null)
            ->where('ride_type', $data['ride_type'] ?? null)
            ->orderByDesc('id')
            ->first();
    }

    private function findOrCreateUser(array $data): ?int
    {
        if (!Schema::hasTable('users')) {
            return $data['user_id'] ?? null;
        }

        if (!empty($data['user_id'])) {
            return (int) $data['user_id'];
        }

        $mobile = $data['mobile'];
        $user = DB::table('users')->where('mobile', $mobile)->first();

        if ($user) {
            return $user->id;
        }

        return DB::table('users')->insertGetId([
            'name' => $data['name'] ?? 'Dura Cabs Customer',
            'email' => $data['email'] ?? ('customer' . $mobile . '@duracabs.app'),
            'mobile' => $mobile,
            'password' => Hash::make(Str::random(16)),
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function getProduct($productId)
    {
        if (!$productId || !Schema::hasTable('products')) {
            return null;
        }

        return DB::table('products')->where('id', $productId)->where('is_active', 1)->first()
            ?? DB::table('products')->where('id', $productId)->first();
    }

    private function getCategory($categoryId)
    {
        if (!$categoryId || !Schema::hasTable('categories')) {
            return null;
        }

        return DB::table('categories')->where('id', $categoryId)->where('is_active', 1)->first()
            ?? DB::table('categories')->where('id', $categoryId)->first();
    }

    private function resolveSafeAmount(array $data, $product): float
    {
        $serverAmount = (float) ($product->price ?? 0);

        if ($serverAmount > 0) {
            return round($serverAmount, 2);
        }

        $fallbackAmount = (float) ($data['amount'] ?? $data['grand_total'] ?? 0);

        return $fallbackAmount > 0 ? round($fallbackAmount, 2) : 0;
    }

    private function resolveCouponValue(array $data, float $amount): float
    {
        $couponValue = (float) ($data['coupon_value'] ?? 0);

        if ($couponValue < 0) {
            return 0;
        }

        if ($couponValue > $amount) {
            return 0;
        }

        return round($couponValue, 2);
    }

    private function resolveTax(array $data, float $amount, float $couponValue): float
    {
        if (isset($data['tax'])) {
            $tax = (float) $data['tax'];

            return $tax >= 0 ? round($tax, 2) : 0;
        }

        $taxableAmount = max(0, $amount - $couponValue);

        return round(($taxableAmount * 5) / 100, 2);
    }

    private function resolveImage($product): ?string
    {
        if (!$product || empty($product->images)) {
            return null;
        }

        $decoded = json_decode($product->images, true);

        return is_array($decoded) && isset($decoded[0]) ? $decoded[0] : null;
    }

    private function validPlan(?string $plan): string
    {
        $allowed = ['none', '4 Hour / 40 Km', '8 Hour / 80 Km', '12 Hour / 120 Km'];

        return in_array($plan, $allowed, true) ? $plan : 'none';
    }

    private function formatOrder($order): array
    {
        return [
            'id' => $order->id,
            'booking_id' => 'DC' . str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
            'user_id' => $order->user_id,
            'status' => $order->status,
            'ride_type' => $order->ride_type,
            'taxi_type' => $order->taxi_type,
            'product_name' => $order->productName,
            'pickup' => $order->booking_from,
            'drop' => $order->booking_to,
            'pickup_city' => $order->cityFrom,
            'drop_city' => $order->cityTo,
            'date' => $order->date,
            'return_date' => $order->dateTo,
            'time' => $order->time,
            'return_time' => $order->endTime,
            'grand_total' => (float) ($order->grand_total ?? 0),
            'coupon_name' => $order->coupon_name,
            'coupon_value' => (float) ($order->coupon_value ?? 0),
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'total_km' => $order->total_km,
            'notes' => $order->notes,
            'created_at' => $order->created_at,
        ];
    }
}
