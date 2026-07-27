<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fleets', function (Blueprint $table) {
            if (!Schema::hasColumn('fleets', 'sun_roof')) {
                $table->boolean('sun_roof')->default(false)->after('range_km');
            }

            if (!Schema::hasColumn('fleets', 'gps')) {
                $table->boolean('gps')->default(false)->after('sun_roof');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fleets', function (Blueprint $table) {
            if (Schema::hasColumn('fleets', 'gps')) {
                $table->dropColumn('gps');
            }

            if (Schema::hasColumn('fleets', 'sun_roof')) {
                $table->dropColumn('sun_roof');
            }
        });
    }
};