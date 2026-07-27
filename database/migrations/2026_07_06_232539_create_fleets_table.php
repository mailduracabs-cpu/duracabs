<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();

            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->string('vehicle_type', 30)->default('car');

            $table->string('name', 180);
            $table->string('slug', 220)->unique();
            $table->string('image')->nullable();

            $table->string('model_similar')->nullable();
            $table->string('fuel_type', 50)->nullable();
            $table->string('transmission', 50)->nullable();
            $table->string('passenger_capacity', 30)->nullable();
            $table->string('luggage_capacity', 30)->nullable();
            $table->decimal('range_km', 10, 2)->default(0);
            $table->boolean('new_vehicle')->default(false);
            $table->boolean('roof_carrier')->default(false);

            $table->string('vehicle_number', 80)->nullable();
            $table->string('chassis_number', 120)->nullable();
            $table->string('engine_number', 120)->nullable();
            $table->string('owner_name')->nullable();
            $table->string('car_color', 80)->nullable();

            $table->string('photo_left')->nullable();
            $table->string('photo_right')->nullable();
            $table->string('photo_front')->nullable();
            $table->string('photo_back')->nullable();
            $table->string('photo_interior')->nullable();

            $table->string('rc_image')->nullable();
            $table->string('insurance_image')->nullable();
            $table->string('pollution_image')->nullable();

            $table->decimal('km_charge', 10, 2)->default(0);
            $table->decimal('hour_charge', 10, 2)->default(0);
            $table->decimal('weekly_charge', 10, 2)->default(0);
            $table->decimal('monthly_charge', 10, 2)->default(0);
            $table->decimal('security_deposit', 10, 2)->default(0);

            $table->string('status', 30)->default('active');

            $table->timestamps();
            $table->softDeletes();

            $table->index('vendor_id');
            $table->index('vehicle_type');
            $table->index('status');
            $table->index('slug');
            $table->index('vehicle_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleets');
    }
};