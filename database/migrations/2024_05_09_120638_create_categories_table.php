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
        Schema::create('categories', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Basic
            |--------------------------------------------------------------------------
            */

            $table->string('name');
            $table->string('slug')->unique();

            $table->string('image')->nullable();

            $table->boolean('is_active')->default(true);

            /*
            |--------------------------------------------------------------------------
            | Service Type
            |--------------------------------------------------------------------------
            */

            $table->enum('service_group', [
                'with_driver',
                'without_driver'
            ])->default('with_driver');

            /*
            |--------------------------------------------------------------------------
            | Vehicle Type
            |--------------------------------------------------------------------------
            */

            $table->enum('vehicle_type', [
                'car',
                'bike',
                'tempo'
            ])->default('car');

            /*
            |--------------------------------------------------------------------------
            | Vehicle Information
            |--------------------------------------------------------------------------
            */

            $table->string('model')->nullable();

            $table->string('passanger_capacity')->nullable();

            $table->string('luggage_capacity')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Taxi Charges
            |--------------------------------------------------------------------------
            */

            $table->decimal('km_charge',10,2)->nullable();

            $table->decimal('driver_charge',10,2)->nullable();

            $table->decimal('range',10,2)->nullable();

            $table->boolean('in_return')->default(false);

            /*
            |--------------------------------------------------------------------------
            | Self Drive Features
            |--------------------------------------------------------------------------
            */

            $table->boolean('security')->default(false);

            $table->boolean('new_vehicle')->default(false);

            $table->boolean('roof_career')->default(false);

            $table->boolean('pet_friendly')->default(false);

            /*
            |--------------------------------------------------------------------------
            | Sorting
            |--------------------------------------------------------------------------
            */

            $table->integer('sort_order')->default(0);

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};