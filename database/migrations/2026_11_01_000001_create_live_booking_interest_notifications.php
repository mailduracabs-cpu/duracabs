<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  if (!Schema::hasTable('live_booking_interest_notifications')) Schema::create('live_booking_interest_notifications', function(Blueprint $t) {
   $t->id(); $t->unsignedBigInteger('live_booking_id'); $t->unsignedBigInteger('vendor_id');
   $t->string('recipient',30)->nullable(); $t->text('message'); $t->string('status',32)->default('pending')->index();
   $t->string('provider_message_id',191)->nullable(); $t->timestamps();
   $t->unique(['live_booking_id','vendor_id'],'live_interest_notice_unique');
  });
 }
 public function down(): void { /* Preserve delivery evidence to prevent accidental duplicate sends. */ }
};
