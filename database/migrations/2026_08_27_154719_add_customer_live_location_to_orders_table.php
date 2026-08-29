<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('customer_live_lat', 10, 7)
                ->nullable()
                ->after('booking_to');

            $table->decimal('customer_live_lng', 10, 7)
                ->nullable()
                ->after('customer_live_lat');

            $table->boolean('location_sharing_enabled')
                ->default(false)
                ->after('customer_live_lng');

            $table->timestamp('customer_live_location_updated_at')
                ->nullable()
                ->after('location_sharing_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'customer_live_lat',
                'customer_live_lng',
                'location_sharing_enabled',
                'customer_live_location_updated_at',
            ]);
        });
    }
};