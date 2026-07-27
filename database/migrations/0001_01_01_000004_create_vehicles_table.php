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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('vehicle_number')->nullable();
            $table->string('chassis_number')->nullable();
            $table->string('insurance_number')->nullable();
            $table->string('owner_name')->nullable();
            $table->string('car_company_name')->nullable();
            $table->string('car_classification')->nullable();
            $table->string('car_color')->nullable();
            $table->string('insurance_company_name')->nullable();
            $table->string('rc_image')->nullable();
            $table->string('insurance_image')->nullable();
            $table->string('polution_image')->nullable();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->nullable();
            $table->boolean('is_active')->default(false);
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
