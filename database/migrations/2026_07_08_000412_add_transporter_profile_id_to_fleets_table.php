<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fleets', function (Blueprint $table) {
            if (!Schema::hasColumn('fleets', 'transporter_profile_id')) {
                $table->foreignId('transporter_profile_id')
                    ->nullable()
                    ->after('uuid')
                    ->constrained('fleet_transporter_profiles')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('fleets', function (Blueprint $table) {
            if (Schema::hasColumn('fleets', 'transporter_profile_id')) {
                $table->dropConstrainedForeignId('transporter_profile_id');
            }
        });
    }
};