<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  if(!Schema::hasColumn('partner_market_posts','communication_mode'))Schema::table('partner_market_posts',function(Blueprint $t){
   // Old bookings have never opted into disclosure.
   $t->string('communication_mode',16)->default('chat_only');
  });
 }
 public function down():void {Schema::table('partner_market_posts',fn(Blueprint $t)=>$t->dropColumn('communication_mode'));}
};
