<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fleet_transporter_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('fleet_transporter_profiles', 'pickup_place_id')) {
                $table->string('pickup_place_id')->nullable()->after('office_address');
            }

            if (!Schema::hasColumn('fleet_transporter_profiles', 'pickup_latitude')) {
                $table->decimal('pickup_latitude', 10, 7)->nullable()->after('pickup_place_id');
            }

            if (!Schema::hasColumn('fleet_transporter_profiles', 'pickup_longitude')) {
                $table->decimal('pickup_longitude', 10, 7)->nullable()->after('pickup_latitude');
            }

            if (!Schema::hasColumn('fleet_transporter_profiles', 'service_radius_km')) {
                $table->decimal('service_radius_km', 8, 2)->default(40)->after('pickup_longitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fleet_transporter_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('fleet_transporter_profiles', 'service_radius_km')) {
                $table->dropColumn('service_radius_km');
            }

            if (Schema::hasColumn('fleet_transporter_profiles', 'pickup_longitude')) {
                $table->dropColumn('pickup_longitude');
            }

            if (Schema::hasColumn('fleet_transporter_profiles', 'pickup_latitude')) {
                $table->dropColumn('pickup_latitude');
            }

            if (Schema::hasColumn('fleet_transporter_profiles', 'pickup_place_id')) {
                $table->dropColumn('pickup_place_id');
            }
        });
    }
};