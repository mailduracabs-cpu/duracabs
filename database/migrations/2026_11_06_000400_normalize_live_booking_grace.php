<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\PartnerMarketplaceSchedule;
return new class extends Migration {
 public function up():void {
  if(!Schema::hasTable('live_bookings'))return;
  DB::table('live_bookings')->whereIn('status',['open','pending_confirmation'])->orderBy('id')->chunkById(300,function($rows){
   foreach($rows as $row){$end=PartnerMarketplaceSchedule::deadline($row);if(!$end)continue;$expiry=$end->format('Y-m-d H:i:s');
    if((string)$row->expires_at===$expiry)continue;
    DB::table('live_bookings')->where('id',$row->id)->where('pickup_at',$row->pickup_at)->whereIn('status',['open','pending_confirmation'])->update(['expires_at'=>$expiry]);
   }
  });
 }
 public function down():void { /* Preserve corrected expiry times and booking history. */ }
};
