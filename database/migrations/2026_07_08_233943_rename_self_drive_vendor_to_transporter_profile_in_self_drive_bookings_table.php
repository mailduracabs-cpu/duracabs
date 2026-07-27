<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('self_drive_bookings', function (Blueprint $table) {

            if (Schema::hasColumn('self_drive_bookings', 'self_drive_vendor_id')) {

                $table->renameColumn(
                    'self_drive_vendor_id',
                    'transporter_profile_id'
                );

            }

        });
    }

    public function down(): void
    {
        Schema::table('self_drive_bookings', function (Blueprint $table) {

            if (Schema::hasColumn('self_drive_bookings', 'transporter_profile_id')) {

                $table->renameColumn(
                    'transporter_profile_id',
                    'self_drive_vendor_id'
                );

            }

        });
    }
};