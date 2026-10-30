<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('partner_market_secure_payments', 'booking_amount_paise')) Schema::table('partner_market_secure_payments', fn (Blueprint $t) => $t->unsignedBigInteger('booking_amount_paise')->nullable());
        if (!Schema::hasColumn('partner_market_secure_payments', 'booking_snapshot')) Schema::table('partner_market_secure_payments', fn (Blueprint $t) => $t->json('booking_snapshot')->nullable());
    }
    public function down(): void { /* Retain agreed payment terms and financial evidence. */ }
};
