<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('partner_market_posts')) {
            throw new RuntimeException('partner_market_posts does not exist. Apply Phase 1 first.');
        }

        $columns = [
            'vendor_commission' => Schema::hasColumn('partner_market_posts', 'vendor_commission'),
            'service_fee' => Schema::hasColumn('partner_market_posts', 'service_fee'),
            'vendor_payout' => Schema::hasColumn('partner_market_posts', 'vendor_payout'),
            'secure_booking' => Schema::hasColumn('partner_market_posts', 'secure_booking'),
        ];

        Schema::table('partner_market_posts', function (Blueprint $table) use ($columns): void {
            if (!$columns['vendor_commission']) {
                $table->decimal('vendor_commission', 12, 2)->default(0);
            }
            if (!$columns['service_fee']) {
                $table->decimal('service_fee', 12, 2)->default(0);
            }
            if (!$columns['vendor_payout']) {
                $table->decimal('vendor_payout', 12, 2)->default(0);
            }
            if (!$columns['secure_booking']) {
                $table->boolean('secure_booking')->default(false);
            }
        });
    }

    public function down(): void
    {
        // Intentionally non-destructive: existing booking financial data must not be dropped.
    }
};
