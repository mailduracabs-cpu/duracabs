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
}