<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_transporter_cities', function (Blueprint $table) {

            $table->id();

            $table->foreignId('transporter_profile_id')
                ->constrained('fleet_transporter_profiles')
                ->cascadeOnDelete();

            $table->foreignId('city_id')
                ->constrained('master_cities')
                ->cascadeOnDelete();

            $table->boolean('status')->default(true);

            $table->timestamps();

            $table->unique(['transporter_profile_id', 'city_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_transporter_cities');
    }
};