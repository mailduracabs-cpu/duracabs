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
        Schema::create('smart_home_blocks', function (Blueprint $table) {
            $table->id();

            // Home Section
            $table->string('block_type', 50);
            // hero, popular_route, featured_vehicle,
            // self_drive, offer, festival

            // Service
            $table->string('service_type', 50)->nullable();
            // one_way, round_trip, local,
            // airport, self_drive, bike

            // Optional Route
            $table->unsignedBigInteger('from_city_id')->nullable();
            $table->unsignedBigInteger('to_city_id')->nullable();

            // Optional Custom Title
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();

            // Behaviour
            $table->boolean('is_dynamic')->default(true);
            $table->boolean('is_active')->default(true);

            // Ordering
            $table->unsignedInteger('priority')->default(1);

            // Schedule
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->timestamps();

            $table->index('block_type');
            $table->index('service_type');
            $table->index('priority');
            $table->index('is_active');
            $table->index(['block_type', 'is_active']);
            $table->index(['service_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('smart_home_blocks');
    }
};