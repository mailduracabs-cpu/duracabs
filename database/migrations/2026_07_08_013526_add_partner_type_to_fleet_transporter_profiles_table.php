<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fleet_transporter_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('fleet_transporter_profiles', 'partner_type')) {
                $table->enum('partner_type', [
                    'host',
                    'vendor',
                    'both',
                ])->default('host')->after('user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fleet_transporter_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('fleet_transporter_profiles', 'partner_type')) {
                $table->dropColumn('partner_type');
            }
        });
    }
};