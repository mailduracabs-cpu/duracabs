<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_booking_otps', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('fleet_id');

            $table->foreignId('transporter_profile_id')
                ->constrained('fleet_transporter_profiles')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('customer_id');

            $table->enum('otp_type', [
                'pickup',
                'drop',
            ]);

            $table->string('otp_code', 10);

            $table->enum('status', [
                'pending',
                'verified',
                'expired',
                'cancelled',
            ])->default('pending');

            $table->timestamp('expires_at')->nullable();
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();

            $table->index('booking_id');
            $table->index('fleet_id');
            $table->index('customer_id');
            $table->index('otp_type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_booking_otps');
    }
};