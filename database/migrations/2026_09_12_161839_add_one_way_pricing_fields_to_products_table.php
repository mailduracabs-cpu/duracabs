<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('toll_included')
                ->default(false)
                ->after('toll_tax');

            $table->boolean('gst_included')
                ->default(false)
                ->after('driver_allowances');

            $table->decimal('gst_percentage', 5, 2)
                ->default(5.00)
                ->after('gst_included');

            $table->decimal('pat_charge', 10, 2)
                ->default(200.00)
                ->after('gst_percentage');

            $table->decimal('roof_carrier_charge', 10, 2)
                ->default(300.00)
                ->after('pat_charge');

            $table->boolean('parking_included')
                ->default(false)
                ->after('roof_carrier_charge');

            $table->boolean('state_tax_included')
                ->default(false)
                ->after('parking_included');

            $table->decimal('night_charge', 10, 2)
                ->default(0.00)
                ->after('state_tax_included');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'toll_included',
                'gst_included',
                'gst_percentage',
                'pat_charge',
                'roof_carrier_charge',
                'parking_included',
                'state_tax_included',
                'night_charge',
            ]);
        });
    }
};