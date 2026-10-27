<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('partner_market_secure_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('post_id')->constrained('partner_market_posts')->restrictOnDelete();
            $table->foreignId('quote_id')->unique()->constrained('partner_market_quotes')->restrictOnDelete();
            $table->foreignId('payer_vendor_id')->constrained('fleet_transporter_profiles')->restrictOnDelete();
            $table->foreignId('payee_vendor_id')->constrained('fleet_transporter_profiles')->restrictOnDelete();
            $table->unsignedBigInteger('commission_paise');
            $table->unsignedBigInteger('service_fee_paise');
            $table->unsignedBigInteger('payout_paise');
            $table->string('status', 32)->default('pending'); // pending | paid | cancelled | refund_pending | refunded
            $table->string('razorpay_order_id', 100)->nullable()->unique();
            $table->string('razorpay_payment_id', 100)->nullable()->unique();
            $table->string('razorpay_refund_id', 100)->nullable()->unique();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
            $table->index(['post_id', 'status']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('partner_market_secure_payments');
    }
};
