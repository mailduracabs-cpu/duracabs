<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('self_drive_bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('self_drive_bookings', 'booked_hours')) {
                $table->unsignedInteger('booked_hours')->default(24)->after('end_datetime');
            }

            if (!Schema::hasColumn('self_drive_bookings', 'hourly_price')) {
                $table->decimal('hourly_price', 10, 2)->default(0)->after('booked_hours');
            }

            if (!Schema::hasColumn('self_drive_bookings', 'minimum_booking_hours')) {
                $table->unsignedInteger('minimum_booking_hours')->default(24)->after('hourly_price');
            }

            if (!Schema::hasColumn('self_drive_bookings', 'security_deposit')) {
                $table->decimal('security_deposit', 10, 2)->default(0)->after('minimum_booking_hours');
            }

            if (!Schema::hasColumn('self_drive_bookings', 'pickup_otp')) {
                $table->string('pickup_otp', 4)->nullable()->after('status');
            }

            if (!Schema::hasColumn('self_drive_bookings', 'pickup_otp_verified_at')) {
                $table->timestamp('pickup_otp_verified_at')->nullable()->after('pickup_otp');
            }

            if (!Schema::hasColumn('self_drive_bookings', 'return_otp')) {
                $table->string('return_otp', 4)->nullable()->after('pickup_otp_verified_at');
            }

            if (!Schema::hasColumn('self_drive_bookings', 'return_otp_verified_at')) {
                $table->timestamp('return_otp_verified_at')->nullable()->after('return_otp');
            }

            if (!Schema::hasColumn('self_drive_bookings', 'trip_start_datetime')) {
                $table->dateTime('trip_start_datetime')->nullable()->after('return_otp_verified_at');
            }

            if (!Schema::hasColumn('self_drive_bookings', 'trip_end_datetime')) {
                $table->dateTime('trip_end_datetime')->nullable()->after('trip_start_datetime');
            }

            if (!Schema::hasColumn('self_drive_bookings', 'start_km')) {
                $table->decimal('start_km', 10, 2)->nullable()->after('trip_end_datetime');
            }

            if (!Schema::hasColumn('self_drive_bookings', 'end_km')) {
                $table->decimal('end_km', 10, 2)->nullable()->after('start_km');
            }

            if (!Schema::hasColumn('self_drive_bookings', 'actual_hours')) {
                $table->decimal('actual_hours', 10, 2)->default(0)->after('end_km');
            }

            if (!Schema::hasColumn('self_drive_bookings', 'free_km')) {
                $table->decimal('free_km', 10, 2)->default(0)->after('actual_hours');
            }

            if (!Schema::hasColumn('self_drive_bookings', 'actual_km')) {
                $table->decimal('actual_km', 10, 2)->default(0)->after('free_km');
            }

            if (!Schema::hasColumn('self_drive_bookings', 'extra_hours')) {
                $table->decimal('extra_hours', 10, 2)->default(0)->after('actual_km');
            }

            if (!Schema::hasColumn('self_drive_bookings', 'extra_km')) {
                $table->decimal('extra_km', 10, 2)->default(0)->after('extra_hours');
            }

            if (!Schema::hasColumn('self_drive_bookings', 'extra_hour_amount')) {
                $table->decimal('extra_hour_amount', 10, 2)->default(0)->after('extra_km');
            }

            if (!Schema::hasColumn('self_drive_bookings', 'extra_km_amount')) {
                $table->decimal('extra_km_amount', 10, 2)->default(0)->after('extra_hour_amount');
            }

            if (!Schema::hasColumn('self_drive_bookings', 'damage_amount')) {
                $table->decimal('damage_amount', 10, 2)->default(0)->after('extra_km_amount');
            }

            if (!Schema::hasColumn('self_drive_bookings', 'damage_note')) {
                $table->text('damage_note')->nullable()->after('damage_amount');
            }

            if (!Schema::hasColumn('self_drive_bookings', 'final_amount')) {
                $table->decimal('final_amount', 10, 2)->default(0)->after('damage_note');
            }

            if (!Schema::hasColumn('self_drive_bookings', 'refund_amount')) {
                $table->decimal('refund_amount', 10, 2)->default(0)->after('final_amount');
            }

            if (!Schema::hasColumn('self_drive_bookings', 'settlement_status')) {
                $table->string('settlement_status')->default('pending')->after('refund_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('self_drive_bookings', function (Blueprint $table) {
            $columns = [
                'booked_hours',
                'hourly_price',
                'minimum_booking_hours',
                'security_deposit',
                'pickup_otp',
                'pickup_otp_verified_at',
                'return_otp',
                'return_otp_verified_at',
                'trip_start_datetime',
                'trip_end_datetime',
                'start_km',
                'end_km',
                'actual_hours',
                'free_km',
                'actual_km',
                'extra_hours',
                'extra_km',
                'extra_hour_amount',
                'extra_km_amount',
                'damage_amount',
                'damage_note',
                'final_amount',
                'refund_amount',
                'settlement_status',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('self_drive_bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};