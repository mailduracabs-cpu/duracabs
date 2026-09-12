<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            if (! Schema::hasColumn('categories', 'extra_km_charge')) {
                $table->decimal('extra_km_charge', 10, 2)->default(0)->after('km_charge');
            }

            if (! Schema::hasColumn('categories', 'extra_hr_charge')) {
                $table->decimal('extra_hr_charge', 10, 2)->default(0)->after('extra_km_charge');
            }
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $columns = [];
            if (Schema::hasColumn('categories', 'extra_km_charge')) { $columns[] = 'extra_km_charge'; }
            if (Schema::hasColumn('categories', 'extra_hr_charge')) { $columns[] = 'extra_hr_charge'; }
            if ($columns !== []) { $table->dropColumn($columns); }
        });
    }
};
