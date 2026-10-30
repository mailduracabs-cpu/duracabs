<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('partner_market_messages', 'read_at')) {
            Schema::table('partner_market_messages', function (Blueprint $table): void {
                $table->timestamp('read_at')->nullable();
                $table->index(['recipient_vendor_id', 'read_at'], 'pm_unread_recipient_idx');
            });
        }
    }
    public function down(): void
    {
        Schema::table('partner_market_messages', function (Blueprint $table): void {
            $table->dropIndex('pm_unread_recipient_idx');
            $table->dropColumn('read_at');
        });
    }
};
