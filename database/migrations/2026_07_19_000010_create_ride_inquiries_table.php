<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ride_inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('inquiry_no', 32)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('mobile', 15)->index();
            $table->string('customer_name')->nullable();
            $table->unsignedBigInteger('pickup_city_id')->nullable()->index();
            $table->unsignedBigInteger('drop_city_id')->nullable()->index();
            $table->string('pickup_name')->nullable();
            $table->string('drop_name')->nullable();
            $table->string('trip_type', 30)->default('one_way')->index();
            $table->date('travel_date')->nullable()->index();
            $table->time('travel_time')->nullable();
            $table->date('return_date')->nullable();
            $table->decimal('estimated_fare_from', 12, 2)->nullable();
            $table->string('source', 80)->default('seo_route_page')->index();
            $table->text('landing_url')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('gclid', 255)->nullable();
            $table->string('fbclid', 255)->nullable();
            $table->string('status', 30)->default('new')->index();
            $table->unsignedBigInteger('assigned_to')->nullable()->index();
            $table->timestamp('last_follow_up_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->index(
                ['mobile', 'trip_type', 'travel_date'],
                'ride_inquiry_mobile_trip_date_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ride_inquiries');
    }
};
