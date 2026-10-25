<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('vehicles', 'partner_payout_mode')) {
            Schema::table('vehicles', function (Blueprint $table): void {
                $table->string('partner_payout_mode', 32)->nullable()->default('percentage');
            });
        } elseif (Schema::getColumnType('vehicles', 'partner_payout_mode') === 'enum') {
            // An earlier installation may have restricted this column to two modes.
            Schema::table('vehicles', function (Blueprint $table): void {
                $table->string('partner_payout_mode', 32)->nullable()->default('percentage')->change();
            });
        }

        if (! Schema::hasColumn('vehicles', 'partner_fixed_rate_per_24h')) {
            Schema::table('vehicles', function (Blueprint $table): void {
                $table->decimal('partner_fixed_rate_per_24h', 12, 2)->nullable();
            });
        }
        if (! Schema::hasColumn('vehicles', 'partner_monthly_amount')) {
            Schema::table('vehicles', function (Blueprint $table): void {
                $table->decimal('partner_monthly_amount', 12, 2)->nullable();
            });
        }

        DB::table('vehicles')->where('service_type', 'self_drive')
            ->where(function ($query): void {
                $query->whereNull('partner_payout_mode')->orWhere('partner_payout_mode', '');
            })->update(['partner_payout_mode' => 'percentage']);
        // Booking snapshots and existing payout items are deliberately untouched.
    }

    public function down(): void
    {
        // A monthly agreement must be deliberately migrated before its amount is removed.
        if (DB::table('vehicles')->where('partner_payout_mode', 'monthly')->exists()) {
            throw new RuntimeException('Monthly vendor agreements exist. Preserve their terms before rolling back.');
        }
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->dropColumn('partner_monthly_amount');
        });
        // Keep the two pre-existing agreement columns and normalized legacy values.
    }
};
