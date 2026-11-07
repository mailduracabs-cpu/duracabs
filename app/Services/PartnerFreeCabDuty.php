<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
final class PartnerFreeCabDuty {
 public static function available(array $cab,int $pickup,?int $at=null):bool {
  $from=strtotime((string)($cab['available_from']??''));
  if(empty($cab['is_free'])||$from===false||$from>$pickup)return false;
  return !empty($cab['until_duty'])||(!empty($cab['available_until'])&&strtotime((string)$cab['available_until'])>=$pickup&&($at===null||strtotime((string)$cab['available_until'])>$at));
 }
 public static function cooldown(int $vendor):?string {
  $legacy=DB::table('live_booking_vendor_cooldowns')->where('vendor_id',$vendor)->where('cooldown_until','>',now())->value('cooldown_until');
  $duty=DB::table('partner_market_free_cab_duties')->where('vendor_id',$vendor)->where('cooldown_until','>',now())->max('cooldown_until');
  return max($legacy??'', $duty??'')?:null;
 }
 public static function select(object $vendor,int $vehicle,int $post,?object $live=null):void {
   $saved=PartnerMarketplaceFleet::vehicles($vendor)->whereKey($vehicle)->lockForUpdate()->first();
   abort_unless($saved,403,'Choose an approved taxi belonging to this vendor.');
   $listing=DB::table('partner_market_posts')->where('id',$post)->lockForUpdate()->first();
   abort_unless($listing&&$listing->kind==='free_vehicle'&&(int)$listing->transporter_profile_id===(int)$vendor->id&&(int)$listing->availability_vehicle_id===$vehicle,403,'This free-cab post does not belong to you.');
   $cab=DB::table('partner_market_free_cabs')->where('vehicle_id',$vehicle)->where('vendor_id',$vendor->id)->lockForUpdate()->first();
   abort_unless($cab&&(int)$cab->post_id===$post,409,'This availability was replaced. Refresh before submitting.');
   abort_unless($cab->is_free&&$listing->status==='open',409,'This car is no longer marked free.');
   if($live){$match=(array)$cab+['car_classification'=>$saved->car_classification,'taxi_name'=>trim($saved->car_company_name.' '.$saved->model_name)];abort_unless(LiveBookingMatching::cab($live,$match),422,'This duty does not match the selected free car.');}
 }
 public static function record(object $vendor,int $vehicle,int $post,string $source,?object $live=null):object {
  abort_unless(in_array($source,['taxi_sathi','other'],true),422,'Choose where you received the duty.');
  abort_unless(!$live||$live->status==='confirmed',409,'Requester confirmation is required before closing the free car.');
  return DB::transaction(function()use($vendor,$vehicle,$post,$source,$live){
   $saved=PartnerMarketplaceFleet::vehicles($vendor)->whereKey($vehicle)->lockForUpdate()->first();
   abort_unless($saved,403,'Choose an approved taxi belonging to this vendor.');
   $listing=DB::table('partner_market_posts')->where('id',$post)->lockForUpdate()->first();
   abort_unless($listing&&$listing->kind==='free_vehicle'&&(int)$listing->transporter_profile_id===(int)$vendor->id&&(int)$listing->availability_vehicle_id===$vehicle,403,'This free-cab post does not belong to you.');
   $cab=DB::table('partner_market_free_cabs')->where('vehicle_id',$vehicle)->where('vendor_id',$vendor->id)->lockForUpdate()->first();
   abort_unless($cab&&(int)$cab->post_id===$post,409,'This availability was replaced. Refresh before submitting.');
   $existing=DB::table('partner_market_free_cab_duties')->where('post_id',$post)->first();
   if($existing){abort_unless($existing->source===$source&&(int)($existing->live_booking_id??0)===(int)($live->id??0),409,'Duty was already recorded with different details.');return $existing;}
   abort_unless($cab->is_free&&$listing->status==='open',409,'This car is no longer marked free.');
   if($live){$match=(array)$cab+['car_classification'=>$saved->car_classification,'taxi_name'=>trim($saved->car_company_name.' '.$saved->model_name)];abort_unless(LiveBookingMatching::cab($live,$match),422,'This duty does not match the selected free car.');}
   $now=now();$id=DB::table('partner_market_free_cab_duties')->insertGetId(['vendor_id'=>$vendor->id,'vehicle_id'=>$vehicle,'post_id'=>$post,'source'=>$source,'live_booking_id'=>$live?->id,'received_at'=>$now,'cooldown_until'=>$now->copy()->addMinutes((int)config('live_bookings.cooldown_minutes',60)),'created_at'=>$now,'updated_at'=>$now]);
   DB::table('partner_market_free_cabs')->where('id',$cab->id)->where('post_id',$post)->update(['is_free'=>false,'updated_at'=>$now]);
   DB::table('partner_market_posts')->where('id',$post)->update(['status'=>'completed','revision'=>DB::raw('revision + 1'),'updated_at'=>$now]);
   return DB::table('partner_market_free_cab_duties')->find($id);
  },3);
 }
}
