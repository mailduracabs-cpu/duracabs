<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('self_drive_bookings', function (Blueprint $table) {

            if (! Schema::hasColumn('self_drive_bookings', 'booking_type')) {
                $table->string('booking_type', 20)
                    ->default('car')
                    ->after('vehicle_id')
                    ->index();
            }

            if (! Schema::hasColumn('self_drive_bookings', 'helmet_option')) {
                $table->string('helmet_option', 50)
                    ->nullable()
                    ->after('booking_type');
            }

            if (! Schema::hasColumn('self_drive_bookings', 'helmet_charge')) {
                $table->decimal('helmet_charge', 10, 2)
                    ->default(0)
                    ->after('helmet_option');
            }

            if (! Schema::hasColumn('self_drive_bookings', 'included_helmets')) {
                $table->unsignedInteger('included_helmets')
                    ->default(0)
                    ->after('helmet_charge');
            }

            if (! Schema::hasColumn('self_drive_bookings', 'extra_helmet_count')) {
                $table->unsignedInteger('extra_helmet_count')
                    ->default(0)
                    ->after('included_helmets');
            }

            if (! Schema::hasColumn('self_drive_bookings', 'plan_type')) {
                $table->string('plan_type', 20)
                    ->default('daily')
                    ->after('extra_helmet_count');
            }

            if (! Schema::hasColumn('self_drive_bookings', 'base_rent')) {
                $table->decimal('base_rent', 12, 2)
                    ->default(0)
                    ->after('plan_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('self_drive_bookings', function (Blueprint $table) {

            foreach ([
                'booking_type',
                'helmet_option',
                'helmet_charge',
                'included_helmets',
                'extra_helmet_count',
                'plan_type',
                'base_rent',
            ] as $column) {

                if (Schema::hasColumn('self_drive_bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};