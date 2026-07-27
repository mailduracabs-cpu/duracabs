<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_transporter_reviews', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('booking_id')->nullable();

            $table->foreignId('transporter_profile_id')
                ->constrained('fleet_transporter_profiles')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('customer_id')->nullable();

            $table->enum('review_by', [
                'customer',
                'transporter',
                'admin',
            ])->default('customer');

            $table->tinyInteger('rating')->default(5);

            $table->text('review')->nullable();

            $table->timestamps();

            $table->index('booking_id');
            $table->index('customer_id');
            $table->index('review_by');
            $table->index('rating');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_transporter_reviews');
    }
};