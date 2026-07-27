<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'service_group')) {
                $table->enum('service_group', [
                    'with_driver',
                    'without_driver'
                ])->default('with_driver')->after('category_id');
            }

            if (!Schema::hasColumn('products', 'vehicle_type')) {
                $table->enum('vehicle_type', [
                    'car',
                    'bike',
                    'tempo'
                ])->default('car')->after('service_group');
            }
        });

        DB::table('products')->update([
            'service_group' => 'with_driver',
            'vehicle_type' => 'car',
        ]);

        DB::table('products')
            ->where(function ($query) {
                $query->where('ride_type', 'self_drive')
                    ->orWhere('ride_type', 'self-drive')
                    ->orWhere('ride_type', 'Self Drive');
            })
            ->update([
                'service_group' => 'without_driver',
                'vehicle_type' => 'car',
            ]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'vehicle_type')) {
                $table->dropColumn('vehicle_type');
            }

            if (Schema::hasColumn('products', 'service_group')) {
                $table->dropColumn('service_group');
            }
        });
    }
};