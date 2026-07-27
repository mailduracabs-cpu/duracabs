<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('vehicles')) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Core service columns
        |--------------------------------------------------------------------------
        |
        | service_type:
        | - taxi
        | - self_drive
        | - bike_rental
        |
        | vehicle_type:
        | - car
        | - bike
        | - scooter
        |
        */

        Schema::table('vehicles', function (Blueprint $table): void {
            if (! Schema::hasColumn('vehicles', 'service_type')) {
                $table
                    ->string('service_type', 30)
                    ->nullable()
                    ->default('taxi')
                    ->index('vehicles_service_type_idx');
            }

            if (! Schema::hasColumn('vehicles', 'vehicle_type')) {
                $table
                    ->string('vehicle_type', 30)
                    ->nullable()
                    ->default('car')
                    ->index('vehicles_vehicle_type_idx');
            }
        });

        /*
        |--------------------------------------------------------------------------
        | Rental pricing columns
        |--------------------------------------------------------------------------
        */

        Schema::table('vehicles', function (Blueprint $table): void {
            if (! Schema::hasColumn('vehicles', 'daily_price')) {
                $table
                    ->decimal('daily_price', 12, 2)
                    ->nullable()
                    ->default(0);
            }

            if (! Schema::hasColumn('vehicles', 'weekly_price')) {
                $table
                    ->decimal('weekly_price', 12, 2)
                    ->nullable()
                    ->default(0);
            }

            if (! Schema::hasColumn('vehicles', 'monthly_price')) {
                $table
                    ->decimal('monthly_price', 12, 2)
                    ->nullable()
                    ->default(0);
            }

            if (! Schema::hasColumn('vehicles', 'free_km')) {
                $table
                    ->decimal('free_km', 10, 2)
                    ->nullable()
                    ->default(0);
            }

            if (! Schema::hasColumn('vehicles', 'extra_km_rate')) {
                $table
                    ->decimal('extra_km_rate', 10, 2)
                    ->nullable()
                    ->default(0);
            }

            if (! Schema::hasColumn('vehicles', 'extra_hour_rate')) {
                $table
                    ->decimal('extra_hour_rate', 10, 2)
                    ->nullable()
                    ->default(0);
            }
        });

        /*
        |--------------------------------------------------------------------------
        | Bike Rental fields
        |--------------------------------------------------------------------------
        */

        Schema::table('vehicles', function (Blueprint $table): void {
            if (! Schema::hasColumn('vehicles', 'bike_category')) {
                $table
                    ->string('bike_category', 50)
                    ->nullable()
                    ->index('vehicles_bike_category_idx');
            }

            if (! Schema::hasColumn('vehicles', 'engine_cc')) {
                $table
                    ->unsignedInteger('engine_cc')
                    ->nullable();
            }

            if (! Schema::hasColumn('vehicles', 'gear_type')) {
                $table
                    ->string('gear_type', 30)
                    ->nullable();
            }

            if (! Schema::hasColumn('vehicles', 'helmet_available')) {
                $table
                    ->boolean('helmet_available')
                    ->default(false);
            }

            if (! Schema::hasColumn('vehicles', 'included_helmets')) {
                $table
                    ->unsignedTinyInteger('included_helmets')
                    ->default(0);
            }

            if (! Schema::hasColumn('vehicles', 'maximum_helmets')) {
                $table
                    ->unsignedTinyInteger('maximum_helmets')
                    ->default(2);
            }

            if (! Schema::hasColumn('vehicles', 'helmet_charge')) {
                $table
                    ->decimal('helmet_charge', 10, 2)
                    ->default(100);
            }

            if (! Schema::hasColumn('vehicles', 'fuel_capacity')) {
                $table
                    ->decimal('fuel_capacity', 8, 2)
                    ->nullable();
            }

            if (! Schema::hasColumn('vehicles', 'mileage')) {
                $table
                    ->decimal('mileage', 8, 2)
                    ->nullable();
            }
        });

        /*
        |--------------------------------------------------------------------------
        | Availability and booking rules
        |--------------------------------------------------------------------------
        */

        Schema::table('vehicles', function (Blueprint $table): void {
            if (! Schema::hasColumn('vehicles', 'minimum_booking_hours')) {
                $table
                    ->unsignedInteger('minimum_booking_hours')
                    ->default(1);
            }

            if (! Schema::hasColumn('vehicles', 'maximum_booking_hours')) {
                $table
                    ->unsignedInteger('maximum_booking_hours')
                    ->nullable();
            }

            if (! Schema::hasColumn('vehicles', 'security_deposit')) {
                $table
                    ->decimal('security_deposit', 12, 2)
                    ->default(0);
            }

            if (! Schema::hasColumn('vehicles', 'is_live')) {
                $table
                    ->boolean('is_live')
                    ->default(false)
                    ->index('vehicles_is_live_idx');
            }

            if (! Schema::hasColumn('vehicles', 'is_verified')) {
                $table
                    ->boolean('is_verified')
                    ->default(false)
                    ->index('vehicles_is_verified_idx');
            }
        });

        $this->backfillExistingVehicles();
    }

    /**
     * Preserve existing Taxi and Self Drive records.
     */
    private function backfillExistingVehicles(): void
    {
        /*
         * Every existing vehicle is treated as a car unless its vehicle type
         * was already assigned.
         */
        DB::table('vehicles')
            ->whereNull('vehicle_type')
            ->update([
                'vehicle_type' => 'car',
            ]);

        /*
         * Detect existing Self Drive vehicles using the strongest available
         * legacy indicators.
         */
        if (Schema::hasColumn('vehicles', 'is_self_drive')) {
            DB::table('vehicles')
                ->where('is_self_drive', true)
                ->update([
                    'service_type' => 'self_drive',
                    'vehicle_type' => 'car',
                ]);
        }

        if (Schema::hasColumn('vehicles', 'self_drive_enabled')) {
            DB::table('vehicles')
                ->where('self_drive_enabled', true)
                ->update([
                    'service_type' => 'self_drive',
                    'vehicle_type' => 'car',
                ]);
        }

        if (Schema::hasColumn('vehicles', 'self_drive_vendor_id')) {
            DB::table('vehicles')
                ->whereNotNull('self_drive_vendor_id')
                ->update([
                    'service_type' => 'self_drive',
                    'vehicle_type' => 'car',
                ]);
        }

        /*
         * Current Self Drive system uses hourly pricing. Vehicles having
         * rental pricing and a security deposit are therefore preserved as
         * Self Drive vehicles.
         */
        if (
            Schema::hasColumn('vehicles', 'hourly_price')
            && Schema::hasColumn('vehicles', 'security_deposit')
        ) {
            DB::table('vehicles')
                ->where(function ($query): void {
                    $query
                        ->where('hourly_price', '>', 0)
                        ->orWhere('daily_price', '>', 0);
                })
                ->where('security_deposit', '>', 0)
                ->update([
                    'service_type' => 'self_drive',
                    'vehicle_type' => 'car',
                ]);
        }

        /*
         * Remaining legacy records continue as Taxi vehicles.
         */
        DB::table('vehicles')
            ->whereNull('service_type')
            ->update([
                'service_type' => 'taxi',
            ]);

        /*
         * Bike Rental defaults.
         */
        DB::table('vehicles')
            ->where('service_type', 'bike_rental')
            ->update([
                'security_deposit' => DB::raw(
                    'CASE WHEN security_deposit IS NULL OR security_deposit = 0
                     THEN 2000
                     ELSE security_deposit END'
                ),
                'vehicle_type' => DB::raw(
                    "CASE WHEN vehicle_type IS NULL OR vehicle_type = 'car'
                     THEN 'bike'
                     ELSE vehicle_type END"
                ),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('vehicles')) {
            return;
        }

        Schema::table('vehicles', function (Blueprint $table): void {
            $columns = [
                'service_type',
                'vehicle_type',
                'daily_price',
                'weekly_price',
                'monthly_price',
                'free_km',
                'extra_km_rate',
                'extra_hour_rate',
                'bike_category',
                'engine_cc',
                'gear_type',
                'helmet_available',
                'included_helmets',
                'maximum_helmets',
                'helmet_charge',
                'fuel_capacity',
                'mileage',
                'minimum_booking_hours',
                'maximum_booking_hours',
                'security_deposit',
                'is_live',
                'is_verified',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('vehicles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};