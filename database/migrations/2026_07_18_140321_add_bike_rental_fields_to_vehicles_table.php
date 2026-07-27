<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table): void {
            if (! Schema::hasColumn('vehicles', 'service_type')) {
                $table->string('service_type', 50)
                    ->nullable()
                    ->index()
                    ->after('id');
            }

            if (! Schema::hasColumn('vehicles', 'vehicle_category')) {
                $table->string('vehicle_category', 50)
                    ->nullable()
                    ->after('service_type');
            }

            if (! Schema::hasColumn('vehicles', 'hourly_price')) {
                $table->decimal('hourly_price', 10, 2)
                    ->nullable();
            }

            if (! Schema::hasColumn('vehicles', 'daily_price')) {
                $table->decimal('daily_price', 10, 2)
                    ->nullable();
            }

            if (! Schema::hasColumn('vehicles', 'weekly_discount')) {
                $table->decimal('weekly_discount', 5, 2)
                    ->default(20);
            }

            if (! Schema::hasColumn('vehicles', 'monthly_discount')) {
                $table->decimal('monthly_discount', 5, 2)
                    ->default(30);
            }

            if (! Schema::hasColumn('vehicles', 'security_deposit')) {
                $table->decimal('security_deposit', 10, 2)
                    ->default(2000);
            }

            if (! Schema::hasColumn('vehicles', 'minimum_booking_hours')) {
                $table->unsignedInteger('minimum_booking_hours')
                    ->default(1);
            }

            if (! Schema::hasColumn('vehicles', 'helmet_option')) {
                $table->string('helmet_option', 50)
                    ->nullable();
            }

            if (! Schema::hasColumn('vehicles', 'bike_engine_cc')) {
                $table->unsignedInteger('bike_engine_cc')
                    ->nullable();
            }

            if (! Schema::hasColumn('vehicles', 'bike_type')) {
                $table->string('bike_type', 80)
                    ->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table): void {
            $columns = [
                'service_type',
                'vehicle_category',
                'hourly_price',
                'daily_price',
                'weekly_discount',
                'monthly_discount',
                'security_deposit',
                'minimum_booking_hours',
                'helmet_option',
                'bike_engine_cc',
                'bike_type',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('vehicles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};