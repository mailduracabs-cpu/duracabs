<?php
declare(strict_types=1);
namespace App\Services;
use App\Jobs\SendLiveBookingAlert;
use Illuminate\Support\Facades\DB;
final class LiveBookingAlerts {
 public static function plan(int $id): void {
  $b=DB::table('live_bookings')->where('id',$id)->first();if(!$b||$b->status!=='open')return;
  $vendors=DB::table('partner_market_free_cabs as f')->join('partner_market_presence as p','p.vendor_id','=','f.vendor_id')->join('fleet_transporter_profiles as v','v.id','=','f.vendor_id')
   ->where('f.is_free',true)->where('f.available_from','<=',$b->pickup_at)->where('f.available_until','>=',$b->pickup_at)
   ->where('p.app_alerts',true)->whereNotExists(function($q){$q->selectRaw('1')->from('live_booking_vendor_cooldowns as c')->whereColumn('c.vendor_id','f.vendor_id')->where('c.cooldown_until','>',now());})->where('v.status',true)->where('v.verification_status','verified')->whereNull('v.deleted_at')->distinct()->pluck('f.vendor_id');
  foreach($vendors as $vendor){$free=PartnerMarketplaceAlerts::freeCabs((int)$vendor);if(!collect($free)->contains(fn($cab)=>LiveBookingMatching::cab($b,$cab)))continue;
   DB::table('live_booking_alerts')->insertOrIgnore(['live_booking_id'=>$id,'vendor_id'=>$vendor,'status'=>'pending','created_at'=>now(),'updated_at'=>now()]);$alert=DB::table('live_booking_alerts')->where('live_booking_id',$id)->where('vendor_id',$vendor)->first();if($alert)SendLiveBookingAlert::dispatch((int)$alert->id);
  }
 }
 public static function deliver(int $alertId): void {
  $a=DB::table('live_booking_alerts')->find($alertId);if(!$a||$a->status!=='pending')return;$b=DB::table('live_bookings')->where('id',$a->live_booking_id)->whereIn('status',['open','pending_confirmation'])->first();$p=DB::table('partner_market_presence')->where('vendor_id',$a->vendor_id)->first();if(!$b||!$p||!$p->app_alerts||DB::table('live_booking_vendor_cooldowns')->where('vendor_id',$a->vendor_id)->where('cooldown_until','>',now())->exists())return;
  DB::table('live_booking_alerts')->where('id',$alertId)->update(['status'=>'sending','updated_at'=>now()]);
  foreach(DB::table('partner_market_devices')->where('vendor_id',$a->vendor_id)->where('seen_at','>=',now()->subDays(60))->get() as $d){try{$token=\Illuminate\Support\Facades\Crypt::decryptString($d->encrypted_token);$r=PartnerMarketplacePush::sendLive($token,$b,$a);if($r['status']==='invalid_token')DB::table('partner_market_devices')->where('id',$d->id)->delete();}catch(\Throwable){}}
  DB::table('live_booking_alerts')->where('id',$alertId)->update(['status'=>'sent','updated_at'=>now()]);
 }
}
