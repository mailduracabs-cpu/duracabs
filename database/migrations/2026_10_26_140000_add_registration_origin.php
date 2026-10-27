<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            foreach (['registration_source' => 'string', 'registration_account_type' => 'string',
                'registration_completed_at' => 'timestamp', 'registration_admin_attempted_at' => 'timestamp'] as $name => $type) {
                if (! Schema::hasColumn('users', $name)) Schema::table('users', function (Blueprint $table) use ($name, $type): void {
                    $type === 'string' ? $table->string($name, 24)->nullable() : $table->timestamp($name)->nullable();
                });
            }
        }
        if (Schema::hasTable('fleet_transporter_profiles')) {
            if (! Schema::hasColumn('fleet_transporter_profiles', 'registration_source')) Schema::table('fleet_transporter_profiles', function (Blueprint $table): void {
                $table->string('registration_source', 24)->nullable();
            });
            if (! Schema::hasColumn('fleet_transporter_profiles', 'registration_admin_attempted_at')) Schema::table('fleet_transporter_profiles', function (Blueprint $table): void {
                $table->timestamp('registration_admin_attempted_at')->nullable();
            });
        }
        // Intentionally no historical backfill and no historical notifications.
    }
    public function down(): void
    {
        // Audit/source data is retained on rollback. Reverting code must not erase
        // original signup channels or allow old notifications to be sent twice.
    }
};
