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
         * Preserve any booking reference that exists only in the old duplicate
         * column before removing it.
         */
        if (
            Schema::hasTable('self_drive_bookings')
            && Schema::hasColumn('self_drive_bookings', 'vehicle_id')
            && Schema::hasColumn('self_drive_bookings', 'self_drive_vehicle_id')
        ) {
            DB::table('self_drive_bookings')
                ->whereNull('vehicle_id')
                ->whereNotNull('self_drive_vehicle_id')
                ->update([
                    'vehicle_id' => DB::raw('self_drive_vehicle_id'),
                ]);

            Schema::table('self_drive_bookings', function (Blueprint $table): void {
                $table->dropColumn('self_drive_vehicle_id');
            });
        }

        /*
         * Remove the temporary Phase-1 tracking column if it was added.
         */
        if (
            Schema::hasTable('vehicles')
            && Schema::hasColumn('vehicles', 'legacy_fleet_id')
        ) {
            Schema::table('vehicles', function (Blueprint $table): void {
                $table->dropColumn('legacy_fleet_id');
            });
        }

        /*
         * Fleet data is testing data, so the obsolete table can now be removed.
         */
        Schema::dropIfExists('fleets');
    }

    public function down(): void
    {
        if (
            Schema::hasTable('self_drive_bookings')
            && ! Schema::hasColumn('self_drive_bookings', 'self_drive_vehicle_id')
        ) {
            Schema::table('self_drive_bookings', function (Blueprint $table): void {
                $table->unsignedBigInteger('self_drive_vehicle_id')
                    ->nullable()
                    ->after('vehicle_id');
            });

            DB::table('self_drive_bookings')
                ->whereNotNull('vehicle_id')
                ->update([
                    'self_drive_vehicle_id' => DB::raw('vehicle_id'),
                ]);
        }

        /*
         * The original fleets table is intentionally not recreated here because
         * its old schema spans multiple historical migrations.
         */
    }
};
