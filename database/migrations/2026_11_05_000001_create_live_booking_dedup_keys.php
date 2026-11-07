<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('live_booking_dedup_keys', function (Blueprint $table) {
            $table->string('fingerprint', 64)->primary();
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('live_booking_dedup_keys');
    }
};
