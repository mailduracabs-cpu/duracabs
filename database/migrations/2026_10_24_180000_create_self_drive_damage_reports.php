<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('self_drive_damage_reports', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('booking_id')->index();
            $table->string('fingerprint',64); $table->string('status')->default('pending');
            $table->json('manifest')->nullable(); $table->json('result')->nullable();
            $table->string('model')->nullable(); $table->text('error')->nullable();
            $table->timestamp('notified_at')->nullable(); $table->timestamps();
            $table->unique(['booking_id','fingerprint']);
        });
    }
    public function down(): void { Schema::dropIfExists('self_drive_damage_reports'); }
};
