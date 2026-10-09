<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (!Schema::hasColumn('users', 'partner_driver_profile_id')) {
            Schema::table('users', fn (Blueprint $table) => $table->unsignedBigInteger('partner_driver_profile_id')->nullable()->index());
        }
        if (!Schema::hasColumn('users', 'partner_driver_removed_at')) {
            Schema::table('users', fn (Blueprint $table) => $table->timestamp('partner_driver_removed_at')->nullable()->index());
        }
    }
    public function down(): void { /* Keep account ownership and archived driver history. */ }
};
