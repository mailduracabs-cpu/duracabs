<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('self_drive_vehicles')) {

            Schema::create('self_drive_vehicles', function (Blueprint $table) {

                $table->id();

                // Product (Master Vehicle)
                $table->unsignedBigInteger('product_id')->index();

                // Vendor Office
                $table->unsignedBigInteger('self_drive_vendor_id')->index();

                // Pricing
                $table->decimal('daily_price', 10, 2);
                $table->decimal('security_deposit', 10, 2)->default(0);

                // Vehicle Status
                $table->boolean('is_live')->default(true);
                $table->boolean('is_verified')->default(false);
                $table->boolean('is_active')->default(true);

                // Display Order
                $table->integer('sort_order')->default(0);

                $table->timestamps();

                $table->index(
    ['self_drive_vendor_id', 'is_live', 'is_verified', 'is_active'],
    'sdv_vendor_status_idx'
);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('self_drive_vehicles');
    }
};