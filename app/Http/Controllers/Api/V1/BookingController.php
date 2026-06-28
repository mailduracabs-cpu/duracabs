<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\ApplyCouponRequest;
use App\Http\Requests\Api\V1\BookingRequest;
use App\Http\Requests\Api\V1\CancelBookingRequest;
use App\Services\BookingService;
use App\Services\CouponService;
use Illuminate\Http\Request;

class BookingController extends BaseApiController
{
    public function store(BookingRequest $request, BookingService $bookingService)
    {
        $result = $bookingService->create($request->validated());

        if (!$result['status']) {
            return $this->error($result['message'], $result['code'] ?? 422, $result['errors'] ?? null);
        }

        return $this->success($result['data'], $result['message'], 201);
    }

    public function index(Request $request, BookingService $bookingService)
    {
        $result = $bookingService->myBookings(
            mobile: $request->query('mobile'),
            userId: $request->query('user_id'),
            limit: (int) $request->query('limit', 20)
        );

        return $this->success($result, 'Bookings loaded successfully');
    }

    public function show($booking_id, BookingService $bookingService)
    {
        $result = $bookingService->detail($booking_id);

        if (!$result['status']) {
            return $this->error($result['message'], 404);
        }

        return $this->success($result['data'], 'Booking detail loaded successfully');
    }

    public function cancel(CancelBookingRequest $request, BookingService $bookingService)
    {
        $result = $bookingService->cancel($request->validated());

        if (!$result['status']) {
            return $this->error($result['message'], $result['code'] ?? 422);
        }

        return $this->success($result['data'], $result['message']);
    }

    public function coupons(CouponService $couponService)
    {
        return $this->success($couponService->activeCoupons(), 'Coupons loaded successfully');
    }

    public function applyCoupon(ApplyCouponRequest $request, CouponService $couponService)
    {
        $result = $couponService->apply(
            $request->coupon_code,
            (float) $request->amount
        );

        if (!$result['status']) {
            return $this->error($result['message'], 422);
        }

        return $this->success($result['data'], $result['message']);
    }
}
