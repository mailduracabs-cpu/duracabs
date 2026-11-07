<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        foreach (['partner_market_posts', 'partner_market_free_cabs'] as $table) {
            // MySQL DDL is not transactional: retry safely after a partial run.
            if (Schema::hasColumn($table, 'drop_cities')) continue;
            Schema::table($table, function (Blueprint $table) { $table->json('drop_cities')->nullable(); });
        }
    }
    public function down(): void {
        foreach (['partner_market_posts', 'partner_market_free_cabs'] as $table) {
            if (!Schema::hasColumn($table, 'drop_cities')) continue;
            Schema::table($table, function (Blueprint $table) { $table->dropColumn('drop_cities'); });
        }
    }
};
