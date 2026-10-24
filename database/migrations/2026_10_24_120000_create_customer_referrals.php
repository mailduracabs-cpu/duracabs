<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('customer_referral_program', function (Blueprint $t) { $t->unsignedInteger('id')->primary(); });
        \Illuminate\Support\Facades\DB::table('customer_referral_program')->insert(['id' => 1]);
        Schema::create('customer_referral_codes', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id')->unique(); $t->string('code', 20)->unique(); $t->timestamps();
        });
        Schema::create('customer_referrals', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('referrer_id')->index(); $t->unsignedBigInteger('customer_id')->unique(); $t->timestamps();
        });
        Schema::create('customer_referral_rewards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('referral_id')->index(); $t->unsignedBigInteger('referrer_id')->index();
            $t->string('booking_source', 20); $t->unsignedBigInteger('booking_id'); $t->decimal('amount', 12, 2);
            $t->unsignedBigInteger('wallet_transaction_id')->nullable(); $t->unsignedBigInteger('reversal_transaction_id')->nullable();
            $t->string('status', 20)->default('credited'); $t->timestamps();
            $t->unique(['booking_source', 'booking_id'], 'customer_referral_reward_booking_unique');
        });
    }
    public function down(): void {
        Schema::dropIfExists('customer_referral_rewards'); Schema::dropIfExists('customer_referrals'); Schema::dropIfExists('customer_referral_codes'); Schema::dropIfExists('customer_referral_program');
    }
};
