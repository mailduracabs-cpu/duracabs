<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('self_drive_vendors', function (Blueprint $table) {
            if (!Schema::hasColumn('self_drive_vendors', 'mobile')) {
                $table->string('mobile', 20)->nullable()->after('office_name');
            }

            $table->unique('mobile', 'sd_vendor_mobile_unique');
            $table->unique('vendor_id', 'sd_vendor_vendor_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('self_drive_vendors', function (Blueprint $table) {
            $table->dropUnique('sd_vendor_mobile_unique');
            $table->dropUnique('sd_vendor_vendor_id_unique');

            if (Schema::hasColumn('self_drive_vendors', 'mobile')) {
                $table->dropColumn('mobile');
            }
        });
    }
};