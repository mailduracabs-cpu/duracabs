<?php

namespace App\Services;

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

        try {
            return DB::transaction(function () use ($data) {
                $userId = $this->findOrCreateUser($data);
                $product = $this->getProduct($data['product_id'] ?? null);
                $category = $this->getCategory($data['category_id'] ?? ($product->category_id ?? null));
                $amount = $this->resolveAmount($data, $product);

                $orderId = DB::table('orders')->insertGetId([
                    'user_id' => $userId,
                    'vehicle_id' => $data['vehicle_id'] ?? null,
                    'grand_total' => $data['grand_total'] ?? $amount,
                    'coupon_value' => $data['coupon_value'] ?? null,
                    'coupon_name' => $data['coupon_name'] ?? null,
                    'tax' => $data['tax'] ?? null,
                    'payment_method' => $data['payment_method'] ?? 'cash',
                    'payment_status' => $data['payment_status'] ?? 'pending',
                    'total_km' => $data['total_km'] ?? ($product->km_limit ?? null),
                    'date' => $data['pickup_date'] ?? null,
                    'dateTo' => $data['return_date'] ?? null,
                    'time' => $data['pickup_time'] ?? null,
                    'endTime' => $data['return_time'] ?? null,
                    'booking_from' => $data['pickup_address'] ?? null,
                    'booking_to' => $data['drop_address'] ?? null,
                    'status' => 'new',
                    'plan' => $this->validPlan($data['plan'] ?? ($product->plan ?? 'none')),
                    'currency' => 'INR',
                    'extraOptions' => isset($data['extra_options']) ? json_encode($data['extra_options']) : null,
                    'cityFrom' => $data['pickup_city'] ?? null,
                    'cityTo' => $data['drop_city'] ?? null,
                    'productName' => $product->name ?? ($category->name ?? ($data['taxi_type'] ?? 'Taxi Booking')),
                    'image' => $this->resolveImage($product),
                    'shipping_ammount' => null,
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

                $booking = $this->detail($orderId)['data'];

                return [
                    'status' => true,
                    'message' => 'Booking created successfully',
                    'data' => $booking,
                ];
            });
        } catch (\Throwable $e) {
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

        if (in_array($order->status, ['cancelled', 'closed'])) {
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

        return DB::table('products')->where('id', $productId)->first();
    }

    private function getCategory($categoryId)
    {
        if (!$categoryId || !Schema::hasTable('categories')) {
            return null;
        }

        return DB::table('categories')->where('id', $categoryId)->first();
    }

    private function resolveAmount(array $data, $product): float
    {
        if (isset($data['grand_total'])) {
            return (float) $data['grand_total'];
        }

        if (isset($data['amount'])) {
            return (float) $data['amount'];
        }

        return (float) ($product->price ?? 0);
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
