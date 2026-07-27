<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('self_drive_vehicles', function (Blueprint $table) {
            $table->renameColumn('daily_price', 'hourly_price');
        });
    }

    public function down(): void
    {
        Schema::table('self_drive_vehicles', function (Blueprint $table) {
            $table->renameColumn('hourly_price', 'daily_price');
        });
    }
};