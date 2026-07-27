<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_transporter_services', function (Blueprint $table) {

            $table->id();

            $table->foreignId('transporter_profile_id')
                ->constrained('fleet_transporter_profiles')
                ->cascadeOnDelete();

            $table->enum('service', [
                'self_drive_car',
                'bike_rental',
                'oneway_taxi',
                'airport_transfer',
                'local_rental',
                'round_trip',
                'tour_packages',
            ]);

            $table->boolean('status')->default(true);

            $table->timestamps();

            $table->unique(['transporter_profile_id', 'service']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_transporter_services');
    }
};