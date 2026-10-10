<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('self_drive_bookings', fn (Blueprint $table) => $table->json('security_refund_ledger')->nullable());
    }
    public function down(): void
    {
        Schema::table('self_drive_bookings', fn (Blueprint $table) => $table->dropColumn('security_refund_ledger'));
    }
};
