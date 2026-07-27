<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bike_rental_bookings')) {
            Schema::drop('bike_rental_bookings');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('bike_rental_bookings')) {
            Schema::create('bike_rental_bookings', function (Blueprint $table) {
                $table->id();

                $table->string('booking_no')->unique();

                $table->foreignId('customer_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->foreignId('vehicle_id')
                    ->nullable()
                    ->constrained('vehicles')
                    ->nullOnDelete();

                $table->foreignId('transporter_profile_id')
                    ->nullable()
                    ->constrained('fleet_transporter_profiles')
                    ->nullOnDelete();

                $table->string('pickup_location')->nullable();
                $table->decimal('pickup_latitude', 10, 7)->nullable();
                $table->decimal('pickup_longitude', 10, 7)->nullable();

                $table->dateTime('start_datetime')->nullable();
                $table->dateTime('end_datetime')->nullable();

                $table->unsignedInteger('booked_hours')->nullable();
                $table->string('plan_type')->nullable();

                $table->decimal('base_rent', 12, 2)->default(0);
                $table->string('helmet_option')->nullable();
                $table->decimal('helmet_charge', 10, 2)->default(0);
                $table->decimal('security_deposit', 12, 2)->default(0);
                $table->decimal('discount_amount', 12, 2)->default(0);
                $table->decimal('total_amount', 12, 2)->default(0);
                $table->decimal('paid_amount', 12, 2)->default(0);
                $table->decimal('remaining_amount', 12, 2)->default(0);

                $table->string('payment_type')->nullable();
                $table->string('payment_method')->nullable();
                $table->string('payment_reference')->nullable();
                $table->string('payment_status')->default('pending');
                $table->string('booking_status')->default('pending');

                $table->text('customer_note')->nullable();

                $table->timestamps();

                $table->index('booking_status');
                $table->index('payment_status');
                $table->index(['vehicle_id', 'start_datetime', 'end_datetime']);
            });
        }
    }
};