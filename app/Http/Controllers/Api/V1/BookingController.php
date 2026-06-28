<?php

namespace App\Http\Controllers\Api\V1;

class BookingController extends BaseApiController
{
    public function store()
    {
        return $this->success([], 'Booking API coming in Package 4');
    }

    public function index()
    {
        return $this->success([], 'My bookings API coming in Package 4');
    }

    public function show($booking_id)
    {
        return $this->success(['booking_id' => $booking_id], 'Booking detail API coming in Package 4');
    }

    public function cancel()
    {
        return $this->success([], 'Booking cancel API coming in Package 4');
    }

    public function coupons()
    {
        return $this->success([], 'Coupons API coming in Package 4');
    }

    public function applyCoupon()
    {
        return $this->success([], 'Apply coupon API coming in Package 4');
    }
}
