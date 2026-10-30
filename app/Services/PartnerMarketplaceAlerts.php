<?php
namespace App\Services;
use App\Jobs\SendPartnerMarketplaceAlert;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class PartnerMarketplaceAlerts {
 public static function freeCabs(int $vendor): array {
  $profile=DB::table('fleet_transporter_profiles')->find($vendor); if(!$profile)return [];
  return DB::table('partner_market_free_cabs as f')->join('vehicles as v','v.id','=','f.vehicle_id')->where('f.vendor_id',$vendor)->where('f.is_free',true)
   ->where('v.is_active',true)->where('v.is_verified',true)->where('v.verification_status','approved')->whereNull('v.partner_removed_at')->where('v.service_type','taxi')
   ->where(function($q)use($vendor,$profile){$q->where('v.transporter_profile_id',$vendor)->orWhere(function($q)use($profile){$q->whereNull('v.transporter_profile_id')->where('v.user_id',$profile->user_id);});})
   ->select('f.*','v.car_classification','v.car_company_name','v.model_name')->get()->map(fn($r)=>(array)$r+['taxi_name'=>trim($r->car_company_name.' '.$r->model_name)])->all();
 }
 public static function eligible(object $post,object $p): ?array {
  return PartnerMarketplaceMatching::tier((array)$post,(array)$p,self::freeCabs((int)$p->vendor_id),now()->getTimestamp(),(float)config('partner_market.radius_km',30),(int)config('partner_market.location_fresh_seconds',300));
 }
 public static function safePlan(int $id): void {
  try { self::plan($id); } catch(\Throwable) { \Illuminate\Support\Facades\Log::warning('Marketplace alert planning deferred',['post_id'=>$id]); }
 }
 public static function plan(int $id): void {
  $send=[];
  DB::transaction(function()use($id,&$send){
   $post=DB::table('partner_market_posts')->where('id',$id)->lockForUpdate()->first();
   if (!$post||$post->kind!=='booking'||$post->status!=='open'||$post->alerts_planned_at||Carbon::parse($post->pickup_at)->isPast()||$post->pickup_latitude===null||$post->pickup_longitude===null)return;
   $targets=[];
   DB::table('partner_market_presence as p')->join('fleet_transporter_profiles as v','v.id','=','p.vendor_id')->where('v.status',true)->where('v.verification_status','verified')->whereNull('v.deleted_at')->whereIn('v.partner_type',['vendor','both'])
    ->where(function($q){$q->where('p.app_alerts',true)->orWhere('p.whatsapp_alerts',true);})->select('p.*')->orderBy('p.vendor_id')->chunk(200,function($rows)use($post,&$targets){foreach($rows as $p){$match=self::eligible($post,$p);if($match)$targets[]=['p'=>$p,'match'=>$match];}});
   $priority=collect($targets)->contains(fn($t)=>$t['match']['tier']===0);
   $until=$priority?now()->addSeconds(max(0,(int)config('partner_market.priority_seconds',60))):null;
   DB::table('partner_market_posts')->where('id',$id)->update(['priority_until'=>$until,'alerts_planned_at'=>now()]);
   foreach($targets as $t){$m=$t['match'];$at=$m['tier']===0||!$until?now():$until;
    $expires=Carbon::parse($post->pickup_at)->min(now()->addMinutes(30));if($at->gte($expires))continue;
    DB::table('partner_market_alerts')->insertOrIgnore(['post_id'=>$id,'post_revision'=>$post->revision,'vendor_id'=>$t['p']->vendor_id,'tier'=>$m['tier'],'reason'=>$m['reason'],'free_cab_id'=>$m['free_cab_id'],
     'available_at'=>$at,'expires_at'=>$expires,'whatsapp_status'=>$t['p']->whatsapp_alerts?'pending':'disabled','created_at'=>now(),'updated_at'=>now()]);
    $a=DB::table('partner_market_alerts')->where('post_id',$id)->where('post_revision',$post->revision)->where('vendor_id',$t['p']->vendor_id)->first();if($a)$send[]=['id'=>$a->id,'at'=>$at];
   }
  },3);
  if(config('queue.default')!=='sync') foreach($send as $s)SendPartnerMarketplaceAlert::dispatch((int)$s['id'])->delay($s['at']);
 }
 public static function visible(object $post,int $vendor): bool {
  if((int)$post->transporter_profile_id===$vendor||!$post->priority_until||Carbon::parse($post->priority_until)->isPast())return true;
  return DB::table('partner_market_alerts')->where('post_id',$post->id)->where('post_revision',$post->revision)->where('vendor_id',$vendor)->where('tier',0)->exists();
 }
 public static function gate(object $post,int $vendor): void {abort_unless(self::visible($post,$vendor),409,'Matching free cabs have first access. Wait for the priority window to end.');}
 public static function booked(object $post,int $vendor,?int $submission): void {
  $fleet=$submission?DB::table('partner_market_fleet_submissions')->find($submission):null;if(!$fleet||(int)$fleet->vendor_id!==$vendor||empty($fleet->vehicle_id))return;
  $cab=DB::table('partner_market_free_cabs')->where('vehicle_id',$fleet->vehicle_id)->where('vendor_id',$vendor)->first();if(!$cab)return;
  DB::table('partner_market_free_cabs')->where('id',$cab->id)->update(['is_free'=>false,'updated_at'=>now()]);
  if($cab->post_id)DB::table('partner_market_posts')->where('id',$cab->post_id)->where('status','open')->update(['status'=>'assigned','updated_at'=>now()]);
 }
 public static function deliver(int $id): void {
  $a=DB::table('partner_market_alerts')->find($id);if(!$a||Carbon::parse($a->available_at)->isFuture()||Carbon::parse($a->expires_at)->isPast())return;
  DB::table('partner_market_alerts')->where('id',$id)->update(['last_checked_at'=>now()]);
  $post=DB::table('partner_market_posts')->find($a->post_id);$p=DB::table('partner_market_presence')->where('vendor_id',$a->vendor_id)->first();$vendor=DB::table('fleet_transporter_profiles')->find($a->vendor_id);
  if(!$post||$post->status!=='open'||(int)$post->revision!==(int)$a->post_revision||!$p||!$vendor||!$vendor->status||$vendor->verification_status!=='verified'||$vendor->deleted_at)return;
  $match=self::eligible($post,$p);if(!$match||((int)$a->tier===0&&$match['tier']!==0))return;
  if($p->app_alerts)foreach(DB::table('partner_market_devices')->where('vendor_id',$a->vendor_id)->where('seen_at','>=',now()->subDays(60))->get() as $d){
   DB::table('partner_market_alert_devices')->insertOrIgnore(['alert_id'=>$id,'device_id'=>$d->id,'status'=>'pending']);
   $claimed=DB::table('partner_market_alert_devices')->where('alert_id',$id)->where('device_id',$d->id)->whereIn('status',['pending','not_configured'])->update(['status'=>'checking','claimed_at'=>now()]);if(!$claimed)continue;
   try{$r=PartnerMarketplacePush::send(Crypt::decryptString($d->encrypted_token),$post,$a);}catch(\Throwable){$r=['status'=>'unknown','error'=>'push_result_uncertain'];}
   DB::table('partner_market_alert_devices')->where('alert_id',$id)->where('device_id',$d->id)->update(['status'=>$r['status'],'error'=>$r['error']??null]);if($r['status']==='invalid_token')DB::table('partner_market_devices')->where('id',$d->id)->delete();
  }
  if(!$p->whatsapp_alerts||!in_array($a->whatsapp_status,['pending','not_configured'],true))return;
  if(!config('partner_market.whatsapp_enabled')){DB::table('partner_market_alerts')->where('id',$id)->whereIn('whatsapp_status',['pending','not_configured'])->update(['whatsapp_status'=>'not_configured','last_error'=>'whatsapp_disabled']);return;}
  if(!DB::table('partner_market_alerts')->where('id',$id)->whereIn('whatsapp_status',['pending','not_configured'])->update(['whatsapp_status'=>'checking','updated_at'=>now()]))return;
  try{
   $r=WhatsAppService::sendByKey((string)config('partner_market.whatsapp_template_key'),(string)(($vendor->whatsapp_number??null)?:$vendor->mobile),[(string)$post->id,$post->pickup_city,$post->drop_city,$post->pickup_at,$post->vehicle_class,$post->pricing_mode==='quote'?'Discuss best price in app':'Fare INR '.$post->asking_amount.'; commission INR '.$post->vendor_commission]);
   $ok=(bool)($r['status']??false);$error=$r['error']??null;
   DB::table('partner_market_alerts')->where('id',$id)->update(['whatsapp_status'=>$ok?'accepted':($error==='template_not_ready'?'not_configured':'unknown'),'whatsapp_message_id'=>$r['message_id']??null,'last_error'=>$ok?null:($error==='template_not_ready'?'whatsapp_template_not_ready':'whatsapp_result_uncertain'),'updated_at'=>now()]);
  }catch(\Throwable){DB::table('partner_market_alerts')->where('id',$id)->update(['whatsapp_status'=>'unknown','last_error'=>'whatsapp_result_uncertain','updated_at'=>now()]);}
 }
}
