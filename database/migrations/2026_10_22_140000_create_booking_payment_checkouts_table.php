<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('booking_payment_checkouts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('customer_id')->index();
            $table->string('source', 20);
            $table->longText('payload');
            $table->decimal('amount', 12, 2);
            $table->decimal('total', 12, 2);
            $table->string('payment_type', 20);
            $table->string('gateway_order_id')->nullable()->unique();
            $table->string('gateway_payment_id')->nullable()->unique();
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->string('state', 30)->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_payment_checkouts');
    }
};
