<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  if (!Schema::hasTable('live_bookings')) Schema::create('live_bookings', function(Blueprint $t) {
   $t->id(); $t->string('source_message_id',191)->unique(); $t->string('source_group_id',191)->index(); $t->string('source_sender',40)->nullable();
   $t->string('from_city',150)->index(); $t->string('to_city',150)->index(); $t->string('trip_type',30)->index(); $t->string('car_type',80)->index();
   $t->dateTime('pickup_at')->index(); $t->dateTime('return_at')->nullable(); $t->string('requester_phone',30)->nullable(); $t->string('requester_name',120)->nullable();
   $t->string('status',30)->default('open')->index(); $t->unsignedBigInteger('confirmed_vendor_id')->nullable()->index(); $t->string('confirm_token_hash',64)->nullable(); $t->dateTime('confirm_token_expires_at')->nullable();
   $t->dateTime('confirmed_at')->nullable(); $t->dateTime('expires_at')->index(); $t->string('ignore_reason',120)->nullable(); $t->timestamps();
  });
  if (!Schema::hasTable('live_booking_interests')) Schema::create('live_booking_interests', function(Blueprint $t) {
   $t->id(); $t->unsignedBigInteger('live_booking_id'); $t->unsignedBigInteger('vendor_id'); $t->string('status',24)->default('interested'); $t->dateTime('created_at')->nullable(); $t->dateTime('updated_at')->nullable();
   $t->unique(['live_booking_id','vendor_id']); $t->index(['vendor_id','status']);
  });
  if (!Schema::hasTable('live_booking_alerts')) Schema::create('live_booking_alerts', function(Blueprint $t) {
   $t->id(); $t->unsignedBigInteger('live_booking_id'); $t->unsignedBigInteger('vendor_id'); $t->string('status',24)->default('pending'); $t->dateTime('read_at')->nullable(); $t->timestamps(); $t->unique(['live_booking_id','vendor_id']); $t->index(['vendor_id','status']);
  });
  if (!Schema::hasTable('live_booking_vendor_cooldowns')) Schema::create('live_booking_vendor_cooldowns', function(Blueprint $t) {
   $t->unsignedBigInteger('vendor_id')->primary(); $t->dateTime('cooldown_until')->index(); $t->unsignedBigInteger('live_booking_id')->nullable(); $t->timestamps();
  });
 }
 public function down(): void { /* Keep operational evidence. */ }
};
