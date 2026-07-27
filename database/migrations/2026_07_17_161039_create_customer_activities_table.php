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
        Schema::create('customer_activities', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Public Identifier
            |--------------------------------------------------------------------------
            |
            | Admin panel, APIs aur logs me internal numeric ID expose karne ke
            | bajay UUID use kiya jayega.
            |
            */
            $table->uuid('uuid')->unique();

            /*
            |--------------------------------------------------------------------------
            | Customer Information
            |--------------------------------------------------------------------------
            |
            | user_id nullable rakha gaya hai kyunki guest user bhi app me search
            | kar sakta hai. Mobile/session ke through guest activity track hogi.
            |
            */
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('mobile', 25)->nullable()->index();
            $table->string('customer_name')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Session and Device Information
            |--------------------------------------------------------------------------
            */
            $table->string('session_id', 120)->nullable()->index();
            $table->string('device_id', 191)->nullable()->index();
            $table->string('device_token', 500)->nullable();

            $table->string('platform', 30)->nullable();
            $table->string('device_name')->nullable();
            $table->string('operating_system')->nullable();
            $table->string('app_version', 50)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Activity Information
            |--------------------------------------------------------------------------
            |
            | event examples:
            | otp_requested
            | otp_verified
            | user_registered
            | user_login
            | one_way_search
            | round_trip_search
            | local_search
            | self_drive_search
            | bike_rental_search
            | vehicle_viewed
            | checkout_started
            | payment_started
            | payment_failed
            | booking_created
            |
            */
            $table->string('event', 100)->index();

            /*
            | Module examples:
            | auth, taxi, self_drive, bike_rental, booking, payment, profile
            */
            $table->string('module', 60)->nullable()->index();

            /*
            | Service examples:
            | one_way, round_trip, local, airport, tour,
            | self_drive, bike_rental
            */
            $table->string('service_type', 60)->nullable()->index();

            /*
            | Activity stage examples:
            | opened, searched, viewed, selected, checkout,
            | payment, booked, cancelled, completed
            */
            $table->string('stage', 60)->nullable()->index();

            /*
            |--------------------------------------------------------------------------
            | Related Record
            |--------------------------------------------------------------------------
            |
            | related_type:
            | booking, self_drive_booking, bike_booking, vehicle,
            | vehicle_category, payment etc.
            |
            */
            $table->string('related_type', 100)->nullable()->index();
            $table->unsignedBigInteger('related_id')->nullable()->index();

            /*
            |--------------------------------------------------------------------------
            | Search / Route Summary
            |--------------------------------------------------------------------------
            |
            | Important information duplicated as searchable columns.
            | Detailed data payload JSON me rahega.
            |
            */
            $table->string('pickup_location')->nullable();
            $table->string('pickup_city', 100)->nullable()->index();
            $table->decimal('pickup_latitude', 10, 7)->nullable();
            $table->decimal('pickup_longitude', 10, 7)->nullable();

            $table->string('drop_location')->nullable();
            $table->string('drop_city', 100)->nullable()->index();
            $table->decimal('drop_latitude', 10, 7)->nullable();
            $table->decimal('drop_longitude', 10, 7)->nullable();

            $table->dateTime('start_datetime')->nullable()->index();
            $table->dateTime('end_datetime')->nullable();
            $table->dateTime('return_datetime')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Vehicle / Plan Information
            |--------------------------------------------------------------------------
            */
            $table->unsignedBigInteger('vehicle_category_id')->nullable()->index();
            $table->unsignedBigInteger('vehicle_id')->nullable()->index();
            $table->string('vehicle_name')->nullable();

            /*
            | plan_type examples:
            | hourly, daily, weekly, monthly, 8hr_80km, 12hr_120km
            */
            $table->string('plan_type', 100)->nullable()->index();
            $table->unsignedInteger('passengers')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Pricing Information
            |--------------------------------------------------------------------------
            */
            $table->decimal('estimated_distance', 10, 2)->nullable();
            $table->decimal('estimated_amount', 12, 2)->nullable()->index();

            /*
            |--------------------------------------------------------------------------
            | Lead Intelligence
            |--------------------------------------------------------------------------
            |
            | intent_score will help admin prioritize customers.
            |
            */
            $table->unsignedInteger('intent_score')->default(0)->index();

            /*
            | priority:
            | low, normal, high, urgent
            */
            $table->string('priority', 20)->default('normal')->index();

            /*
            | lead_status:
            | new, active, contacted, converted, lost, ignored
            */
            $table->string('lead_status', 30)->default('new')->index();

            /*
            |--------------------------------------------------------------------------
            | Metadata
            |--------------------------------------------------------------------------
            */
            $table->json('data')->nullable();
            $table->json('utm_data')->nullable();

            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            $table->string('source', 50)->default('flutter_app')->index();

            /*
            |--------------------------------------------------------------------------
            | Notification State
            |--------------------------------------------------------------------------
            |
            | Admin notification, WhatsApp aur SMS generate hua ya nahi.
            |
            */
            $table->boolean('admin_notified')->default(false)->index();
            $table->boolean('whatsapp_notified')->default(false);
            $table->boolean('sms_notified')->default(false);
            $table->boolean('push_notified')->default(false);

            $table->timestamp('occurred_at')->nullable()->index();
            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Compound Indexes
            |--------------------------------------------------------------------------
            */
            $table->index(
                ['user_id', 'event', 'created_at'],
                'customer_activity_user_event_idx'
            );

            $table->index(
                ['service_type', 'stage', 'created_at'],
                'customer_activity_service_stage_idx'
            );

            $table->index(
                ['lead_status', 'priority', 'created_at'],
                'customer_activity_lead_priority_idx'
            );

            /*
            |--------------------------------------------------------------------------
            | Foreign Key
            |--------------------------------------------------------------------------
            |
            | User delete hone par activity history preserve rahegi,
            | isliye user_id null kar diya jayega.
            |
            */
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_activities');
    }
};