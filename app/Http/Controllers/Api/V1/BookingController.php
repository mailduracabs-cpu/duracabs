<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\ApplyCouponRequest;
use App\Http\Requests\Api\V1\BookingRequest;
use App\Http\Requests\Api\V1\CancelBookingRequest;
use App\Models\Order;
use App\Services\BookingService;
use App\Services\CouponService;
use Illuminate\Http\Request;

class BookingController extends BaseApiController
{
    public function store(
        BookingRequest $request,
        BookingService $bookingService
    ) {
        $result = $bookingService->create(
            $request->validated()
        );

        if (! ($result['status'] ?? false)) {
            return $this->error(
                $result['message'] ?? 'Booking could not be created.',
                $result['code'] ?? 422,
                $result['errors'] ?? null
            );
        }

        return $this->success(
            $result['data'] ?? [],
            $result['message'] ?? 'Booking created successfully.',
            201
        );
    }

    public function index(
        Request $request,
        BookingService $bookingService
    ) {
        $result = $bookingService->myBookings(
            mobile: $request->query('mobile'),
            userId: $request->query('user_id')
                ? (int) $request->query('user_id')
                : null,
            limit: (int) $request->query('limit', 20)
        );

        return $this->success(
            $result,
            'Bookings loaded successfully.'
        );
    }

    public function show(
        $bookingId,
        BookingService $bookingService
    ) {
        $result = $bookingService->detail($bookingId);

        if (! ($result['status'] ?? false)) {
            return $this->error(
                $result['message'] ?? 'Booking not found.',
                $result['code'] ?? 404
            );
        }

        return $this->success(
            $result['data'] ?? [],
            'Booking detail loaded successfully.'
        );
    }

    public function cancel(
        CancelBookingRequest $request,
        BookingService $bookingService
    ) {
        $result = $bookingService->cancel(
            $request->validated()
        );

        if (! ($result['status'] ?? false)) {
            return $this->error(
                $result['message'] ?? 'Booking could not be cancelled.',
                $result['code'] ?? 422
            );
        }

        return $this->success(
            $result['data'] ?? [],
            $result['message'] ?? 'Booking cancelled successfully.'
        );
    }

    public function coupons(
        CouponService $couponService
    ) {
        return $this->success(
            $couponService->activeCoupons(),
            'Coupons loaded successfully.'
        );
    }

    public function applyCoupon(
        ApplyCouponRequest $request,
        CouponService $couponService
    ) {
        $result = $couponService->apply(
            $request->coupon_code,
            (float) $request->amount
        );

        if (! ($result['status'] ?? false)) {
            return $this->error(
                $result['message'] ?? 'Coupon could not be applied.',
                422
            );
        }

        return $this->success(
            $result['data'] ?? [],
            $result['message'] ?? 'Coupon applied successfully.'
        );
    }

    public function reschedule(
        Request $request,
        BookingService $bookingService
    ) {
        $validated = $request->validate([
            'booking_id' => 'required',
            'pickup_date' => 'required|date',
            'pickup_time' => 'required',
        ]);

        $result = $bookingService->reschedule(
            $validated
        );

        if (! ($result['status'] ?? false)) {
            return $this->error(
                $result['message'] ?? 'Booking could not be rescheduled.',
                $result['code'] ?? 422
            );
        }

        return $this->success(
            $result['data'] ?? [],
            $result['message'] ?? 'Booking rescheduled successfully.'
        );
    }

    public function confirm(
        Request $request,
        BookingService $bookingService
    ) {
        $validated = $request->validate([
            'booking_id' => 'required',
        ]);

        $result = $bookingService->confirm(
            $validated['booking_id']
        );

        if (! ($result['status'] ?? false)) {
            return $this->error(
                $result['message'] ?? 'Booking could not be confirmed.',
                $result['code'] ?? 422
            );
        }

        return $this->success(
            $result['data'] ?? [],
            $result['message'] ?? 'Booking confirmed successfully.'
        );
    }

    public function driverDetails(
        Request $request,
        BookingService $bookingService
    ) {
        $validated = $request->validate([
            'booking_id' => 'required',
        ]);

        $result = $bookingService->driverDetails(
            $validated['booking_id']
        );

        if (! ($result['status'] ?? false)) {
            return $this->error(
                $result['message'] ?? 'Driver details not found.',
                $result['code'] ?? 404
            );
        }

        return $this->success(
            $result['data'] ?? [],
            $result['message'] ?? 'Driver details loaded successfully.'
        );
    }

    public function updateLiveLocation(
        Request $request,
        string $booking
    ) {
        $validated = $request->validate([
            'latitude' => [
                'required',
                'numeric',
                'between:-90,90',
            ],
            'longitude' => [
                'required',
                'numeric',
                'between:-180,180',
            ],
            'sharing_enabled' => [
                'nullable',
                'boolean',
            ],
        ]);

        $user = $request->user();

        if (! $user) {
            return $this->error(
                'Unauthenticated.',
                401
            );
        }

        $order = Order::query()
            ->where('user_id', $user->id)
            ->where(function ($query) use ($booking): void {
                $query->where('booking_no', $booking);

                if (ctype_digit($booking)) {
                    $query->orWhere('id', (int) $booking);
                }
            })
            ->first();

        if (! $order) {
            return $this->error(
                'Booking not found or you are not allowed to update this booking.',
                404
            );
        }

        if (in_array(
            strtolower((string) $order->status),
            [
                'completed',
                'complete',
                'closed',
                'cancelled',
                'canceled',
            ],
            true
        )) {
            return $this->error(
                'Live location cannot be updated for this booking.',
                422
            );
        }

        $order->customer_live_lat =
            (float) $validated['latitude'];

        $order->customer_live_lng =
            (float) $validated['longitude'];

        $order->location_sharing_enabled =
            (bool) ($validated['sharing_enabled'] ?? true);

        $order->customer_live_location_updated_at = now();

        $order->save();

        return $this->success(
            [
                'booking_id' => $order->id,
                'booking_no' => $order->booking_no,
                'location' => [
                    'latitude' =>
                        (float) $order->customer_live_lat,
                    'longitude' =>
                        (float) $order->customer_live_lng,
                ],
                'sharing_enabled' =>
                    (bool) $order->location_sharing_enabled,
                'updated_at' =>
                    $order->customer_live_location_updated_at
                        ? $order->customer_live_location_updated_at
                            ->toIso8601String()
                        : null,
            ],
            'Live location updated successfully.'
        );
    }

    public function stopLiveLocation(
        Request $request,
        string $booking
    ) {
        $user = $request->user();

        if (! $user) {
            return $this->error(
                'Unauthenticated.',
                401
            );
        }

        $order = Order::query()
            ->where('user_id', $user->id)
            ->where(function ($query) use ($booking): void {
                $query->where('booking_no', $booking);

                if (ctype_digit($booking)) {
                    $query->orWhere('id', (int) $booking);
                }
            })
            ->first();

        if (! $order) {
            return $this->error(
                'Booking not found or you are not allowed to update this booking.',
                404
            );
        }

        $order->location_sharing_enabled = false;
        $order->save();

        return $this->success(
            [
                'booking_id' => $order->id,
                'booking_no' => $order->booking_no,
                'sharing_enabled' => false,
            ],
            'Live location sharing stopped successfully.'
        );
    }

}