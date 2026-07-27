<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('self_drive_bookings')) {
            return;
        }

        Schema::table('self_drive_bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('self_drive_bookings', 'booking_type')) {
                $table->string('booking_type', 20)
                    ->default('car')
                    ->index();
            }

            if (! Schema::hasColumn('self_drive_bookings', 'helmet_option')) {
                $table->string('helmet_option', 50)->nullable();
            }

            if (! Schema::hasColumn('self_drive_bookings', 'helmet_charge')) {
                $table->decimal('helmet_charge', 10, 2)->default(0);
            }

            if (! Schema::hasColumn('self_drive_bookings', 'included_helmets')) {
                $table->unsignedInteger('included_helmets')->default(0);
            }

            if (! Schema::hasColumn('self_drive_bookings', 'extra_helmet_count')) {
                $table->unsignedInteger('extra_helmet_count')->default(0);
            }

            if (! Schema::hasColumn('self_drive_bookings', 'plan_type')) {
                $table->string('plan_type', 20)->default('daily');
            }

            if (! Schema::hasColumn('self_drive_bookings', 'base_rent')) {
                $table->decimal('base_rent', 12, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('self_drive_bookings')) {
            return;
        }

        $columns = [
            'booking_type',
            'helmet_option',
            'helmet_charge',
            'included_helmets',
            'extra_helmet_count',
            'plan_type',
            'base_rent',
        ];

        foreach ($columns as $column) {
            if (Schema::hasColumn('self_drive_bookings', $column)) {
                Schema::table('self_drive_bookings', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};