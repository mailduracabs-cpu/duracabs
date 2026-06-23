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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete()->nullable();
            $table->foreignId('booking_to')->constrained('brands')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('ride_type')->nullable();
            $table->decimal('price',10,2);
            $table->decimal('max_price',10,2);
            $table->decimal('km_limit',10,2);
            $table->decimal('hr_limit',10,2);
            $table->decimal('extra_km_charge',10,2);
            $table->decimal('extra_hr_charge',10,2);
            $table->decimal('toll_tax',10,2);
            $table->decimal('border_tax',10,2);
            $table->decimal('driver_allowances',10,2);
            $table->json('images')->nullable();
            $table->longText('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('in_stock')->default(true);
            $table->boolean('on_sale')->default(false);
            $table->enum('plan',['none','4 Hour / 40 Km', '8 Hour / 80 Km','12 Hour / 120 Km'])->default('none');
            $table->string('meta_title');
            $table->string('meta_description');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
