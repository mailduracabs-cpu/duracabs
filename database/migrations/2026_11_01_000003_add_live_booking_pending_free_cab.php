<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  if(!Schema::hasColumn('live_bookings','pending_free_vehicle_id'))Schema::table('live_bookings',function(Blueprint $t){$t->unsignedBigInteger('pending_free_vehicle_id')->nullable();});
  if(!Schema::hasColumn('live_bookings','pending_free_post_id'))Schema::table('live_bookings',function(Blueprint $t){$t->unsignedBigInteger('pending_free_post_id')->nullable();});
 }
 public function down():void { /* Preserve pending confirmation references. */ }
};
