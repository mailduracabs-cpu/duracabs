<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('self_drive_vehicles', function (Blueprint $table) {
            if (!Schema::hasColumn('self_drive_vehicles', 'minimum_booking_hours')) {
                $table->unsignedInteger('minimum_booking_hours')
                    ->default(24)
                    ->after('security_deposit');
            }
        });
    }

    public function down(): void
    {
        Schema::table('self_drive_vehicles', function (Blueprint $table) {
            if (Schema::hasColumn('self_drive_vehicles', 'minimum_booking_hours')) {
                $table->dropColumn('minimum_booking_hours');
            }
        });
    }
};