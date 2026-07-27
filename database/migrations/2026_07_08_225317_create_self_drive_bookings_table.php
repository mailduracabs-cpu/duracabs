<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('self_drive_bookings')) {

            Schema::create('self_drive_bookings', function (Blueprint $table) {

                $table->id();

                $table->string('booking_no')->unique();

                $table->unsignedBigInteger('customer_id')->nullable()->index();

                $table->unsignedBigInteger('self_drive_vehicle_id')->index();

                $table->unsignedBigInteger('self_drive_vendor_id')->index();

                $table->string('pickup_location')->nullable();

                $table->decimal('pickup_latitude', 10, 7)->nullable();
                $table->decimal('pickup_longitude', 10, 7)->nullable();

                $table->dateTime('start_datetime');
                $table->dateTime('end_datetime');

                $table->integer('total_days')->default(1);

                $table->decimal('price_per_day', 10, 2)->default(0);

                $table->decimal('total_amount', 10, 2)->default(0);

                $table->enum('status', [
                    'pending',
                    'confirmed',
                    'running',
                    'completed',
                    'cancelled'
                ])->default('pending');

                $table->timestamps();

                $table->index(
                    ['self_drive_vehicle_id', 'status'],
                    'sdb_vehicle_status_idx'
                );

                $table->index(
                    ['start_datetime', 'end_datetime'],
                    'sdb_date_idx'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('self_drive_bookings');
    }
};