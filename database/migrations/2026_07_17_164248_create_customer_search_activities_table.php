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
        Schema::create('customer_search_activities', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Unique Identification
            |--------------------------------------------------------------------------
            */

            $table->uuid('uuid')->unique();

            /*
            |--------------------------------------------------------------------------
            | Customer / Guest Information
            |--------------------------------------------------------------------------
            */

            $table
                ->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('mobile', 20)->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();

            $table->string('session_id', 191)->nullable();
            $table->string('device_id', 191)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Application / Device Information
            |--------------------------------------------------------------------------
            */

            $table->string('source', 50)->default('flutter_app');
            $table->string('platform', 30)->nullable();
            $table->string('device_name')->nullable();
            $table->string('operating_system')->nullable();
            $table->string('app_version', 50)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Search Type
            |--------------------------------------------------------------------------
            |
            | Examples:
            | taxi, self_drive, bike_rental
            |
            */

            $table->string('module', 50)->index();

            /*
            |--------------------------------------------------------------------------
            | Service Type
            |--------------------------------------------------------------------------
            |
            | Examples:
            | one_way, round_trip, local, airport, tour,
            | self_drive, bike_rental
            |
            */

            $table->string('service_type', 50)->index();

            /*
            |--------------------------------------------------------------------------
            | Search Stage
            |--------------------------------------------------------------------------
            |
            | Examples:
            | initiated, searched, results_viewed, vehicle_viewed,
            | vehicle_selected, checkout_started, converted, abandoned
            |
            */

            $table->string('stage', 50)->default('searched')->index();

            /*
            |--------------------------------------------------------------------------
            | Pickup Information
            |--------------------------------------------------------------------------
            */

            $table->text('pickup_location')->nullable();
            $table->string('pickup_city')->nullable();
            $table->string('pickup_state')->nullable();
            $table->string('pickup_country')->nullable();
            $table->string('pickup_pincode', 20)->nullable();

            $table->decimal('pickup_latitude', 10, 7)->nullable();
            $table->decimal('pickup_longitude', 10, 7)->nullable();

            $table->string('pickup_place_id', 191)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Drop Information
            |--------------------------------------------------------------------------
            */

            $table->text('drop_location')->nullable();
            $table->string('drop_city')->nullable();
            $table->string('drop_state')->nullable();
            $table->string('drop_country')->nullable();
            $table->string('drop_pincode', 20)->nullable();

            $table->decimal('drop_latitude', 10, 7)->nullable();
            $table->decimal('drop_longitude', 10, 7)->nullable();

            $table->string('drop_place_id', 191)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Airport Information
            |--------------------------------------------------------------------------
            */

            $table->string('airport_name')->nullable();
            $table->string('airport_code', 20)->nullable();
            $table->string('airport_trip_type', 30)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Multi-City / Via Locations
            |--------------------------------------------------------------------------
            |
            | JSON example:
            |
            | [
            |   {
            |     "location": "Mathura",
            |     "latitude": 27.4924,
            |     "longitude": 77.6737,
            |     "waiting_minutes": 60
            |   }
            | ]
            |
            */

            $table->json('via_locations')->nullable();
            $table->unsignedInteger('total_stops')->default(0);

            /*
            |--------------------------------------------------------------------------
            | Search Date and Time
            |--------------------------------------------------------------------------
            */

            $table->dateTime('start_datetime')->nullable();
            $table->dateTime('end_datetime')->nullable();
            $table->dateTime('return_datetime')->nullable();

            $table->unsignedInteger('trip_days')->nullable();
            $table->unsignedInteger('rental_hours')->nullable();
            $table->unsignedInteger('rental_days')->nullable();
            $table->unsignedInteger('rental_weeks')->nullable();
            $table->unsignedInteger('rental_months')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Local Rental / Package Information
            |--------------------------------------------------------------------------
            */

            $table->string('package_name')->nullable();
            $table->unsignedInteger('package_hours')->nullable();
            $table->decimal('package_km', 10, 2)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Vehicle Information
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger('vehicle_category_id')->nullable();
            $table->unsignedBigInteger('vehicle_id')->nullable();

            $table->string('vehicle_category_name')->nullable();
            $table->string('vehicle_name')->nullable();
            $table->string('vehicle_type', 100)->nullable();
            $table->string('fuel_type', 50)->nullable();
            $table->string('transmission_type', 50)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Self Drive / Bike Rental Plan
            |--------------------------------------------------------------------------
            */

            $table->string('plan_type', 50)->nullable();
            $table->string('plan_name')->nullable();

            $table->unsignedInteger('minimum_hours')->nullable();
            $table->unsignedInteger('included_km')->nullable();

            $table->decimal('price_per_hour', 12, 2)->nullable();
            $table->decimal('price_per_day', 12, 2)->nullable();
            $table->decimal('price_per_week', 12, 2)->nullable();
            $table->decimal('price_per_month', 12, 2)->nullable();

            $table->decimal('weekly_discount_percent', 5, 2)->nullable();
            $table->decimal('monthly_discount_percent', 5, 2)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Bike Rental Information
            |--------------------------------------------------------------------------
            */

            $table->string('helmet_option', 50)->nullable();
            $table->unsignedInteger('helmet_quantity')->default(0);
            $table->decimal('helmet_charge', 12, 2)->default(0);
            $table->decimal('security_deposit', 12, 2)->default(0);

            /*
            |--------------------------------------------------------------------------
            | Route Information
            |--------------------------------------------------------------------------
            */

            $table->decimal('estimated_distance_km', 12, 2)->nullable();
            $table->unsignedInteger('estimated_duration_minutes')->nullable();

            $table->decimal('minimum_km', 12, 2)->nullable();
            $table->decimal('billable_km', 12, 2)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Fare Information
            |--------------------------------------------------------------------------
            */

            $table->char('currency', 3)->default('INR');

            $table->decimal('base_fare', 12, 2)->nullable();
            $table->decimal('estimated_amount', 12, 2)->nullable();
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('coupon_discount', 12, 2)->default(0);

            $table->decimal('driver_allowance', 12, 2)->default(0);
            $table->decimal('toll_amount', 12, 2)->default(0);
            $table->decimal('parking_amount', 12, 2)->default(0);
            $table->decimal('state_tax_amount', 12, 2)->default(0);
            $table->decimal('waiting_charge', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);

            $table->decimal('grand_total', 12, 2)->nullable();

            $table->boolean('is_all_inclusive')->default(false);

            /*
            |--------------------------------------------------------------------------
            | Coupon Information
            |--------------------------------------------------------------------------
            */

            $table->string('coupon_code', 100)->nullable();
            $table->unsignedBigInteger('coupon_id')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Search Result Information
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger('result_count')->nullable();
            $table->boolean('has_available_vehicle')->nullable();

            $table->decimal('minimum_result_price', 12, 2)->nullable();
            $table->decimal('maximum_result_price', 12, 2)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Conversion Information
            |--------------------------------------------------------------------------
            */

            $table->string('search_status', 50)
                ->default('active')
                ->index();

            $table->string('checkout_status', 50)
                ->default('not_started')
                ->index();

            $table->string('payment_status', 50)
                ->default('not_started')
                ->index();

            $table->boolean('is_converted')->default(false)->index();
            $table->boolean('is_abandoned')->default(false)->index();

            $table->string('booking_type', 100)->nullable();
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->string('booking_number', 100)->nullable();

            $table->dateTime('checkout_started_at')->nullable();
            $table->dateTime('payment_started_at')->nullable();
            $table->dateTime('converted_at')->nullable();
            $table->dateTime('abandoned_at')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Customer Intent / CRM
            |--------------------------------------------------------------------------
            */

            $table->unsignedTinyInteger('intent_score')->default(0)->index();

            $table->string('priority', 20)
                ->default('low')
                ->index();

            $table->string('lead_status', 50)
                ->default('new')
                ->index();

            $table->text('lead_notes')->nullable();
            $table->dateTime('follow_up_at')->nullable();

            $table
                ->foreignId('assigned_to')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Admin / Communication Notification Status
            |--------------------------------------------------------------------------
            */

            $table->boolean('admin_notified')->default(false)->index();
            $table->boolean('whatsapp_notified')->default(false);
            $table->boolean('sms_notified')->default(false);
            $table->boolean('push_notified')->default(false);
            $table->boolean('email_notified')->default(false);

            $table->dateTime('admin_notified_at')->nullable();
            $table->dateTime('whatsapp_notified_at')->nullable();
            $table->dateTime('sms_notified_at')->nullable();
            $table->dateTime('push_notified_at')->nullable();
            $table->dateTime('email_notified_at')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Flexible JSON Data
            |--------------------------------------------------------------------------
            */

            $table->json('fare_breakdown')->nullable();
            $table->json('filters')->nullable();
            $table->json('search_data')->nullable();
            $table->json('metadata')->nullable();
            $table->json('utm_data')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Activity Relation
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger('customer_activity_id')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Time Information
            |--------------------------------------------------------------------------
            */

            $table->dateTime('searched_at')->nullable();
            $table->dateTime('last_activity_at')->nullable();
            $table->dateTime('expires_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | Foreign Keys
            |--------------------------------------------------------------------------
            */

            $table
                ->foreign('customer_activity_id', 'csa_activity_fk')
                ->references('id')
                ->on('customer_activities')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Performance Indexes
            |--------------------------------------------------------------------------
            */

            $table->index(
                ['user_id', 'searched_at'],
                'csa_user_search_idx'
            );

            $table->index(
                ['mobile', 'searched_at'],
                'csa_mobile_search_idx'
            );

            $table->index(
                ['session_id', 'searched_at'],
                'csa_session_search_idx'
            );

            $table->index(
                ['module', 'service_type', 'searched_at'],
                'csa_service_search_idx'
            );

            $table->index(
                ['pickup_city', 'drop_city'],
                'csa_route_idx'
            );

            $table->index(
                ['priority', 'lead_status'],
                'csa_lead_priority_idx'
            );

            $table->index(
                ['is_abandoned', 'follow_up_at'],
                'csa_abandoned_followup_idx'
            );

            $table->index(
                ['booking_type', 'booking_id'],
                'csa_booking_idx'
            );

            $table->index(
                ['vehicle_category_id', 'vehicle_id'],
                'csa_vehicle_idx'
            );

            $table->index(
                ['created_at', 'is_converted'],
                'csa_conversion_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_search_activities');
    }
};