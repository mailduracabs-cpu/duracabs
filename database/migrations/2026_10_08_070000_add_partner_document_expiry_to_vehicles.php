<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['insurance_expiry_date', 'puc_expiry_date'] as $column) {
            if (!Schema::hasColumn('vehicles', $column)) {
                Schema::table('vehicles', function (Blueprint $table) use ($column): void {
                    $table->date($column)->nullable()->index();
                });
            }
        }
    }

    public function down(): void
    {
        // Preserve compliance dates on rollback; the columns may have pre-existed.
        // Any intentional removal should use a separate reviewed migration.
    }
};
