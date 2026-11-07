<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Services\PartnerMarketplaceSchedule as Schedule;
return new class extends Migration {
 public function up(): void {
  if(!Schema::hasColumn('partner_market_posts','total_days'))Schema::table('partner_market_posts',function(Blueprint $t){$t->unsignedSmallInteger('total_days')->nullable();$t->index(['kind','status','pickup_at'],'pm_booking_expiry_scan');});
  DB::table('partner_market_posts')->where('kind','booking')->where('trip_type','round_trip')->whereNull('total_days')->whereNotNull('return_at')->orderBy('id')->chunkById(300,function($rows){foreach($rows as $r){try{$days=Schedule::days($r->pickup_at,$r->return_at);}catch(\Throwable){continue;}DB::table('partner_market_posts')->where('id',$r->id)->update(['total_days'=>$days]);}});
  if(Schema::hasTable('live_bookings'))DB::table('live_bookings')->whereIn('status',['open','pending_confirmation'])->orderBy('id')->chunkById(300,function($rows){foreach($rows as $r)DB::table('live_bookings')->where('id',$r->id)->update(['expires_at'=>Schedule::deadline($r)->format('Y-m-d H:i:s')]);});
 }
 public function down():void { /* Keep duration/history evidence. */ }
};
