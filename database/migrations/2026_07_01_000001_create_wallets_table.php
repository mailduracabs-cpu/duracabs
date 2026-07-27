<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('wallets')) {
            Schema::create('wallets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('wallet_type')->default('customer');
                $table->decimal('balance', 12, 2)->default(0);
                $table->decimal('hold_balance', 12, 2)->default(0);
                $table->string('currency')->default('INR');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['user_id', 'wallet_type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};