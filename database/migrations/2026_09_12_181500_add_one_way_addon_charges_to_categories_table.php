<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (! Schema::hasColumn('categories', 'pet_friendly_charge')) {
                $table->decimal('pet_friendly_charge', 10, 2)->default(200)->after('extra_hr_charge');
            }

            if (! Schema::hasColumn('categories', 'roof_carrier_charge')) {
                $table->decimal('roof_carrier_charge', 10, 2)->default(300)->after('pet_friendly_charge');
            }

            if (! Schema::hasColumn('categories', 'extra_pickup_charge')) {
                $table->decimal('extra_pickup_charge', 10, 2)->default(500)->after('roof_carrier_charge');
            }

            if (! Schema::hasColumn('categories', 'extra_drop_charge')) {
                $table->decimal('extra_drop_charge', 10, 2)->default(500)->after('extra_pickup_charge');
            }
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('categories', 'pet_friendly_charge') ? 'pet_friendly_charge' : null,
                Schema::hasColumn('categories', 'roof_carrier_charge') ? 'roof_carrier_charge' : null,
                Schema::hasColumn('categories', 'extra_pickup_charge') ? 'extra_pickup_charge' : null,
                Schema::hasColumn('categories', 'extra_drop_charge') ? 'extra_drop_charge' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
