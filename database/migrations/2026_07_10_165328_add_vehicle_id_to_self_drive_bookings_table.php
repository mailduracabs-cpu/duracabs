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
        | Add direct physical vehicle relation
        |--------------------------------------------------------------------------
        */

        Schema::table('self_drive_bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('self_drive_bookings', 'vehicle_id')) {
                $table->unsignedBigInteger('vehicle_id')
                    ->nullable()
                    ->after('customer_id')
                    ->index();
            }
        });

        /*
        |--------------------------------------------------------------------------
        | Backfill old booking records
        |--------------------------------------------------------------------------
        |
        | Agar purani self_drive_vehicles table abhi database me hai aur usme
        | vehicle_id available hai, to existing bookings ko automatically
        | naye vehicles table ke record se connect kar denge.
        |
        */

        if (
            Schema::hasTable('self_drive_vehicles')
            && Schema::hasColumn('self_drive_vehicles', 'vehicle_id')
            && Schema::hasColumn(
                'self_drive_bookings',
                'self_drive_vehicle_id'
            )
        ) {
            DB::statement(
                '
                UPDATE self_drive_bookings AS bookings
                INNER JOIN self_drive_vehicles AS listings
                    ON listings.id = bookings.self_drive_vehicle_id
                SET bookings.vehicle_id = listings.vehicle_id
                WHERE bookings.vehicle_id IS NULL
                  AND listings.vehicle_id IS NOT NULL
                '
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Add foreign key
        |--------------------------------------------------------------------------
        */

        Schema::table('self_drive_bookings', function (Blueprint $table) {
            $table->foreign('vehicle_id')
                ->references('id')
                ->on('vehicles')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('self_drive_bookings', function (Blueprint $table) {
            if (Schema::hasColumn('self_drive_bookings', 'vehicle_id')) {
                $table->dropForeign([
                    'vehicle_id',
                ]);

                $table->dropColumn('vehicle_id');
            }
        });
    }
};