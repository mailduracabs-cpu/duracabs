<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicles', 'hourly_price')) {
                $table->decimal('hourly_price', 10, 2)
                    ->nullable()
                    ->after('bags');
            }

            if (! Schema::hasColumn('vehicles', 'daily_price')) {
                $table->decimal('daily_price', 10, 2)
                    ->nullable()
                    ->after('hourly_price');
            }

            if (! Schema::hasColumn('vehicles', 'security_deposit')) {
                $table->decimal('security_deposit', 10, 2)
                    ->default(0)
                    ->after('daily_price');
            }

            if (! Schema::hasColumn('vehicles', 'minimum_booking_hours')) {
                $table->unsignedInteger('minimum_booking_hours')
                    ->default(24)
                    ->after('security_deposit');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $columns = [];

            foreach ([
                'hourly_price',
                'daily_price',
                'security_deposit',
                'minimum_booking_hours',
            ] as $column) {
                if (Schema::hasColumn('vehicles', $column)) {
                    $columns[] = $column;
                }
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};