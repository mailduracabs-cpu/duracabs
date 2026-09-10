<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('self_drive_bookings', function (Blueprint $table) {
            $table->decimal('commission_percentage', 5, 2)
                ->nullable()
                ->after('price_per_day');
        });

        /*
         * Existing bookings के लिए current vehicle commission backfill.
         * Historical rate 100% exact तभी होगा अगर commission कभी बदला न हो.
         */
        DB::statement("
            UPDATE self_drive_bookings AS b
            INNER JOIN vehicles AS v
                ON v.id = b.vehicle_id
            SET b.commission_percentage = COALESCE(v.commission_percentage, 0)
            WHERE b.commission_percentage IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('self_drive_bookings', function (Blueprint $table) {
            $table->dropColumn('commission_percentage');
        });
    }
};