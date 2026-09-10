<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Self Drive Vendor Payouts
        |--------------------------------------------------------------------------
        */

        Schema::create('self_drive_vendor_payouts', function (Blueprint $table) {
            $table->id();

            $table->string('payout_no', 50)->unique();

            $table->unsignedBigInteger('transporter_profile_id');

            $table->date('period_from');
            $table->date('period_to');

            $table->unsignedInteger('total_booking_units')
                ->default(0);

            $table->decimal(
                'gross_booking_amount',
                12,
                2
            )->default(0);

            $table->decimal(
                'payout_amount',
                12,
                2
            )->default(0);

            $table->decimal(
                'paid_amount',
                12,
                2
            )->default(0);

            $table->decimal(
                'remaining_amount',
                12,
                2
            )->default(0);

            $table->enum('status', [
                'draft',
                'pending',
                'partial',
                'paid',
                'cancelled',
            ])->default('draft');

            $table->string(
                'payment_method',
                50
            )->nullable();

            $table->string(
                'payment_reference',
                255
            )->nullable();

            $table->timestamp('paid_at')
                ->nullable();

            $table->text('notes')
                ->nullable();

            $table->timestamps();

            /*
             * Short custom FK name.
             */
            $table->foreign(
                'transporter_profile_id',
                'sdvp_vendor_fk'
            )
                ->references('id')
                ->on('fleet_transporter_profiles')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->index(
                [
                    'transporter_profile_id',
                    'period_from',
                    'period_to',
                ],
                'sdvp_period_idx'
            );

            $table->index(
                'status',
                'sdvp_status_idx'
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Self Drive Vendor Payout Items
        |--------------------------------------------------------------------------
        */

        Schema::create('self_drive_vendor_payout_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger(
                'self_drive_vendor_payout_id'
            );

            $table->unsignedBigInteger(
                'self_drive_booking_id'
            );

            $table->unsignedBigInteger(
                'vehicle_id'
            );

            /*
             * Booking snapshot.
             */
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');

            $table->unsignedInteger(
                'booked_hours'
            )->default(0);

            /*
             * 24-hour units.
             *
             * 24h = 1
             * 48h = 2
             * 49h = 3
             */
            $table->unsignedInteger(
                'booking_units'
            )->default(1);

            /*
             * Customer's 24-hour rate snapshot.
             */
            $table->decimal(
                'customer_daily_rate',
                12,
                2
            )->default(0);

            /*
             * Commission snapshot.
             */
            $table->decimal(
                'commission_percentage',
                5,
                2
            )->default(0);

            /*
             * Vendor earning per 24h unit.
             */
            $table->decimal(
                'vendor_rate_per_24h',
                12,
                2
            )->default(0);

            /*
             * Customer booking amount snapshot.
             * Reporting only.
             */
            $table->decimal(
                'customer_booking_amount',
                12,
                2
            )->default(0);

            /*
             * Final payout for this booking.
             */
            $table->decimal(
                'payout_amount',
                12,
                2
            )->default(0);

            $table->timestamps();

            /*
             * Payout header FK
             */
            $table->foreign(
                'self_drive_vendor_payout_id',
                'sdvpi_payout_fk'
            )
                ->references('id')
                ->on('self_drive_vendor_payouts')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
             * Self Drive booking FK
             */
            $table->foreign(
                'self_drive_booking_id',
                'sdvpi_booking_fk'
            )
                ->references('id')
                ->on('self_drive_bookings')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
             * Vehicle FK
             */
            $table->foreign(
                'vehicle_id',
                'sdvpi_vehicle_fk'
            )
                ->references('id')
                ->on('vehicles')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
             * One booking can only be included
             * in one vendor payout.
             */
            $table->unique(
                'self_drive_booking_id',
                'sdvpi_booking_unique'
            );

            $table->index(
                [
                    'self_drive_vendor_payout_id',
                    'vehicle_id',
                ],
                'sdvpi_payout_vehicle_idx'
            );

            $table->index(
                'vehicle_id',
                'sdvpi_vehicle_idx'
            );
        });
    }

    public function down(): void
    {
        /*
         * Child table first because it has FKs
         * to payout, booking and vehicle.
         */
        Schema::dropIfExists(
            'self_drive_vendor_payout_items'
        );

        Schema::dropIfExists(
            'self_drive_vendor_payouts'
        );
    }
};