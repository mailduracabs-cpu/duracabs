<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('self_drive_bookings', function (Blueprint $table): void {
            $table->decimal('customer_live_lat', 10, 7)
                ->nullable()
                ->after('pickup_longitude');

            $table->decimal('customer_live_lng', 11, 7)
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
        Schema::table('self_drive_bookings', function (Blueprint $table): void {
            $table->dropColumn([
                'customer_live_lat',
                'customer_live_lng',
                'location_sharing_enabled',
                'customer_live_location_updated_at',
            ]);
        });
    }
};