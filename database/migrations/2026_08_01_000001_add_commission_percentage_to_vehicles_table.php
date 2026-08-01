<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('vehicles', 'commission_percentage')) {
            Schema::table('vehicles', function (Blueprint $table): void {
                $table->decimal('commission_percentage', 5, 2)
                    ->default(30)
                    ->after('daily_price');
            });
        }

        // Older admin form stored commission in daily_price. Preserve that
        // percentage and rebuild the actual 24-hour customer price.
        DB::table('vehicles')
            ->whereIn('service_type', ['self_drive', 'bike_rental'])
            ->whereNotNull('daily_price')
            ->whereBetween('daily_price', [0, 100])
            ->orderBy('id')
            ->chunkById(100, function ($vehicles): void {
                foreach ($vehicles as $vehicle) {
                    $commission = max(0, min(100, (float) $vehicle->daily_price));
                    $hourlyPrice = max(0, (float) ($vehicle->hourly_price ?? 0));
                    $dailyPrice = round($hourlyPrice * 24, 2);

                    DB::table('vehicles')
                        ->where('id', $vehicle->id)
                        ->update([
                            'commission_percentage' => $commission ?: 30,
                            'daily_price' => $dailyPrice,
                            'weekly_price' => round(($dailyPrice * 7) * 0.80, 2),
                            'monthly_price' => round(($dailyPrice * 30) * 0.70, 2),
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('vehicles', 'commission_percentage')) {
            Schema::table('vehicles', function (Blueprint $table): void {
                $table->dropColumn('commission_percentage');
            });
        }
    }
};