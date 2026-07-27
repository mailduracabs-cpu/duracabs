<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BookingService
{
    private const GST_PERCENT = 5.0;

    /** @var array<string, bool> */
    private array $tableExistsCache = [];

    /** @var array<string, bool> */
    private array $columnExistsCache = [];

    public function __construct(
        private readonly CustomerJourneyService $customerJourneyService,
        private readonly NotificationService $notificationService,
        private readonly BookingNotificationContentService $bookingNotificationContentService
    ) {
    }

    public function create(array $data): array
    {
        if (! $this->tableExists('orders')) {
            return $this->failure('Orders table not found.', 500);
        }

        $mobile = preg_replace('/\D+/', '', (string) ($data['mobile'] ?? ''));

        if ($mobile === '') {
            return $this->failure('Customer mobile number is required.', 422);
        }

        $data['mobile'] = $mobile;
        $lockKey = $this->bookingLockKey($data);

        if (! Cache::add($lockKey, true, now()->addSeconds(60))) {
            return $this->failure(
                'Duplicate booking request detected. Please wait.',
                429
            );
        }

        try {
            $result = DB::transaction(function () use ($data, $lockKey): array {
                $recent = $this->findRecentDuplicate($data);

                if ($recent) {
                    Cache::forget($lockKey);

                    return [
                        'status' => true,
                        'message' => 'Booking already submitted.',
                        'data' => $this->detail($recent->id)['data'],
                    ];
                }

                $userId = $this->findOrCreateUser($data);
                $product = $this->getProduct($data['product_id'] ?? null);
                $category = $this->getCategory(
                    $data['category_id'] ?? ($product->category_id ?? null)
                );

                $isRoundTrip = $this->isRoundTrip($data);
                $baseFare = $this->resolveBaseFare($data, $product);

                if ($baseFare <= 0) {
                    Cache::forget($lockKey);

                    return $this->failure(
                        'Invalid booking amount. Please select a valid route or vehicle.',
                        422
                    );
                }

                $special = $this->resolveSpecialRequests($data);
                $tollAmount = $this->resolveCharge(
                    $data,
                    ['toll_amount', 'toll', 'toll_tax'],
                    ['toll_status', 'toll_included']
                );
                $parkingAmount = $this->resolveCharge(
                    $data,
                    ['parking_amount', 'parking'],
                    ['parking_status', 'parking_included']
                );
                $stateTaxAmount = $this->resolveCharge(
                    $data,
                    ['state_tax_amount', 'state_tax', 'other_tax'],
                    ['state_tax_status', 'state_tax_included']
                );
                $driverTotal = max(
                    0,
                    round((float) (
                        $data['driver_total']
                        ?? data_get($data, 'fare_breakup.driver_total')
                        ?? 0
                    ), 2)
                );
                $gstAmount = max(
                    0,
                    round((float) (
                        $data['gst_amount']
                        ?? data_get($data, 'fare_breakup.gst_amount')
                        ?? 0
                    ), 2)
                );
                $couponValue = $this->resolveCouponValue(
                    $data,
                    (float) ($data['grand_total'] ?? $baseFare)
                );

                if ($isRoundTrip) {
                    $grandTotal = max(
                        0,
                        round((float) (
                            $data['grand_total']
                            ?? $data['amount']
                            ?? $data['fare']
                            ?? 0
                        ), 2)
                    );
                } else {
                    $grandTotal = max(
                        0,
                        round(
                            $baseFare
                            + $tollAmount
                            + $parkingAmount
                            + $stateTaxAmount
                            + $special['total']
                            + $gstAmount
                            - $couponValue,
                            2
                        )
                    );
                }

                if ($grandTotal <= 0) {
                    Cache::forget($lockKey);

                    return $this->failure(
                        'Invalid final amount. Booking cannot be created with zero amount.',
                        422
                    );
                }

                $paymentMethod = strtolower(
                    trim((string) ($data['payment_method'] ?? 'cash'))
                );
                $isOnline = in_array(
                    $paymentMethod,
                    ['online', 'razorpay', 'razorpay_payment', 'card', 'upi'],
                    true
                );

                $orderStatus = $isOnline ? 'new' : 'confirm';
                $paymentStatus = $isOnline
                    ? 'pending'
                    : (string) ($data['payment_status'] ?? 'pending');

                $extraOptions = [
                    'source' => $data['source'] ?? 'flutter_app',
                    'search_activity_id' => $data['search_activity_id'] ?? null,
                    'search_activity_uuid' => $data['search_activity_uuid'] ?? null,
                    'service_type' => $this->serviceType($data),
                    'category_id' => $data['category_id']
                        ?? ($product->category_id ?? null),
                    'product_id' => $product->id ?? null,
                    'base_fare' => $baseFare,
                    'driver_total' => $driverTotal,
                    'toll_amount' => $tollAmount,
                    'parking_amount' => $parkingAmount,
                    'state_tax_amount' => $stateTaxAmount,
                    'tax_amount' => $stateTaxAmount,
                    'gst_percent' => (float) (
                        $data['gst_percent']
                        ?? data_get($data, 'fare_breakup.gst_percent')
                        ?? self::GST_PERCENT
                    ),
                    'gst_amount' => $gstAmount,
                    'special_requests' => $special['items'],
                    'special_request_total' => $special['total'],
                    'coupon_discount' => $couponValue,
                    'grand_total' => $grandTotal,
                    'trip_days' => (int) ($data['trip_days'] ?? 1),
                    'total_km' => $this->numericValue(
                        $data['total_km']
                        ?? data_get($data, 'route_meta.total_km')
                        ?? data_get($data, 'route_meta.distance')
                    ),
                    'minimum_km' => $this->numericValue(
                        $data['minimum_km']
                        ?? data_get($data, 'route_meta.minimum_km')
                    ),
                    'billable_km' => $this->numericValue(
                        $data['billable_km']
                        ?? data_get($data, 'route_meta.billable_km')
                    ),
                    'duration' => $data['duration']
                        ?? data_get($data, 'route_meta.duration'),
                    'locations' => $this->normalizeLocations(
                        $data['locations']
                        ?? $data['trip_route']
                        ?? data_get($data, 'route_meta.locations')
                        ?? []
                    ),
                    'trip_route' => $this->normalizeLocations(
                        $data['trip_route']
                        ?? $data['locations']
                        ?? data_get($data, 'route_meta.locations')
                        ?? []
                    ),
                    'trip_cities' => array_values(
                        array_filter(
                            (array) ($data['trip_cities'] ?? []),
                            fn ($value) => trim((string) $value) !== ''
                        )
                    ),
                    'fare_breakup' => is_array($data['fare_breakup'] ?? null)
                        ? $data['fare_breakup']
                        : [],
                    'auto_return_to_start' => (bool) (
                        $data['auto_return_to_start']
                        ?? $isRoundTrip
                    ),
                ];

                $orderData = [
                    'user_id' => $userId,
                    'vehicle_id' => $data['vehicle_id'] ?? null,
                    'grand_total' => $grandTotal,
                    'coupon_value' => $couponValue,
                    'coupon_name' => $data['coupon_name']
                        ?? $data['coupon_code']
                        ?? null,
                    'tax' => $gstAmount,
                    'payment_method' => $paymentMethod,
                    'payment_status' => $paymentStatus,
                    'total_km' => $this->numericValue(
                        $data['total_km']
                        ?? data_get($data, 'route_meta.total_km')
                        ?? data_get($data, 'route_meta.distance')
                        ?? ($product->km_limit ?? null)
                    ),
                    'date' => $data['pickup_date'] ?? null,
                    'dateTo' => $data['return_date'] ?? null,
                    'time' => $data['pickup_time'] ?? null,
                    'endTime' => $data['return_time'] ?? null,
                    'booking_from' => $data['pickup_address']
                        ?? $data['pickup']
                        ?? null,
                    'booking_to' => $data['drop_address']
                        ?? $data['drop']
                        ?? null,
                    'status' => $orderStatus,
                    'plan' => $this->validPlan(
                        $data['plan'] ?? ($product->plan ?? 'none')
                    ),
                    'currency' => 'INR',
                    'extraOptions' => json_encode(
                        $extraOptions,
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    ),
                    'cityFrom' => $data['pickup_city'] ?? null,
                    'cityTo' => $data['drop_city'] ?? null,
                    'productName' => $product->name
                        ?? $category->name
                        ?? $data['taxi_type']
                        ?? 'Taxi Booking',
                    'image' => $this->resolveBookingImage(
                        $product,
                        $category,
                        $data
                    ),
                    'shipping_ammount' => 0,
                    'taxi_type' => $data['taxi_type']
                        ?? $data['vehicle_type']
                        ?? ($category->name ?? null),
                    'notes' => $data['notes']
                        ?? $data['comments']
                        ?? 'Booking created from Dura Cabs mobile app.',
                    'ride_type' => $data['ride_type']
                        ?? $data['trip_type']
                        ?? ($product->ride_type ?? 'one_way'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $orderId = DB::table('orders')->insertGetId($orderData);
                $bookingNo = $this->bookingNumber($orderId);

                if ($this->columnExists('orders', 'booking_no')) {
                    DB::table('orders')->where('id', $orderId)->update([
                        'booking_no' => $bookingNo,
                        'updated_at' => now(),
                    ]);
                }

                $this->createOrderItem(
                    $orderId,
                    $product,
                    $data,
                    $baseFare
                );

                $this->createAddress(
                    $orderId,
                    $userId,
                    $data
                );

                Cache::forget($lockKey);

                return [
                    'status' => true,
                    'message' => $isOnline
                        ? 'Booking created. Please complete payment.'
                        : 'Booking created successfully.',
                    'data' => $this->detail($orderId)['data'],
                ];
            });

            if (
                ($result['status'] ?? false)
                && ($result['message'] ?? '') !== 'Booking already submitted.'
            ) {
                $bookingData = $result['data'] ?? [];

                $this->recordBookingCreationJourney(
                    requestData: $data,
                    bookingData: $bookingData
                );

                $this->sendBookingNotificationSafely(
                    event: 'booking_created',
                    bookingData: $bookingData,
                    requestData: $data
                );
            }

            return $result;
        } catch (\Throwable $e) {
            Cache::forget($lockKey);

            Log::error('V1 Booking Create Error', [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect($e->getTrace())->take(8)->all(),
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

    public function myBookings(
        ?string $mobile = null,
        ?int $userId = null,
        int $limit = 20
    ) {
        $limit = min(max($limit, 1), 100);
        $mobile = $mobile
            ? preg_replace('/\D+/', '', $mobile)
            : null;

        $bookings = collect();

        if ($this->tableExists('orders')) {
            $query = DB::table('orders')->orderByDesc('id');

            if ($userId || $mobile) {
                $query->where(function ($builder) use ($userId, $mobile): void {
                    if ($userId) {
                        $builder->where('user_id', $userId);
                    }

                    if ($mobile && $this->tableExists('addresses')) {
                        $ids = DB::table('addresses')
                            ->whereRaw(
                                "REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+91', '') = ?",
                                [$mobile]
                            )
                            ->pluck('order_id')
                            ->filter()
                            ->all();

                        if ($ids) {
                            $userId
                                ? $builder->orWhereIn('id', $ids)
                                : $builder->whereIn('id', $ids);
                        }
                    }
                });
            }

            $bookings = $bookings->merge(
                $query->limit($limit)->get()->map(
                    fn ($order) => array_merge(
                        $this->formatOrder($order),
                        [
                            'service_type' => 'taxi',
                            'booking_status' => $order->status,
                            'vehicle_name' => $order->productName,
                            'pickup_location' => $order->booking_from,
                            'drop_location' => $order->booking_to,
                            'total_amount' => (float) ($order->grand_total ?? 0),
                            'paid_amount' => 0.0,
                            'remaining_amount' => (float) ($order->grand_total ?? 0),
                        ]
                    )
                )
            );
        }

        if ($this->tableExists('self_drive_bookings')) {
            $query = DB::table('self_drive_bookings')
                ->leftJoin(
                    'vehicles',
                    'vehicles.id',
                    '=',
                    'self_drive_bookings.vehicle_id'
                )
                ->select([
                    'self_drive_bookings.*',
                    'vehicles.car_company_name',
                    'vehicles.model_name',
                    'vehicles.front_image',
                    'vehicles.vehicle_number',
                ])
                ->orderByDesc('self_drive_bookings.id');

            if ($userId) {
                $query->where('self_drive_bookings.customer_id', $userId);
            }

            $selfDrive = $query->limit($limit)->get()->map(function ($booking) {
                $vehicleName = trim(
                    ($booking->car_company_name ?? '')
                    . ' '
                    . ($booking->model_name ?? '')
                );

                $start = $booking->start_datetime
                    ? Carbon::parse($booking->start_datetime)
                    : null;
                $end = $booking->end_datetime
                    ? Carbon::parse($booking->end_datetime)
                    : null;

                return [
                    'id' => $booking->id,
                    'order_id' => $booking->id,
                    'booking_id' => $booking->booking_no,
                    'booking_no' => $booking->booking_no,
                    'service_type' => 'self_drive',
                    'ride_type' => 'self_drive',
                    'status' => $booking->booking_status ?: $booking->status,
                    'booking_status' => $booking->booking_status,
                    'vendor_confirmation_status' =>
                        $booking->vendor_confirmation_status,
                    'document_status' => $booking->document_status,
                    'taxi_type' => 'Self Drive',
                    'product_name' => $vehicleName ?: 'Self Drive Car',
                    'vehicle_name' => $vehicleName ?: 'Self Drive Car',
                    'vehicle_number' => ! empty($booking->pickup_otp_verified_at)
                        ? $booking->vehicle_number
                        : null,
                    'image' => $booking->front_image,
                    'pickup' => $booking->pickup_location,
                    'drop' => null,
                    'date' => $start?->format('Y-m-d'),
                    'return_date' => $end?->format('Y-m-d'),
                    'time' => $start?->format('H:i'),
                    'return_time' => $end?->format('H:i'),
                    'grand_total' => (float) (
                        ($booking->total_amount ?? 0)
                        + ($booking->security_deposit ?? 0)
                    ),
                    'payment_method' => $booking->payment_method,
                    'payment_status' => $booking->payment_status,
                    'created_at' => $booking->created_at,
                ];
            });

            $bookings = $bookings->merge($selfDrive);
        }

        return $bookings
            ->sortByDesc('created_at')
            ->values()
            ->take($limit)
            ->values();
    }

    public function detail($bookingId): array
    {
        if (! $this->tableExists('orders')) {
            return $this->failure('Orders table not found.', 500);
        }

        $order = DB::table('orders')
            ->where('id', $bookingId)
            ->when(
                $this->columnExists('orders', 'booking_no'),
                fn ($query) => $query->orWhere('booking_no', $bookingId)
            )
            ->first();

        if (! $order) {
            return $this->failure('Booking not found.', 404);
        }

        $formatted = $this->formatOrder($order);
        $items = $this->tableExists('order_items')
            ? DB::table('order_items')->where('order_id', $order->id)->get()
            : collect();
        $address = $this->tableExists('addresses')
            ? DB::table('addresses')->where('order_id', $order->id)->first()
            : null;

        return [
            'status' => true,
            'data' => array_merge(
                $formatted,
                [
                    'booking' => $formatted,
                    'items' => $items,
                    'address' => $address,
                ]
            ),
        ];
    }

    public function cancel(array $data): array
    {
        $order = $this->tableExists('orders')
            ? DB::table('orders')->where('id', $data['booking_id'])->first()
            : null;

        if (! $order) {
            return $this->failure('Booking not found.', 404);
        }

        if (in_array($order->status, ['cancelled', 'closed'], true)) {
            return $this->failure('Booking cannot be cancelled.', 422);
        }

        DB::table('orders')->where('id', $order->id)->update([
            'status' => 'cancelled',
            'notes' => trim(
                ($order->notes ?? '')
                . "\nCancel reason: "
                . ($data['reason'] ?? 'Cancelled by customer')
            ),
            'updated_at' => now(),
        ]);

        $bookingData = $this->detail($order->id)['data'];

        $this->recordBookingCancellationJourney(
            order: $order,
            bookingData: $bookingData,
            reason: $data['reason'] ?? 'Cancelled by customer',
            requestData: $data
        );

        $this->sendBookingNotificationSafely(
            event: 'booking_cancelled',
            bookingData: $bookingData,
            requestData: array_merge($data, [
                'reason' => $data['reason'] ?? 'Cancelled by customer',
            ])
        );

        return [
            'status' => true,
            'message' => 'Booking cancelled successfully.',
            'data' => $bookingData,
        ];
    }

    public function reschedule(array $data): array
    {
        $order = $this->tableExists('orders')
            ? DB::table('orders')->where('id', $data['booking_id'])->first()
            : null;

        if (! $order) {
            return $this->failure('Booking not found.', 404);
        }

        DB::table('orders')->where('id', $order->id)->update([
            'date' => $data['pickup_date'],
            'time' => $data['pickup_time'],
            'updated_at' => now(),
        ]);

        $bookingData = $this->detail($order->id)['data'];

        $this->sendBookingNotificationSafely(
            event: 'booking_rescheduled',
            bookingData: $bookingData,
            requestData: $data
        );

        return [
            'status' => true,
            'message' => 'Booking rescheduled successfully.',
            'data' => $bookingData,
        ];
    }

    public function confirm($bookingId): array
    {
        $order = $this->tableExists('orders')
            ? DB::table('orders')->where('id', $bookingId)->first()
            : null;

        if (! $order) {
            return $this->failure('Booking not found.', 404);
        }

        DB::table('orders')->where('id', $order->id)->update([
            'status' => 'confirm',
            'updated_at' => now(),
        ]);

        $bookingData = $this->detail($order->id)['data'];

        $this->recordBookingCompletionJourney(
            order: $order,
            bookingData: $bookingData
        );

        $this->sendBookingNotificationSafely(
            event: 'booking_confirmed',
            bookingData: $bookingData
        );

        return [
            'status' => true,
            'message' => 'Booking confirmed successfully.',
            'data' => $bookingData,
        ];
    }

    public function driverDetails($bookingId): array
    {
        $order = $this->tableExists('orders')
            ? DB::table('orders')->where('id', $bookingId)->first()
            : null;

        if (! $order) {
            return $this->failure('Booking not found.', 404);
        }

        return [
            'status' => true,
            'message' => 'Driver details loaded successfully.',
            'data' => [
                'id' => $order->id,
                'order_id' => $order->id,
                'booking_id' => $order->id,
                'booking_no' => $this->bookingNumber($order->id),
                'driver_name' => $order->driver_name ?? null,
                'driver_mobile' => $order->driver_mobile ?? null,
                'vehicle_number' => $order->vehicle_number ?? null,
                'vehicle_name' => $order->productName ?? null,
            ],
        ];
    }

    private function bookingLockKey(array $data): string
    {
        return 'booking_lock_' . md5(json_encode([
            'mobile' => $data['mobile'] ?? null,
            'product_id' => $data['product_id'] ?? null,
            'vehicle_id' => $data['vehicle_id'] ?? null,
            'pickup_date' => $data['pickup_date'] ?? null,
            'pickup_time' => $data['pickup_time'] ?? null,
            'pickup_address' => $data['pickup_address'] ?? null,
            'drop_address' => $data['drop_address'] ?? null,
            'ride_type' => $data['ride_type'] ?? null,
        ]));
    }

    private function bookingNumber(int $id): string
    {
        return 'DURA' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }

    private function serviceType(array $data): string
    {
        $type = strtolower(
            trim((string) (
                $data['service_type']
                ?? $data['ride_type']
                ?? $data['trip_type']
                ?? 'one_way'
            ))
        );

        return match ($type) {
            'roundtrip', 'round-trip' => 'round_trip',
            'selfdrive', 'self-drive' => 'self_drive',
            default => $type,
        };
    }

    private function resolveBaseFare(array $data, $product): float
    {
        $serverAmount = (float) ($product->price ?? 0);

        if ($serverAmount > 0) {
            return round($serverAmount, 2);
        }

        return max(
            0,
            round((float) (
                $data['base_fare']
                ?? $data['subtotal']
                ?? $data['amount']
                ?? $data['fare']
                ?? 0
            ), 2)
        );
    }

    private function resolveCharge(
        array $data,
        array $amountKeys,
        array $statusKeys
    ): float {
        foreach ($statusKeys as $key) {
            if ($this->isIncluded($data[$key] ?? null)) {
                return 0;
            }
        }

        foreach ($amountKeys as $key) {
            if (array_key_exists($key, $data)) {
                return max(0, round((float) $data[$key], 2));
            }
        }

        return 0;
    }

    private function resolveSpecialRequests(array $data): array
    {
        $selectedIds = collect($data['special_request_ids'] ?? [])
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (string) $id)
            ->values();

        $items = collect();

        if (
            $selectedIds->isNotEmpty()
            && $this->tableExists('special_requests')
        ) {
            $query = DB::table('special_requests')
                ->whereIn('id', $selectedIds->all());

            if ($this->columnExists('special_requests', 'is_active')) {
                $query->where('is_active', 1);
            } elseif ($this->columnExists('special_requests', 'active')) {
                $query->where('active', 1);
            }

            $items = $query->get()->map(function ($row): array {
                $price = (float) (
                    $row->price
                    ?? $row->amount
                    ?? $row->charge
                    ?? 0
                );
                $included = $this->isIncluded(
                    $row->included
                    ?? $row->is_included
                    ?? false
                );

                return [
                    'id' => $row->id,
                    'name' => $row->name
                        ?? $row->title
                        ?? 'Special Request',
                    'price' => $included ? 0 : round(max(0, $price), 2),
                    'included' => $included,
                ];
            });
        }

        if ($items->isEmpty() && is_array($data['special_requests'] ?? null)) {
            $items = collect($data['special_requests'])->map(
                function ($item): array {
                    $item = is_array($item) ? $item : (array) $item;
                    $included = $this->isIncluded(
                        $item['included']
                        ?? $item['is_included']
                        ?? false
                    );
                    $price = (float) (
                        $item['price']
                        ?? $item['amount']
                        ?? $item['charge']
                        ?? 0
                    );

                    return [
                        'id' => $item['id'] ?? null,
                        'name' => $item['name']
                            ?? $item['title']
                            ?? 'Special Request',
                        'price' => $included ? 0 : round(max(0, $price), 2),
                        'included' => $included,
                    ];
                }
            );
        }

        return [
            'items' => $items->values()->all(),
            'total' => round((float) $items->sum('price'), 2),
        ];
    }

    private function resolveCouponValue(array $data, float $baseFare): float
    {
        $value = max(0, (float) ($data['coupon_value'] ?? 0));

        return round(min($value, $baseFare), 2);
    }

    private function isIncluded($value): bool
    {
        if ($value === true || $value === 1) {
            return true;
        }

        return in_array(
            strtolower(trim((string) $value)),
            ['1', 'true', 'yes', 'included'],
            true
        );
    }

    private function findRecentDuplicate(array $data)
    {
        if (! $this->tableExists('addresses')) {
            return null;
        }

        $mobile = $data['mobile'] ?? null;

        if (! $mobile) {
            return null;
        }

        return DB::table('orders')
            ->join('addresses', 'addresses.order_id', '=', 'orders.id')
            ->select('orders.*')
            ->where('addresses.phone', $mobile)
            ->where('addresses.created_at', '>=', now()->subSeconds(60))
            ->where('orders.date', $data['pickup_date'] ?? null)
            ->where('orders.time', $data['pickup_time'] ?? null)
            ->where('orders.ride_type', $data['ride_type'] ?? null)
            ->orderByDesc('orders.id')
            ->first();
    }

    private function findOrCreateUser(array $data): ?int
    {
        if (! $this->tableExists('users')) {
            return $data['user_id'] ?? null;
        }

        if (! empty($data['user_id'])) {
            return (int) $data['user_id'];
        }

        $user = DB::table('users')
            ->where('mobile', $data['mobile'])
            ->first();

        if ($user) {
            return $user->id;
        }

        return DB::table('users')->insertGetId([
            'name' => $data['name'] ?? 'Dura Cabs Customer',
            'email' => $data['email']
                ?? ('customer' . $data['mobile'] . '@duracabs.app'),
            'mobile' => $data['mobile'],
            'password' => Hash::make(Str::random(24)),
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function getProduct($productId)
    {
        if (! $productId || ! $this->tableExists('products')) {
            return null;
        }

        return DB::table('products')
            ->where('id', $productId)
            ->first();
    }

    private function getCategory($categoryId)
    {
        if (! $categoryId || ! $this->tableExists('categories')) {
            return null;
        }

        return DB::table('categories')
            ->where('id', $categoryId)
            ->first();
    }

    private function createOrderItem(
        int $orderId,
        $product,
        array $data,
        float $baseFare
    ): void {
        if (! $this->tableExists('order_items')) {
            return;
        }

        $productId = $product->id ?? ($data['product_id'] ?? null);

        if (! $productId) {
            return;
        }

        if (
            $this->tableExists('products')
            && ! DB::table('products')->where('id', $productId)->exists()
        ) {
            return;
        }

        DB::table('order_items')->insert([
            'order_id' => $orderId,
            'product_id' => $productId,
            'quantity' => 1,
            'unit_ammount' => $baseFare,
            'total_ammount' => $baseFare,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createAddress(
        int $orderId,
        ?int $userId,
        array $data
    ): void {
        if (! $this->tableExists('addresses')) {
            return;
        }

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
            'comments' => $data['notes'] ?? $data['comments'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function isRoundTrip(array $data): bool
    {
        return $this->serviceType($data) === 'round_trip';
    }

    private function numericValue($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = preg_replace('/[^0-9.\-]/', '', (string) $value);

        if ($normalized === '' || ! is_numeric($normalized)) {
            return null;
        }

        return round((float) $normalized, 2);
    }

    private function normalizeLocations($locations): array
    {
        if (! is_array($locations)) {
            return [];
        }

        return collect($locations)
            ->map(function ($location): array {
                if (is_string($location)) {
                    return ['name' => trim($location)];
                }

                $location = is_array($location)
                    ? $location
                    : (array) $location;

                return array_filter([
                    'name' => trim((string) (
                        $location['name']
                        ?? $location['city']
                        ?? $location['title']
                        ?? $location['description']
                        ?? ''
                    )),
                    'place_id' => $location['place_id'] ?? null,
                    'lat' => $location['lat']
                        ?? $location['latitude']
                        ?? null,
                    'lng' => $location['lng']
                        ?? $location['longitude']
                        ?? null,
                ], fn ($value) => $value !== null && $value !== '');
            })
            ->filter(fn (array $location) => ! empty($location['name']))
            ->values()
            ->all();
    }

    private function resolveBookingImage(
        $product,
        $category,
        array $data
    ): ?string {
        $direct = $data['image'] ?? $data['image_url'] ?? null;

        if ($direct) {
            return (string) $direct;
        }

        if ($category) {
            $categoryImage = $category->image_url
                ?? $category->image
                ?? null;

            if ($categoryImage) {
                return (string) $categoryImage;
            }
        }

        return $this->resolveImage($product);
    }

    private function resolveImage($product): ?string
    {
        if (! $product || empty($product->images)) {
            return null;
        }

        $decoded = json_decode($product->images, true);

        return is_array($decoded) && isset($decoded[0])
            ? $decoded[0]
            : null;
    }

    private function validPlan(?string $plan): string
    {
        $allowed = [
            'none',
            '4 Hour / 40 Km',
            '8 Hour / 80 Km',
            '12 Hour / 120 Km',
        ];

        return in_array($plan, $allowed, true)
            ? $plan
            : 'none';
    }

    private function formatOrder($order): array
    {
        $extra = [];

        if (! empty($order->extraOptions)) {
            $decoded = json_decode($order->extraOptions, true);
            $extra = is_array($decoded) ? $decoded : [];
        }

        $bookingNo = ! empty($order->booking_no)
            ? $order->booking_no
            : $this->bookingNumber((int) $order->id);

        return [
            'id' => (int) $order->id,
            'order_id' => (int) $order->id,
            'booking_id' => (int) $order->id,
            'booking_no' => $bookingNo,
            'user_id' => $order->user_id,
            'status' => $order->status,
            'ride_type' => $order->ride_type,
            'service_type' => $extra['service_type'] ?? $order->ride_type,
            'taxi_type' => $order->taxi_type,
            'product_name' => $order->productName,
            'pickup' => $order->booking_from,
            'drop' => $order->booking_to,
            'pickup_city' => $order->cityFrom,
            'drop_city' => $order->cityTo,
            'date' => $order->date,
            'pickup_date' => $order->date,
            'return_date' => $order->dateTo,
            'time' => $order->time,
            'pickup_time' => $order->time,
            'return_time' => $order->endTime,
            'category_id' => $extra['category_id'] ?? null,
            'product_id' => $extra['product_id'] ?? null,
            'base_fare' => (float) ($extra['base_fare'] ?? 0),
            'driver_total' => (float) ($extra['driver_total'] ?? 0),
            'toll_amount' => (float) ($extra['toll_amount'] ?? 0),
            'parking_amount' => (float) ($extra['parking_amount'] ?? 0),
            'state_tax_amount' => (float) (
                $extra['state_tax_amount']
                ?? $extra['tax_amount']
                ?? 0
            ),
            'tax_amount' => (float) ($extra['tax_amount'] ?? 0),
            'gst_percent' => (float) (
                $extra['gst_percent'] ?? self::GST_PERCENT
            ),
            'gst_amount' => (float) (
                $extra['gst_amount'] ?? $order->tax ?? 0
            ),
            'special_requests' => $extra['special_requests'] ?? [],
            'special_request_total' => (float) (
                $extra['special_request_total'] ?? 0
            ),
            'trip_days' => (int) ($extra['trip_days'] ?? 1),
            'minimum_km' => $extra['minimum_km'] ?? null,
            'billable_km' => $extra['billable_km'] ?? null,
            'duration' => $extra['duration'] ?? null,
            'locations' => $extra['locations'] ?? [],
            'trip_route' => $extra['trip_route'] ?? [],
            'trip_cities' => $extra['trip_cities'] ?? [],
            'fare_breakup' => $extra['fare_breakup'] ?? [],
            'auto_return_to_start' => (bool) (
                $extra['auto_return_to_start'] ?? false
            ),
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

    /**
     * Record checkout and payment-stage activity after a new booking is saved.
     *
     * Journey tracking must never break booking creation, so all exceptions
     * are logged and swallowed here.
     */
    private function recordBookingCreationJourney(
        array $requestData,
        array $bookingData
    ): void {
        $activity = $this->resolveJourneyActivity($requestData);

        if (! $activity) {
            return;
        }

        try {
            $paymentMethod = strtolower(
                trim((string) (
                    $bookingData['payment_method']
                    ?? $requestData['payment_method']
                    ?? 'cash'
                ))
            );

            $paymentStatus = strtolower(
                trim((string) (
                    $bookingData['payment_status']
                    ?? $requestData['payment_status']
                    ?? 'pending'
                ))
            );

            $grandTotal = (float) (
                $bookingData['grand_total']
                ?? $requestData['grand_total']
                ?? $requestData['amount']
                ?? 0
            );

            $checkoutData = [
                'vehicle_id' => $requestData['vehicle_id'] ?? null,
                'vehicle_category_id' => $requestData['category_id'] ?? null,
                'vehicle_name' => $bookingData['product_name']
                    ?? $requestData['taxi_type']
                    ?? $requestData['vehicle_type']
                    ?? null,
                'vehicle_category_name' => $requestData['taxi_type']
                    ?? $requestData['vehicle_type']
                    ?? null,
                'vehicle_type' => $requestData['vehicle_type']
                    ?? $requestData['taxi_type']
                    ?? null,
                'plan_type' => $requestData['plan'] ?? null,
                'plan_name' => $requestData['plan'] ?? null,
                'estimated_amount' => $grandTotal,
                'grand_total' => $grandTotal,
                'base_fare' => (float) (
                    $bookingData['base_fare']
                    ?? $requestData['base_fare']
                    ?? 0
                ),
                'discount_amount' => (float) (
                    $bookingData['coupon_value']
                    ?? $requestData['coupon_value']
                    ?? 0
                ),
                'coupon_code' => $bookingData['coupon_name']
                    ?? $requestData['coupon_code']
                    ?? null,
                'coupon_discount' => (float) (
                    $bookingData['coupon_value']
                    ?? $requestData['coupon_value']
                    ?? 0
                ),
                'currency' => 'INR',
                'is_all_inclusive' => (bool) (
                    $requestData['is_all_inclusive']
                    ?? $requestData['all_inclusive']
                    ?? false
                ),
                'fare_breakdown' => $bookingData['fare_breakup']
                    ?? $requestData['fare_breakup']
                    ?? [],
                'metadata' => [
                    'booking_id' => $bookingData['booking_id']
                        ?? $bookingData['id']
                        ?? null,
                    'booking_no' => $bookingData['booking_no'] ?? null,
                    'service_type' => $bookingData['service_type']
                        ?? $this->serviceType($requestData),
                    'source' => $requestData['source'] ?? 'flutter_app',
                ],
            ];

            $this->customerJourneyService->checkoutStarted(
                searchActivity: $activity,
                checkoutData: $checkoutData
            );

            $isOnline = in_array(
                $paymentMethod,
                ['online', 'razorpay', 'razorpay_payment', 'card', 'upi'],
                true
            );

            if (! $isOnline) {
                return;
            }

            $paymentData = [
                'amount' => $grandTotal,
                'grand_total' => $grandTotal,
                'payment_method' => $paymentMethod,
                'gateway' => $requestData['payment_gateway'] ?? 'razorpay',
                'currency' => 'INR',
                'order_id' => (string) (
                    $bookingData['order_id']
                    ?? $bookingData['id']
                    ?? ''
                ),
                'gateway_order_id' => $requestData['gateway_order_id']
                    ?? $requestData['razorpay_order_id']
                    ?? null,
                'transaction_id' => $requestData['transaction_id']
                    ?? $requestData['payment_id']
                    ?? null,
                'metadata' => [
                    'booking_no' => $bookingData['booking_no'] ?? null,
                    'source' => $requestData['source'] ?? 'flutter_app',
                ],
            ];

            $this->customerJourneyService->paymentStarted(
                searchActivity: $activity,
                paymentData: $paymentData
            );

            if (in_array(
                $paymentStatus,
                ['paid', 'success', 'successful', 'succeeded', 'captured'],
                true
            )) {
                $this->customerJourneyService->paymentSucceeded(
                    searchActivity: $activity,
                    paymentData: array_merge($paymentData, [
                        'paid_amount' => $grandTotal,
                        'gateway_payment_id' => $requestData['gateway_payment_id']
                            ?? $requestData['razorpay_payment_id']
                            ?? $requestData['payment_id']
                            ?? null,
                        'gateway_signature' => $requestData['gateway_signature']
                            ?? $requestData['razorpay_signature']
                            ?? null,
                        'status' => $paymentStatus,
                    ]),
                    bookingData: [
                        'booking_id' => $bookingData['booking_id']
                            ?? $bookingData['id']
                            ?? null,
                        'booking_no' => $bookingData['booking_no'] ?? null,
                        'status' => $bookingData['status'] ?? 'confirm',
                        'service_type' => $bookingData['service_type']
                            ?? $this->serviceType($requestData),
                        'trip_type' => $bookingData['ride_type']
                            ?? $requestData['ride_type']
                            ?? null,
                        'total_amount' => $grandTotal,
                        'paid_amount' => $grandTotal,
                        'metadata' => [
                            'source' => $requestData['source'] ?? 'flutter_app',
                        ],
                    ]
                );
            }
        } catch (\Throwable $exception) {
            Log::error('Booking customer journey recording failed.', [
                'action' => 'booking_created',
                'booking_id' => $bookingData['booking_id']
                    ?? $bookingData['id']
                    ?? null,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);
        }
    }

    /**
     * Record cancellation for the journey connected to an order.
     */
    private function recordBookingCancellationJourney(
        object $order,
        array $bookingData,
        string $reason,
        array $requestData = []
    ): void {
        $extra = [];

        if (! empty($order->extraOptions)) {
            $decoded = json_decode($order->extraOptions, true);
            $extra = is_array($decoded) ? $decoded : [];
        }

        $activity = $this->resolveJourneyActivity(array_merge(
            $extra,
            $requestData
        ));

        if (! $activity) {
            return;
        }

        try {
            $this->customerJourneyService->bookingCancelled(
                searchActivity: $activity,
                bookingData: [
                    'booking_id' => $bookingData['booking_id']
                        ?? $bookingData['id']
                        ?? $order->id,
                    'booking_no' => $bookingData['booking_no']
                        ?? ($order->booking_no ?? $this->bookingNumber((int) $order->id)),
                    'status' => 'cancelled',
                    'refund_amount' => (float) (
                        $requestData['refund_amount'] ?? 0
                    ),
                    'refund_status' => $requestData['refund_status'] ?? null,
                    'cancellation_charge' => (float) (
                        $requestData['cancellation_charge'] ?? 0
                    ),
                    'cancelled_by' => $requestData['cancelled_by']
                        ?? 'customer',
                    'cancellation_type' => $requestData['cancellation_type']
                        ?? 'customer_cancellation',
                    'cancelled_at' => now(),
                    'metadata' => [
                        'service_type' => $bookingData['service_type']
                            ?? $extra['service_type']
                            ?? $order->ride_type
                            ?? null,
                    ],
                ],
                reason: $reason
            );
        } catch (\Throwable $exception) {
            Log::error('Booking cancellation journey recording failed.', [
                'booking_id' => $order->id ?? null,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);
        }
    }

    /**
     * Mark a confirmed booking as converted in the linked customer journey.
     *
     * This runs outside the order update and never blocks confirmation.
     */
    private function recordBookingCompletionJourney(
        object $order,
        array $bookingData
    ): void {
        $extra = [];

        if (! empty($order->extraOptions)) {
            $decoded = json_decode($order->extraOptions, true);
            $extra = is_array($decoded) ? $decoded : [];
        }

        $activity = $this->resolveJourneyActivity($extra);

        if (! $activity) {
            return;
        }

        try {
            $this->customerJourneyService->bookingCompleted(
                searchActivity: $activity,
                bookingData: [
                    'booking_id' => $bookingData['booking_id']
                        ?? $bookingData['id']
                        ?? $order->id,
                    'booking_no' => $bookingData['booking_no']
                        ?? ($order->booking_no ?? $this->bookingNumber((int) $order->id)),
                    'status' => 'confirm',
                    'service_type' => $bookingData['service_type']
                        ?? $extra['service_type']
                        ?? $order->ride_type
                        ?? null,
                    'trip_type' => $bookingData['ride_type']
                        ?? $order->ride_type
                        ?? null,
                    'total_amount' => (float) ($bookingData['grand_total'] ?? 0),
                    'paid_amount' => in_array(
                        strtolower((string) ($bookingData['payment_status'] ?? '')),
                        ['paid', 'success', 'successful', 'succeeded', 'captured'],
                        true
                    ) ? (float) ($bookingData['grand_total'] ?? 0) : 0.0,
                    'completed_at' => now(),
                    'metadata' => [
                        'source' => $extra['source'] ?? 'flutter_app',
                        'conversion_trigger' => 'booking_confirmed',
                    ],
                ]
            );
        } catch (\Throwable $exception) {
            Log::error('Booking completion journey recording failed.', [
                'booking_id' => $order->id ?? null,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);
        }
    }

    /**
     * Send customer notifications without allowing gateway failures to affect
     * booking operations.
     */
    private function sendBookingNotificationSafely(
        string $event,
        array $bookingData,
        array $requestData = []
    ): void {
        try {
            $userId = $bookingData['user_id']
                ?? $requestData['user_id']
                ?? null;

            if (! $userId) {
                Log::info('Booking notification skipped because user ID was unavailable.', [
                    'event' => $event,
                    'booking_id' => $bookingData['booking_id']
                        ?? $bookingData['id']
                        ?? null,
                ]);

                return;
            }

            $user = User::query()->find((int) $userId);

            if (! $user) {
                Log::warning('Booking notification skipped because user was not found.', [
                    'event' => $event,
                    'user_id' => $userId,
                    'booking_id' => $bookingData['booking_id']
                        ?? $bookingData['id']
                        ?? null,
                ]);

                return;
            }

            $notificationContent = $this->bookingNotificationContent(
                event: $event,
                bookingData: $bookingData,
                requestData: $requestData
            );

            $subject = $notificationContent['title'];
            $message = $notificationContent['message'];

            $bookingId = (string) (
                $bookingData['booking_no']
                ?? $bookingData['booking_id']
                ?? $bookingData['id']
                ?? ''
            );

            $status = match ($event) {
                'booking_created' => 'pending',
                'booking_confirmed' => 'confirmed',
                'booking_cancelled' => 'cancelled',
                'booking_rescheduled' => 'rescheduled',
                default => str_replace('booking_', '', $event),
            };

            $channels = ['push'];

            if (filled($user->mobile)) {
                $channels[] = 'whatsapp';
            }

            if ($this->hasRealCustomerEmail($user->email)) {
                $channels[] = 'email';
            }

            $result = $this->notificationService->sendBookingMultiChannel(
                user: $user,
                bookingId: $bookingId,
                bookingStatus: $status,
                channels: $channels,
                title: $subject,
                message: $message,
                bookingType: (string) (
                    $bookingData['service_type']
                    ?? $requestData['service_type']
                    ?? $requestData['ride_type']
                    ?? 'taxi'
                ),
                extraData: [
                    'event' => $event,
                    'order_id' => (string) (
                        $bookingData['order_id']
                        ?? $bookingData['id']
                        ?? ''
                    ),
                    'booking_no' => $bookingData['booking_no'] ?? $bookingId,
                    'status' => $bookingData['status'] ?? $status,
                    'pickup' => $bookingData['pickup'] ?? null,
                    'drop' => $bookingData['drop'] ?? null,
                    'pickup_date' => $bookingData['pickup_date']
                        ?? $bookingData['date']
                        ?? null,
                    'pickup_time' => $bookingData['pickup_time']
                        ?? $bookingData['time']
                        ?? null,
                    'grand_total' => (string) (
                        $bookingData['grand_total'] ?? 0
                    ),
                    'click_action' => 'OPEN_BOOKING',
                ],
                fallback: false
            );

            if (! ($result['status'] ?? false)) {
                Log::warning('Booking notification was not delivered.', [
                    'event' => $event,
                    'booking_id' => $bookingData['booking_id']
                        ?? $bookingData['id']
                        ?? null,
                    'user_id' => $user->id,
                    'result' => $result,
                ]);
            }
        } catch (\Throwable $exception) {
            Log::error('Booking notification dispatch failed.', [
                'event' => $event,
                'booking_id' => $bookingData['booking_id']
                    ?? $bookingData['id']
                    ?? null,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);
        }
    }

    private function hasRealCustomerEmail(?string $email): bool
    {
        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $email = strtolower(trim($email));

        return ! str_ends_with($email, '@duracabs.app')
            && ! str_ends_with($email, '@duracabs.local')
            && ! str_starts_with($email, 'customer');
    }

    private function resolveBookingContact(
        array $bookingData,
        array $requestData,
        ?User $user
    ): array {
        $mobile = $requestData['mobile'] ?? $user?->mobile ?? null;
        $email = $requestData['email'] ?? $user?->email ?? null;

        $bookingId = $bookingData['order_id']
            ?? $bookingData['booking_id']
            ?? $bookingData['id']
            ?? null;

        if ($bookingId && $this->tableExists('addresses')) {
            $address = DB::table('addresses')
                ->where('order_id', $bookingId)
                ->first();

            $mobile ??= $address->phone ?? null;
            $email ??= $address->email ?? null;
        }

        $deviceToken = null;

        if ($user) {
            foreach (['device_token', 'fcm_token', 'firebase_token'] as $column) {
                if (isset($user->{$column}) && trim((string) $user->{$column}) !== '') {
                    $deviceToken = trim((string) $user->{$column});
                    break;
                }
            }
        }

        return [
            'mobile' => $mobile
                ? preg_replace('/\D+/', '', (string) $mobile)
                : null,
            'email' => $email ? trim((string) $email) : null,
            'device_token' => $deviceToken,
        ];
    }

    private function bookingNotificationContent(
        string $event,
        array $bookingData,
        array $requestData = []
    ): array {
        $bookingId = $bookingData['booking_no']
            ?? $bookingData['booking_id']
            ?? $bookingData['id']
            ?? '';

        return $this->bookingNotificationContentService->make(
            status: $event,
            bookingId: $bookingId,
            context: array_merge($requestData, $bookingData)
        );
    }

    /**
     * Resolve a customer journey without making booking operations fail.
     */
    private function resolveJourneyActivity(array $data): ?\App\Models\CustomerSearchActivity
    {
        $identifier = $data['search_activity_id']
            ?? $data['search_activity_uuid']
            ?? null;

        if ($identifier === null || trim((string) $identifier) === '') {
            return null;
        }

        try {
            return $this->customerJourneyService->findActivityOrFail(
                is_numeric($identifier)
                    ? (int) $identifier
                    : trim((string) $identifier)
            );
        } catch (\Throwable $exception) {
            Log::warning('Customer journey activity could not be resolved.', [
                'identifier' => $identifier,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function failure(string $message, int $code): array
    {
        return [
            'status' => false,
            'message' => $message,
            'code' => $code,
        ];
    }

    /**
     * Avoid repeated information_schema queries during one booking request.
     */
    private function tableExists(string $table): bool
    {
        return $this->tableExistsCache[$table]
            ??= Schema::hasTable($table);
    }

    /**
     * Avoid repeated information_schema queries during one booking request.
     */
    private function columnExists(string $table, string $column): bool
    {
        $key = $table . '.' . $column;

        return $this->columnExistsCache[$key]
            ??= Schema::hasColumn($table, $column);
    }

}