<?php

use App\Models\BikeRentalBooking;
use App\Models\SelfDriveBooking;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('bike_rental_bookings') ||
            ! Schema::hasTable('self_drive_bookings')
        ) {
            return;
        }

        DB::transaction(function () {

            BikeRentalBooking::query()
                ->orderBy('id')
                ->chunkById(100, function ($bookings) {

                    foreach ($bookings as $booking) {

                        // Already migrated?
                        if (
                            SelfDriveBooking::where('booking_no', $booking->booking_no)->exists()
                        ) {
                            continue;
                        }

                        SelfDriveBooking::create([

                            'booking_no'                 => $booking->booking_no,

                            'booking_type'               => 'bike',

                            'customer_id'                => $booking->customer_id,

                            'vehicle_id'                 => $booking->vehicle_id,

                            'transporter_profile_id'     => $booking->transporter_profile_id,

                            'pickup_location'            => $booking->pickup_location,
                            'pickup_latitude'            => $booking->pickup_latitude,
                            'pickup_longitude'           => $booking->pickup_longitude,

                            'start_datetime'             => $booking->start_datetime,
                            'end_datetime'               => $booking->end_datetime,

                            'booked_hours'               => $booking->booked_hours,

                            'plan_type'                  => $booking->plan_type,

                            'base_rent'                  => $booking->base_rent,

                            'helmet_option'              => $booking->helmet_option,

                            'helmet_charge'              => $booking->helmet_charge,

                            'security_deposit'           => $booking->security_deposit,

                            'discount_amount'            => $booking->discount_amount,

                            'total_amount'               => $booking->total_amount,

                            'paid_amount'                => $booking->paid_amount,

                            'remaining_amount'           => $booking->remaining_amount,

                            'payment_type'               => $booking->payment_type,

                            'payment_method'             => $booking->payment_method,

                            'payment_reference'          => $booking->payment_reference,

                            'payment_status'             => $booking->payment_status,

                            'booking_status'             => $booking->booking_status,

                            'customer_note'              => $booking->customer_note,

                            'created_at'                 => $booking->created_at,

                            'updated_at'                 => $booking->updated_at,
                        ]);
                    }
                });
        });
    }

    public function down(): void
    {
        SelfDriveBooking::where('booking_type', 'bike')->delete();
    }
};