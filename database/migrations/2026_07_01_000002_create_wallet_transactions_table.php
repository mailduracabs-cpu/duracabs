<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('wallet_transactions')) {
            Schema::create('wallet_transactions', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('wallet_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('booking_id')->nullable()->index();

                $table->string('transaction_id')->nullable()->index();
                $table->enum('type', ['credit', 'debit'])->index();
                $table->string('payment_method')->nullable();

                $table->decimal('amount', 12, 2)->default(0);
                $table->decimal('opening_balance', 12, 2)->default(0);
                $table->decimal('closing_balance', 12, 2)->default(0);

                $table->string('status')->default('success')->index();
                $table->string('reference')->nullable();
                $table->text('remarks')->nullable();
                $table->json('meta')->nullable();

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};