<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('self_drive_bookings', function (Blueprint $table) {
            $table->text('delivery_address')->nullable()->after('pickup_location');
            $table->decimal('delivery_price', 10, 2)->default(0)->after('delivery_address');

            $table->text('pickup_address')->nullable()->after('delivery_price');
            $table->decimal('pickup_price', 10, 2)->default(0)->after('pickup_address');

            $table->decimal('gst_percent', 5, 2)->default(18)->after('total_amount');
            $table->decimal('gst_amount', 10, 2)->default(0)->after('gst_percent');

            $table->decimal('discount_amount', 10, 2)->default(0)->after('gst_amount');

            $table->decimal('manual_price', 10, 2)->nullable()->after('discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('self_drive_bookings', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_address',
                'delivery_price',
                'pickup_address',
                'pickup_price',
                'gst_percent',
                'gst_amount',
                'discount_amount',
                'manual_price',
            ]);
        });
    }
};