<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Physical vehicles table
        |--------------------------------------------------------------------------
        */

        Schema::table('vehicles', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicles', 'product_id')) {
                $table->unsignedBigInteger('product_id')
                    ->nullable()
                    ->after('id')
                    ->index();
            }

            if (! Schema::hasColumn('vehicles', 'transporter_profile_id')) {
                $table->unsignedBigInteger('transporter_profile_id')
                    ->nullable()
                    ->after('product_id')
                    ->index();
            }

            if (! Schema::hasColumn('vehicles', 'engine_number')) {
                $table->string('engine_number')
                    ->nullable()
                    ->after('chassis_number');
            }

            if (! Schema::hasColumn('vehicles', 'model_name')) {
                $table->string('model_name')
                    ->nullable()
                    ->after('car_company_name');
            }

            if (! Schema::hasColumn('vehicles', 'manufacture_year')) {
                $table->unsignedSmallInteger('manufacture_year')
                    ->nullable()
                    ->after('model_name');
            }

            if (! Schema::hasColumn('vehicles', 'fuel_type')) {
                $table->string('fuel_type', 50)
                    ->nullable()
                    ->after('manufacture_year');
            }

            if (! Schema::hasColumn('vehicles', 'transmission')) {
                $table->string('transmission', 50)
                    ->nullable()
                    ->after('fuel_type');
            }

            if (! Schema::hasColumn('vehicles', 'seats')) {
                $table->unsignedTinyInteger('seats')
                    ->nullable()
                    ->after('transmission');
            }

            if (! Schema::hasColumn('vehicles', 'bags')) {
                $table->unsignedTinyInteger('bags')
                    ->nullable()
                    ->after('seats');
            }

            if (! Schema::hasColumn('vehicles', 'front_image')) {
                $table->string('front_image')
                    ->nullable()
                    ->after('polution_image');
            }

            if (! Schema::hasColumn('vehicles', 'back_image')) {
                $table->string('back_image')
                    ->nullable()
                    ->after('front_image');
            }

            if (! Schema::hasColumn('vehicles', 'interior_image')) {
                $table->string('interior_image')
                    ->nullable()
                    ->after('back_image');
            }

            if (! Schema::hasColumn('vehicles', 'verification_status')) {
                $table->enum('verification_status', [
                    'pending',
                    'approved',
                    'rejected',
                ])
                    ->default('pending')
                    ->after('is_active')
                    ->index();
            }

            if (! Schema::hasColumn('vehicles', 'rejection_reason')) {
                $table->text('rejection_reason')
                    ->nullable()
                    ->after('verification_status');
            }
        });

        /*
        |--------------------------------------------------------------------------
        | Normalize existing registration numbers
        |--------------------------------------------------------------------------
        */

        DB::table('vehicles')
            ->whereNotNull('vehicle_number')
            ->orderBy('id')
            ->each(function ($vehicle): void {
                $normalized = strtoupper(
                    preg_replace('/[^A-Za-z0-9]/', '', $vehicle->vehicle_number)
                );

                DB::table('vehicles')
                    ->where('id', $vehicle->id)
                    ->update([
                        'vehicle_number' => $normalized,
                    ]);
            });

        /*
        |--------------------------------------------------------------------------
        | Add unique registration index
        |--------------------------------------------------------------------------
        |
        | Existing duplicates must be removed before this migration runs.
        |
        */

        Schema::table('vehicles', function (Blueprint $table) {
            $table->unique(
                'vehicle_number',
                'vehicles_vehicle_number_unique'
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Self-drive business listing
        |--------------------------------------------------------------------------
        */

        Schema::table('self_drive_vehicles', function (Blueprint $table) {
            if (! Schema::hasColumn('self_drive_vehicles', 'vehicle_id')) {
                $table->unsignedBigInteger('vehicle_id')
                    ->nullable()
                    ->after('id')
                    ->index();
            }

            if (! Schema::hasColumn('self_drive_vehicles', 'daily_price')) {
                $table->decimal('daily_price', 10, 2)
                    ->nullable()
                    ->after('hourly_price');
            }

            if (! Schema::hasColumn('self_drive_vehicles', 'approval_status')) {
                $table->enum('approval_status', [
                    'pending',
                    'approved',
                    'rejected',
                ])
                    ->default('pending')
                    ->after('minimum_booking_hours')
                    ->index();
            }

            if (! Schema::hasColumn('self_drive_vehicles', 'rejection_reason')) {
                $table->text('rejection_reason')
                    ->nullable()
                    ->after('approval_status');
            }
        });

        /*
        |--------------------------------------------------------------------------
        | Foreign keys
        |--------------------------------------------------------------------------
        */

        Schema::table('vehicles', function (Blueprint $table) {
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->nullOnDelete();

            $table->foreign('transporter_profile_id')
                ->references('id')
                ->on('fleet_transporter_profiles')
                ->nullOnDelete();
        });

        Schema::table('self_drive_vehicles', function (Blueprint $table) {
            $table->foreign('vehicle_id')
                ->references('id')
                ->on('vehicles')
                ->cascadeOnDelete();

            $table->unique(
                'vehicle_id',
                'self_drive_vehicle_id_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('self_drive_vehicles', function (Blueprint $table) {
            $table->dropUnique('self_drive_vehicle_id_unique');
            $table->dropForeign(['vehicle_id']);

            $table->dropColumn([
                'vehicle_id',
                'daily_price',
                'approval_status',
                'rejection_reason',
            ]);
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropUnique('vehicles_vehicle_number_unique');
            $table->dropForeign(['product_id']);
            $table->dropForeign(['transporter_profile_id']);

            $table->dropColumn([
                'product_id',
                'transporter_profile_id',
                'engine_number',
                'model_name',
                'manufacture_year',
                'fuel_type',
                'transmission',
                'seats',
                'bags',
                'front_image',
                'back_image',
                'interior_image',
                'verification_status',
                'rejection_reason',
            ]);
        });
    }
};