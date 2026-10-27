<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('partner_market_secure_payments', function (Blueprint $table): void {
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('completion_confirmed_at')->nullable();
            $table->timestamp('settlement_eligible_at')->nullable();
            $table->timestamp('refund_requested_at')->nullable();
            $table->timestamp('refund_initiated_at')->nullable();
            $table->string('refund_request_key', 100)->nullable()->unique();
            $table->text('cancellation_reason')->nullable();
            $table->string('payout_status', 30)->default('blocked'); // blocked | eligible | processing | paid | review_required
            $table->string('refund_status', 30)->default('none'); // none | requested | initiated | processed | failed | review_required
            $table->index(['payout_status', 'settlement_eligible_at'], 'partner_market_payout_queue_idx');
        });
    }

    public function down(): void
    {
        Schema::table('partner_market_secure_payments', function (Blueprint $table): void {
            $table->dropIndex('partner_market_payout_queue_idx');
            $table->dropUnique(['refund_request_key']);
            $table->dropColumn([
                'ended_at', 'completion_confirmed_at', 'settlement_eligible_at',
                'refund_requested_at', 'refund_initiated_at', 'refund_request_key',
                'cancellation_reason', 'payout_status', 'refund_status',
            ]);
        });
    }
};
