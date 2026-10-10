<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('self_drive_vendor_payout_items', function (Blueprint $table): void {
            // Existing partial receipts have no booking allocation: retain null.
            $table->decimal('received_amount', 12, 2)->nullable();
        });
        Schema::create('taxi_vendor_payouts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('transporter_profile_id');
            $table->unsignedBigInteger('order_id')->unique();
            $table->string('payout_no', 50)->unique();
            $table->date('period_from'); $table->date('period_to');
            $table->decimal('payout_amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('remaining_amount', 12, 2);
            $table->string('status', 20)->default('pending');
            $table->string('payment_method', 50)->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamp('paid_at')->nullable(); $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreign('transporter_profile_id', 'tvp_owner_fk')->references('id')->on('fleet_transporter_profiles')->restrictOnDelete();
            $table->foreign('order_id', 'tvp_order_fk')->references('id')->on('orders')->restrictOnDelete();
            $table->index(['transporter_profile_id', 'period_from']);
        });
        Schema::create('partner_payout_payments', function (Blueprint $table): void {
            $table->id();
            $table->string('account', 20); $table->unsignedBigInteger('payout_id');
            $table->unsignedBigInteger('transporter_profile_id');
            $table->string('request_key', 64);
            $table->decimal('amount', 12, 2); $table->dateTime('payment_date');
            $table->string('method', 50); $table->string('reference')->nullable();
            $table->text('notes')->nullable(); $table->json('allocations')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable(); $table->timestamps();
            $table->unique(['account','payout_id','request_key'], 'ppp_request_unique');
            $table->index(['transporter_profile_id','account','payment_date'], 'ppp_owner_date');
        });
    }
    public function down(): void {
        Schema::dropIfExists('partner_payout_payments');
        Schema::dropIfExists('taxi_vendor_payouts');
        Schema::table('self_drive_vendor_payout_items', fn (Blueprint $table) => $table->dropColumn('received_amount'));
    }
};
