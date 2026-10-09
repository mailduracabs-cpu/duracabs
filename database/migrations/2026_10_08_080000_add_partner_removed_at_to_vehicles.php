<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (!Schema::hasColumn('vehicles', 'partner_removed_at')) {
            Schema::table('vehicles', fn (Blueprint $table) => $table->timestamp('partner_removed_at')->nullable()->index());
        }
    }
    public function down(): void { /* Preserve vehicle history and pre-existing columns. */ }
};
