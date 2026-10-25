<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('vehicles', 'partner_joining_date')) {
            Schema::table('vehicles', function (Blueprint $table): void {
                $table->date('partner_joining_date')->nullable();
            });
        }
        // Do not invent joining dates for existing vehicles from created_at or today.
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->dropColumn('partner_joining_date');
        });
    }
};
