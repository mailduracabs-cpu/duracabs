<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // MySQL DDL is not transactional: a failed first run may have already
        // added these columns or created the earlier tables. Preserve them.
        $existing = [];
        foreach (['admin_hold', 'hold_previous_payout_status', 'admin_revision', 'admin_settlement_id'] as $column) {
            $existing[$column] = Schema::hasColumn('partner_market_secure_payments', $column);
        }
        if (in_array(false, $existing, true)) {
            Schema::table('partner_market_secure_payments', function (Blueprint $table) use ($existing): void {
                if (!$existing['admin_hold']) $table->boolean('admin_hold')->default(false);
                if (!$existing['hold_previous_payout_status']) $table->string('hold_previous_payout_status', 30)->nullable();
                if (!$existing['admin_revision']) $table->unsignedInteger('admin_revision')->default(1);
                if (!$existing['admin_settlement_id']) $table->unsignedBigInteger('admin_settlement_id')->nullable();
            });
        }
        if (!Schema::hasTable('partner_market_admin_settlements')) {
            Schema::create('partner_market_admin_settlements', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('payment_id')->constrained('partner_market_secure_payments')->restrictOnDelete();
                $table->unsignedBigInteger('payer_refund_paise');
                $table->unsignedBigInteger('payee_payout_paise');
                $table->unsignedBigInteger('platform_fee_paise');
                $table->unsignedBigInteger('penalty_paise')->default(0);
                $table->string('penalty_against', 20)->default('none');
                $table->text('reason');
                $table->unsignedBigInteger('created_by');
                $table->string('status', 30)->default('planned');
                $table->string('refund_status', 30)->default('not_required');
                $table->string('refund_request_key', 100)->nullable()->unique();
                $table->string('razorpay_refund_id', 100)->nullable()->unique();
                $table->string('payout_status', 30)->default('blocked');
                $table->string('payout_reference', 150)->nullable();
                $table->timestamp('payout_recorded_at')->nullable();
                $table->timestamps();
                $table->index(['payment_id', 'status']);
            });
        }
        if (!Schema::hasTable('partner_market_admin_audits')) {
            Schema::create('partner_market_admin_audits', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('post_id')->constrained('partner_market_posts')->restrictOnDelete();
                $table->unsignedBigInteger('payment_id')->nullable()->index();
                $table->unsignedBigInteger('actor_user_id')->nullable();
                $table->string('actor_name', 150);
                $table->string('action', 60);
                $table->text('reason');
                $table->json('details')->nullable();
                $table->dateTime('created_at')->nullable();
            });
        }
        if (!Schema::hasTable('partner_market_admin_messages')) {
            Schema::create('partner_market_admin_messages', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('post_id')->constrained('partner_market_posts')->restrictOnDelete();
                $table->foreignId('peer_vendor_id')->constrained('fleet_transporter_profiles')->restrictOnDelete();
                $table->unsignedBigInteger('admin_user_id');
                $table->string('admin_name', 150);
                $table->text('message');
                $table->timestamp('owner_read_at')->nullable();
                $table->timestamp('peer_read_at')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->index(['post_id', 'peer_vendor_id', 'id'], 'pm_admin_thread_idx');
            });
        }
    }
    public function down(): void
    {
        // Financial decisions and audit records are deliberately retained on rollback.
    }
};
