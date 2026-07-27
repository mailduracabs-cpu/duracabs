<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->nullable();
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete()->nullable();
            $table->foreignId('transporter_id')->constrained('users')->cascadeOnDelete()->nullable();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete()->nullable();
            $table->decimal('grand_total', 10, 2)->nullable();
            $table->decimal('coupon_value', 10, 2)->nullable();
            $table->string('coupon_name')->nullable();
            $table->decimal('tax', 10, 2)->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('total_km')->nullable();
            $table->date('date')->nullable();;	//here is date	
            $table->date('dateTo')->nullable();;	//here is date To
            $table->time('time')->nullable();;	//here is time
            $table->time('endTime')->nullable();
            $table->string('booking_from')->nullable();
            $table->string('booking_to')->nullable();
            $table->enum('status',['new', 'start','modification', 'confirm','reconfirmation','cancelled','closed','refund'])->default('new');
            $table->enum('plan',['none','4 Hour / 40 Km', '8 Hour / 80 Km','12 Hour / 120 Km'])->default('none');
            $table->string('currency')->nullable();
            $table->string('cityFrom')->nullable();
            $table->string('cityTo')->nullable();
            $table->string('productName')->nullable();
            $table->string('image')->nullable();
            $table->decimal('shipping_ammount',10,1)->nullable();
            $table->string('taxi_type')->nullable();
            $table->text('notes')->nullable();
            $table->string('ride_type')->nullable();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
