<?php
namespace App\Services;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
final class PartnerMarketSavedAlerts {
 public static function matches(int $post,array $spec): bool {
  $q=DB::table('partner_market_posts as p')->where('p.id',$post);PartnerMarketplaceFilters::apply($q,$spec);return $q->exists();
 }
 public static function newPost(int $id): void {
  try {
   $post=DB::table('partner_market_posts')->find($id);
   if(!$post||$post->kind!=='booking'||$post->status!=='open'||PartnerMarketplaceSchedule::due($post))return;
   DB::table('partner_market_saved_filters')->where('active',true)->where('start_post_id','<',$id)->orderBy('id')->chunkById(100,function($filters)use($post){foreach($filters as $f){
    if((int)$f->vendor_id===(int)$post->transporter_profile_id||!self::matches((int)$post->id,json_decode($f->filters,true,512,JSON_THROW_ON_ERROR)))continue;
    $at=now();if(!empty($post->priority_until)&&Carbon::parse($post->priority_until)->gt($at))$at=Carbon::parse($post->priority_until);
    self::record('filter:'.$f->id.':'.$f->version.':'.$post->id,(int)$f->vendor_id,(int)$post->id,'booking_alert',$at,$f);
   }});
  }catch(\Throwable){Log::warning('Saved filter planning deferred',['post_id'=>$id]);}
 }
 public static function scan(): void {
  DB::table('partner_market_saved_filters')->where('active',true)->orderBy('id')->chunkById(100,function($filters){foreach($filters as $filter){
   DB::transaction(function()use($filter){
    $f=DB::table('partner_market_saved_filters')->where('id',$filter->id)->lockForUpdate()->first();if(!$f||!$f->active)return;
    $spec=json_decode($f->filters,true,512,JSON_THROW_ON_ERROR);
    $posts=DB::table('partner_market_posts')->where('id','>',$f->cursor)->orderBy('id')->limit(200)->get();
    foreach($posts as $post){
     if($post->kind==='booking'&&$post->status==='open'&&(int)$post->transporter_profile_id!==(int)$f->vendor_id&&!PartnerMarketplaceSchedule::due($post)&&self::matches((int)$post->id,$spec)){
      $at=now();if(!empty($post->priority_until)&&Carbon::parse($post->priority_until)->gt($at))$at=Carbon::parse($post->priority_until);
      self::record('filter:'.$f->id.':'.$f->version.':'.$post->id,(int)$f->vendor_id,(int)$post->id,'booking_alert',$at,$f);
     }
     DB::table('partner_market_saved_filters')->where('id',$f->id)->update(['cursor'=>$post->id]);
    }
   },3);
  }});
 }
 private static function record(string $key,int $vendor,int $post,string $type,$at,?object $filter=null): void {
  DB::table('partner_market_events')->insertOrIgnore(['event_key'=>$key,'vendor_id'=>$vendor,'post_id'=>$post,'type'=>$type,'filter_id'=>$filter?->id,'filter_version'=>$filter?->version,'available_at'=>$at,'expires_at'=>now()->addDay(),'created_at'=>now(),'updated_at'=>now()]);
 }
 public static function chat(int $message,int $vendor,int $post): void {
  try{self::record('chat:'.$message,$vendor,$post,'chat',now());}catch(\Throwable){Log::warning('Market chat notice could not be recorded',['message_id'=>$message]);}
 }
 public static function paid(int $payment,int $vendor,int $post): void {
  try{self::record('payment:'.$payment,$vendor,$post,'payment_done',now());}
  catch(\Throwable){Log::warning('Payment notice could not be recorded',['payment_id'=>$payment]);}
 }
 public static function assigned(object $post,int $vendor): void {
  try{
   $f=DB::table('partner_market_saved_filters')->where('vendor_id',$vendor)->where('active',true)->first();
   if($f&&self::matches((int)$post->id,json_decode($f->filters,true,512,JSON_THROW_ON_ERROR)))
    DB::table('partner_market_saved_filters')->where('id',$f->id)->where('version',$f->version)->update(['active'=>false,'version'=>$f->version+1,'updated_at'=>now()]);
   self::record('assigned:'.$post->id.':'.($post->assigned_payment_id??$post->revision),$vendor,(int)$post->id,'booking_assigned',now());}
  catch(\Throwable){Log::warning('Market assignment notice could not be recorded',['post_id'=>$post->id]);}
 }
 public static function valid(object $e): bool {
  if(Carbon::parse($e->available_at)->isFuture()||Carbon::parse($e->expires_at)->isPast())return false;
  $v=DB::table('fleet_transporter_profiles')->find($e->vendor_id);
  if(!$v||!$v->status||$v->deleted_at||$v->verification_status!=='verified'||!in_array($v->partner_type,['vendor','both'],true))return false;
  $post=DB::table('partner_market_posts')->find($e->post_id);if(!$post)return false;
  if($e->type==='booking_alert'){
   $f=DB::table('partner_market_saved_filters')->find($e->filter_id);
   $owner=DB::table('fleet_transporter_profiles')->find($post->transporter_profile_id);
   return $f&&$f->active&&(int)$f->vendor_id===(int)$e->vendor_id&&(int)$f->version===(int)$e->filter_version
    &&$owner&&$owner->status&&!$owner->deleted_at&&$owner->verification_status==='verified'
    &&$post->status==='open'&&!PartnerMarketplaceSchedule::due($post)&&PartnerMarketplaceAlerts::visible($post,(int)$e->vendor_id)
    &&self::matches((int)$post->id,json_decode($f->filters,true,512,JSON_THROW_ON_ERROR));
  }
  if($e->type==='chat')return DB::table('partner_market_messages')->where('id',(int)substr($e->event_key,5))->where('recipient_vendor_id',$e->vendor_id)->where('post_id',$e->post_id)->exists();
  if($e->type==='payment_done')return DB::table('partner_market_secure_payments')->where('id',(int)substr($e->event_key,8))->where('post_id',$e->post_id)->where('payer_vendor_id',$e->vendor_id)->where('status','paid')->exists();
  return $e->type==='booking_assigned'&&$post->status==='assigned'&&!empty($post->assigned_fleet_submission_id)
   &&DB::table('partner_market_fleet_submissions')->where('id',$post->assigned_fleet_submission_id)->where('vendor_id',$e->vendor_id)->exists();
 }
 public static function payload(object $e): array {
  $p=DB::table('partner_market_posts')->find($e->post_id);
  $title=match($e->type){'chat'=>'New chat message','booking_assigned'=>'Booking assigned to you','payment_done'=>'Payment successful',default=>'Saved filter: matching booking'};
  $body=$e->type==='chat'?'Open Taxi Sathi to read your new message.':$p->pickup_city.' → '.$p->drop_city.' • '.$p->vehicle_class;
  return ['id'=>'event-'.$e->id,'event_id'=>(string)$e->id,'event_type'=>$e->type,'post_id'=>(string)$e->post_id,'revision'=>(string)$p->revision,'title'=>$title,'message'=>$body];
 }
 public static function deliver(int $id): void {
  $e=DB::table('partner_market_events')->find($id);if(!$e||$e->push_complete)return;
  if(!self::valid($e)){DB::table('partner_market_events')->where('id',$id)->update(['push_complete'=>true]);return;}
  foreach(DB::table('partner_market_devices')->where('vendor_id',$e->vendor_id)->where('seen_at','>=',now()->subDays(60))->get() as $device){
   DB::table('partner_market_event_devices')->insertOrIgnore(['event_id'=>$id,'device_id'=>$device->id,'status'=>'pending']);
   $q=DB::table('partner_market_event_devices')->where('event_id',$id)->where('device_id',$device->id);
   if(!(clone $q)->where('attempts','<',3)->where(function($q){$q->where('status','pending')->orWhere(function($q){$q->whereIn('status',['not_configured','failed'])->where('claimed_at','<=',now()->subMinutes(2));});})->update(['status'=>'checking','claimed_at'=>now(),'attempts'=>DB::raw('attempts + 1')]))continue;
   if($e->type==='booking_alert'&&DB::table('partner_market_alert_devices as ad')->join('partner_market_alerts as a','a.id','=','ad.alert_id')
    ->where('a.post_id',$e->post_id)->where('a.vendor_id',$e->vendor_id)->where('ad.device_id',$device->id)->where('ad.status','accepted')->exists()){
     $q->update(['status'=>'accepted']);continue;
   }
   // Re-read saved filter immediately before dispatch: stopped/edited searches are discarded.
   if(!self::valid($e)){$q->update(['status'=>'cancelled']);continue;}
   try{$r=PartnerMarketplacePush::sendEvent(Crypt::decryptString($device->encrypted_token),self::payload($e));}
   catch(\Throwable){$r=['status'=>'unknown','error'=>'push_result_uncertain'];}
   $q->update(['status'=>$r['status'],'error'=>$r['error']??null]);
   if($r['status']==='invalid_token')DB::table('partner_market_devices')->where('id',$device->id)->delete();
  }
  $pending=DB::table('partner_market_event_devices')->where('event_id',$id)->where(function($q){
   $q->where('status','checking')->orWhere(function($q){$q->whereIn('status',['pending','not_configured','failed'])->where('attempts','<',3);});
  })->exists();
  if(!$pending)DB::table('partner_market_events')->where('id',$id)->update(['push_complete'=>true]);
 }
 public static function tick(): void {
  self::scan();
  DB::table('partner_market_event_devices')->where('status','checking')->where('claimed_at','<',now()->subMinutes(5))->update(['status'=>'unknown','error'=>'worker_interrupted']);
  DB::table('partner_market_events')->where('push_complete',false)->where('available_at','<=',now())->where('expires_at','>',now())->orderBy('id')->chunkById(200,function($rows){foreach($rows as $e)self::deliver((int)$e->id);});
  $old=DB::table('partner_market_events')->where('expires_at','<',now()->subDays(7))->limit(500)->pluck('id');
  if($old->isNotEmpty()){DB::table('partner_market_event_devices')->whereIn('event_id',$old)->delete();DB::table('partner_market_events')->whereIn('id',$old)->delete();}
 }
}
