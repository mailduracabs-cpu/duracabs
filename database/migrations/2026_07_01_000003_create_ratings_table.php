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
        if (!Schema::hasTable('ratings')) {

            Schema::create('ratings', function (Blueprint $table) {

                $table->id();

                $table->unsignedBigInteger('user_id')->index();

                $table->unsignedBigInteger('booking_id')->nullable()->index();

                $table->unsignedBigInteger('driver_id')->nullable()->index();

                $table->unsignedBigInteger('vehicle_id')->nullable()->index();

                // driver | vehicle | trip | self_drive | tour | app
                $table->string('rating_for')->default('trip');

                $table->decimal('rating', 2, 1);

                $table->string('title')->nullable();

                $table->text('review')->nullable();

                $table->json('images')->nullable();

                $table->enum('status', [
                    'pending',
                    'approved',
                    'rejected'
                ])->default('approved');

                $table->text('admin_reply')->nullable();

                $table->json('meta')->nullable();

                $table->timestamps();

            });

        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};