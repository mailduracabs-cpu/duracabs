<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('partner_market_saved_filters',function(Blueprint $t){
   $t->id();$t->unsignedBigInteger('vendor_id')->unique();$t->json('filters');$t->boolean('active')->default(true);
   $t->unsignedInteger('version')->default(1);$t->unsignedBigInteger('start_post_id')->default(0);$t->unsignedBigInteger('cursor')->default(0);$t->timestamps();
  });
  Schema::create('partner_market_events',function(Blueprint $t){
   $t->id();$t->string('event_key',150)->unique();$t->unsignedBigInteger('vendor_id');$t->unsignedBigInteger('post_id');
   $t->string('type',24);$t->boolean('push_complete')->default(false);$t->unsignedBigInteger('filter_id')->nullable();$t->unsignedInteger('filter_version')->nullable();
   $t->dateTime('available_at');$t->dateTime('expires_at');$t->timestamps();$t->index(['vendor_id','id']);$t->index(['push_complete','available_at']);
  });
  Schema::create('partner_market_event_devices',function(Blueprint $t){
   $t->id();$t->unsignedBigInteger('event_id');$t->unsignedBigInteger('device_id');$t->string('status',24)->default('pending');
   $t->unsignedTinyInteger('attempts')->default(0);$t->dateTime('claimed_at')->nullable();$t->string('error',100)->nullable();$t->unique(['event_id','device_id']);
  });
 }
 public function down(): void {Schema::dropIfExists('partner_market_event_devices');Schema::dropIfExists('partner_market_events');Schema::dropIfExists('partner_market_saved_filters');}
};
