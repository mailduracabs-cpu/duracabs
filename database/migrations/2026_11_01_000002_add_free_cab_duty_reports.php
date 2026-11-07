<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  if(!Schema::hasColumn('partner_market_free_cabs','until_duty'))Schema::table('partner_market_free_cabs',fn(Blueprint $t)=>$t->boolean('until_duty')->default(true));
  if(!Schema::hasTable('partner_market_free_cab_duties'))Schema::create('partner_market_free_cab_duties',function(Blueprint $t){
   $t->id();$t->unsignedBigInteger('vendor_id')->index();$t->unsignedBigInteger('vehicle_id')->index();$t->unsignedBigInteger('post_id')->unique();
   $t->string('source',24);$t->unsignedBigInteger('live_booking_id')->nullable()->index();$t->dateTime('received_at');$t->dateTime('cooldown_until')->index();$t->timestamps();
  });
 }
 public function down():void { /* Keep owner duty declarations and their provenance. */ }
};
