<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  foreach (['pickup_latitude','pickup_longitude'] as $column) if (!Schema::hasColumn('partner_market_posts',$column)) Schema::table('partner_market_posts',fn(Blueprint $t)=>$t->double($column)->nullable());
  foreach (['priority_until','alerts_planned_at'] as $column) if (!Schema::hasColumn('partner_market_posts',$column)) Schema::table('partner_market_posts',fn(Blueprint $t)=>$t->dateTime($column)->nullable());
  if (!Schema::hasColumn('partner_market_posts','availability_vehicle_id')) Schema::table('partner_market_posts',fn(Blueprint $t)=>$t->unsignedBigInteger('availability_vehicle_id')->nullable());
  if (!Schema::hasTable('partner_market_presence')) Schema::create('partner_market_presence',function(Blueprint $t){
   $t->unsignedBigInteger('vendor_id')->primary(); $t->boolean('app_alerts')->default(false); $t->boolean('whatsapp_alerts')->default(false); $t->boolean('gps_enabled')->default(false);
   $t->double('latitude')->nullable(); $t->double('longitude')->nullable(); $t->double('accuracy')->nullable(); $t->dateTime('located_at')->nullable();
   $t->string('search_place_id',255)->nullable(); $t->string('search_city',150)->nullable(); $t->double('search_latitude')->nullable(); $t->double('search_longitude')->nullable(); $t->dateTime('search_until')->nullable();
   $t->unsignedInteger('revision')->default(1); $t->timestamps();
  });
  if (!Schema::hasTable('partner_market_free_cabs')) Schema::create('partner_market_free_cabs',function(Blueprint $t){
   $t->id(); $t->unsignedBigInteger('vendor_id'); $t->unsignedBigInteger('vehicle_id')->unique(); $t->unsignedBigInteger('post_id')->nullable();
   $t->double('latitude'); $t->double('longitude'); $t->string('city',150); $t->dateTime('available_from'); $t->dateTime('available_until'); $t->boolean('is_free')->default(true); $t->timestamps();
   $t->index(['vendor_id','is_free']);
  });
  if (!Schema::hasTable('partner_market_devices')) Schema::create('partner_market_devices',function(Blueprint $t){
   $t->id(); $t->unsignedBigInteger('vendor_id')->index(); $t->string('token_hash',64)->unique(); $t->text('encrypted_token'); $t->string('platform',20); $t->dateTime('seen_at');
  });
  if (!Schema::hasTable('partner_market_alerts')) Schema::create('partner_market_alerts',function(Blueprint $t){
   $t->id(); $t->unsignedBigInteger('post_id'); $t->unsignedInteger('post_revision'); $t->unsignedBigInteger('vendor_id'); $t->unsignedTinyInteger('tier'); $t->string('reason',30); $t->unsignedBigInteger('free_cab_id')->nullable();
   $t->dateTime('available_at'); $t->dateTime('expires_at'); $t->dateTime('read_at')->nullable(); $t->dateTime('last_checked_at')->nullable(); $t->string('whatsapp_status',30)->default('pending'); $t->string('whatsapp_message_id',255)->nullable(); $t->string('last_error',120)->nullable(); $t->timestamps();
   $t->unique(['post_id','post_revision','vendor_id'],'pm_alert_recipient_once'); $t->index(['vendor_id','available_at']);
  });
  if (!Schema::hasTable('partner_market_alert_devices')) Schema::create('partner_market_alert_devices',function(Blueprint $t){
   $t->id(); $t->unsignedBigInteger('alert_id'); $t->unsignedBigInteger('device_id'); $t->string('status',30)->default('pending'); $t->dateTime('claimed_at')->nullable(); $t->string('error',120)->nullable();
   $t->unique(['alert_id','device_id'],'pm_alert_device_once');
  });
 }
 public function down(): void { /* Retain alert/read/delivery evidence. */ }
};
