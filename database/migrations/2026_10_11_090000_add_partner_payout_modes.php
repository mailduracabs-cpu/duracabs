<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('vehicles', 'partner_payout_mode')) {
            Schema::table('vehicles', function (Blueprint $table): void {
                $table->string('partner_payout_mode', 20)->default('percentage');
            });
        }

        if (!Schema::hasColumn('vehicles', 'partner_fixed_rate_per_24h')) {
            Schema::table('vehicles', function (Blueprint $table): void {
                $table->decimal('partner_fixed_rate_per_24h', 12, 2)->nullable();
            });
        }

        if (!Schema::hasColumn('self_drive_bookings', 'partner_payout_terms')) {
            Schema::table('self_drive_bookings', function (Blueprint $table): void {
                $table->json('partner_payout_terms')->nullable();
            });
        }

        if (!Schema::hasColumn('self_drive_vendor_payout_items', 'payout_terms')) {
            Schema::table('self_drive_vendor_payout_items', function (Blueprint $table): void {
                $table->json('payout_terms')->nullable();
            });
        }

        // Freeze only rows with no saved payout terms; never replace existing history.
        DB::table('self_drive_bookings as b')
            ->join('vehicles as v', 'v.id', '=', 'b.vehicle_id')
            ->where('b.booking_type', 'car')
            ->whereNull('b.partner_payout_terms')
            ->select('b.id', 'v.commission_percentage')
            ->orderBy('b.id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('self_drive_bookings')
                        ->where('id', $row->id)
                        ->whereNull('partner_payout_terms')
                        ->update(['partner_payout_terms' => json_encode([
                            'version' => 0,
                            'mode' => 'legacy',
                            'commission_percentage' => (string) ($row->commission_percentage ?? 0),
                            'note' => 'Pre-upgrade rule frozen at migration; historical terms not reconstructed.',
                        ], JSON_THROW_ON_ERROR)]);
                }
            }, 'b.id', 'id');
    }

    public function down(): void
    {
        throw new \RuntimeException('Payout terms are financial history. Restore a reviewed backup instead of dropping them.');
    }
};
