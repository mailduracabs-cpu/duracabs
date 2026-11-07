<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api\V1;
use App\Models\FleetManagement\TransporterProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
final class LiveBookingsController extends PartnerController {
 private function vendor(Request $r):TransporterProfile{[$v,$role]=$this->context($r);abort_unless($role==='vendor'&&$v->isActive(),403,'Active vendor account required.');return $v;}
 public function index(Request $r){$v=$this->vendor($r);\App\Services\PartnerMarketplaceSchedule::expireLiveDue();DB::table('live_bookings')->where('status','pending_confirmation')->where('confirm_token_expires_at','<=',now())->update(['status'=>'open','confirmed_vendor_id'=>null,'confirm_token_hash'=>null,'confirm_token_expires_at'=>null,'updated_at'=>now()]);$i=$r->validate(['date_from'=>['nullable','date'],'date_to'=>['nullable','date','after_or_equal:date_from'],'time_from'=>['nullable','date_format:H:i'],'time_to'=>['nullable','date_format:H:i'],'trip_type'=>['nullable',Rule::in(['one_way','round_trip','local'])],'from_city'=>['nullable','string','max:150'],'to_city'=>['nullable','string','max:150'],'car_type'=>['nullable','string','max:80'],'scope'=>['sometimes',Rule::in(['open','confirmed'])],'page'=>['sometimes','integer','min:1']]);
  $scope=$i['scope']??'open';$cool=\App\Services\PartnerFreeCabDuty::cooldown((int)$v->id);$q=DB::table('live_bookings');
  if($scope==='confirmed'){
   $q->where(function($w)use($v){
    $w->where(function($open)use($v){$open->whereIn('status',['open','pending_confirmation'])->where('expires_at','>',now())->whereExists(function($interest)use($v){$interest->selectRaw('1')->from('live_booking_interests as i')->whereColumn('i.live_booking_id','live_bookings.id')->where('i.vendor_id',$v->id)->whereIn('i.status',['interested','confirmed']);});})
     ->orWhere(function($confirmed)use($v){$confirmed->where('status','confirmed')->where('confirmed_vendor_id',$v->id);});
   });
  }else{
   // Every active booking is visible. A free cab only controls priority alerts, never marketplace visibility.
   $q->whereIn('status',['open','pending_confirmation'])->where('expires_at','>',now())->whereNotExists(function($interest)use($v){$interest->selectRaw('1')->from('live_booking_interests as i')->whereColumn('i.live_booking_id','live_bookings.id')->where('i.vendor_id',$v->id)->whereIn('i.status',['interested','confirmed']);});
  }
  foreach(['from_city','to_city','car_type'] as $k) if(!empty($i[$k])) $q->where($k,'like','%'.$i[$k].'%');
  if(!empty($i['trip_type'])) $q->where('trip_type',$i['trip_type']);
  // Range comparisons can use the pickup_at index; DATE(pickup_at) cannot.
  if(!empty($i['date_from'])) $q->where('pickup_at','>=',\Carbon\Carbon::parse($i['date_from'])->startOfDay());
  if(!empty($i['date_to'])) $q->where('pickup_at','<',\Carbon\Carbon::parse($i['date_to'])->startOfDay()->addDay());
  if(!empty($i['time_from'])) $q->whereTime('pickup_at','>=',$i['time_from']);
  if(!empty($i['time_to'])) $q->whereTime('pickup_at','<=',$i['time_to']);
  $page=(int)($i['page']??1);
  $total=(clone $q)->count();
  $items=$q->orderByDesc('created_at')->orderByDesc('id')->forPage($page,30)->get();
  $ids=$items->pluck('id');
  // A fixed three batch reads replaces three reads per booking.
  $interests=$ids->isEmpty()?collect():DB::table('live_booking_interests')->whereIn('live_booking_id',$ids)->where('vendor_id',$v->id)->pluck('status','live_booking_id');
  $outcomes=$ids->isEmpty()?collect():DB::table('live_booking_vendor_outcomes')->whereIn('live_booking_id',$ids)->where('vendor_id',$v->id)->get()->keyBy('live_booking_id');
  $counts=$ids->isEmpty()?collect():DB::table('live_booking_interests')->whereIn('live_booking_id',$ids)->where('status','interested')->select('live_booking_id')->selectRaw('COUNT(*) AS aggregate')->groupBy('live_booking_id')->pluck('aggregate','live_booking_id');
  foreach($items as $row){
   $row->total_days=$row->trip_type==='round_trip'&&$row->return_at?\App\Services\PartnerMarketplaceSchedule::days($row->pickup_at,$row->return_at):null;
   $contactPhone=$row->requester_phone;
   unset($row->requester_phone,$row->source_sender,$row->source_group_id,$row->source_message_id,$row->confirm_token_hash);
   $row->my_interest=$interests->get($row->id);
   if(in_array($row->my_interest,['interested','confirmed'],true)){
    $row->contact_phone=\App\Services\LiveBookingInterestNotification::phone($contactPhone);
    $row->contact_whatsapp_text=\App\Services\LiveBookingInterestNotification::text($row,$v);
   }
   $outcome=$outcomes->get($row->id);
   $row->my_outcome=$outcome?->outcome;
   $row->my_outcome_note=$outcome?->note;
   $row->interested_count=(int)$counts->get($row->id,0);
  }
  return response()->json(['status'=>true,'data'=>['items'=>$items,'page'=>$page,'last_page'=>max(1,(int)ceil($total/30)),'cooldown_until'=>$cool]])->header('Cache-Control','private, no-store');
 }
 public function contact(Request $r,int $id){
  $v=$this->vendor($r);$b=DB::table('live_bookings')->where('id',$id)->first();abort_unless($b,404);
  $confirmed=$b->status==='confirmed'&&(int)$b->confirmed_vendor_id===(int)$v->id;
  $interested=in_array($b->status,['open','pending_confirmation'],true)&&now()->lt($b->expires_at)&&DB::table('live_booking_interests')->where('live_booking_id',$id)->where('vendor_id',$v->id)->where('status','interested')->exists();
  abort_unless($confirmed||$interested,403,'Show interest in this open booking to contact the requester.');
  return response()->json(['status'=>true,'data'=>['requester_phone'=>\App\Services\LiveBookingInterestNotification::phone($b->requester_phone),'requester_name'=>$b->requester_name,'whatsapp_text'=>\App\Services\LiveBookingInterestNotification::text($b,$v)]])->header('Cache-Control','private, no-store');
 }
 public function interest(Request $r,int $id){
  $v=$this->vendor($r);
  $booking=DB::transaction(function()use($v,$id){
   $b=DB::table('live_bookings')->where('id',$id)->lockForUpdate()->first();
   abort_unless($b&&in_array($b->status,['open','pending_confirmation'],true)&&now()->lt($b->expires_at),409,'Booking is no longer open.');
   DB::table('live_booking_interests')->insertOrIgnore(['live_booking_id'=>$id,'vendor_id'=>$v->id,'status'=>'interested','created_at'=>now(),'updated_at'=>now()]);
   return $b;
  },3);
  // Dispatch only after durable interest and notification records commit.
  try {$notificationId=\App\Services\LiveBookingInterestNotification::prepare($booking,$v);\App\Jobs\SendLiveBookingInterest::dispatch($notificationId);}
  catch(\Throwable){return response()->json(['status'=>true,'message'=>'Interest saved. WhatsApp notification is pending; you can call or WhatsApp the requester.','whatsapp_status'=>'pending']);}
  $delivery=DB::table('live_booking_interest_notifications')->where('id',$notificationId)->value('status');
  return response()->json(['status'=>true,'message'=>$delivery==='sent'?'Interest saved. WhatsApp message sent to the booking contact.':'Interest saved. Call or WhatsApp the requester; automatic message status: '.$delivery.'.','whatsapp_status'=>$delivery]);
 }
 public function updateOutcome(Request $r,int $id){
  $v=$this->vendor($r);
  $i=$r->validate(['outcome'=>['required',Rule::in(['done_with_me','done_with_other','other'])],'note'=>['nullable','string','max:1000','required_if:outcome,other']]);
  $note=trim((string)($i['note']??''));
  if($i['outcome']==='other'&&$note==='')return response()->json(['message'=>'Please enter a note for Other.','errors'=>['note'=>['Please enter a note.']]],422);
  DB::transaction(function()use($id,$v,$i,$note){
   $b=DB::table('live_bookings')->where('id',$id)->lockForUpdate()->first();
   abort_unless($b,404,'Booking not found.');
   abort_unless(in_array($b->status,['open','pending_confirmation'],true)&&now()->lt($b->expires_at),409,'Booking is no longer open.');
   abort_unless(DB::table('live_booking_interests')->where('live_booking_id',$id)->where('vendor_id',$v->id)->whereIn('status',['interested','confirmed'])->exists(),403,'Show interest first.');
   DB::table('live_booking_vendor_outcomes')->updateOrInsert(
    ['live_booking_id'=>$id,'vendor_id'=>$v->id],
    ['outcome'=>$i['outcome'],'note'=>$i['outcome']==='other'?$note:null,'updated_at'=>now()]
   );
  },3);
  return response()->json(['status'=>true,'message'=>'Booking result saved.','data'=>['outcome'=>$i['outcome'],'note'=>$i['outcome']==='other'?$note:null]]);
 }
 public function dutyReceived(Request $r,int $id){
  $v=$this->vendor($r);$i=$r->validate(['source'=>['required',Rule::in(['taxi_sathi','other'])],'vehicle_id'=>['required','integer','min:1'],'expected_post_id'=>['required','integer','min:1']]);
  if($i['source']==='other'){
   $d=\App\Services\PartnerFreeCabDuty::record($v,(int)$i['vehicle_id'],(int)$i['expected_post_id'],'other');
   return response()->json(['status'=>true,'message'=>'Other-source duty saved. Only your selected car is no longer free; this Live Booking remains available.','data'=>['duty_id'=>$d->id]]);
  }
  $result=DB::transaction(function()use($id,$v,$i){
   $b=DB::table('live_bookings')->where('id',$id)->lockForUpdate()->first();
   abort_unless($b&&$b->status==='open'&&now()->lt($b->expires_at),409,'Live booking is no longer open.');
   abort_unless(DB::table('live_booking_interests')->where('live_booking_id',$id)->where('vendor_id',$v->id)->where('status','interested')->exists(),403,'Show interest before confirming duty.');
   \App\Services\PartnerFreeCabDuty::select($v,(int)$i['vehicle_id'],(int)$i['expected_post_id'],$b);
   $token=bin2hex(random_bytes(24));$hash=hash('sha256',$token);
   DB::table('live_bookings')->where('id',$id)->update(['status'=>'pending_confirmation','confirmed_vendor_id'=>$v->id,'pending_free_vehicle_id'=>(int)$i['vehicle_id'],'pending_free_post_id'=>(int)$i['expected_post_id'],'confirm_token_hash'=>$hash,'confirm_token_expires_at'=>now()->addMinutes(5),'updated_at'=>now()]);
   return ['phone'=>$b->requester_phone,'hash'=>$hash,'token'=>$token,'vehicle_id'=>(int)$i['vehicle_id']];
  },3);
  $url=rtrim((string)config('app.url'),'/').'/api/live-bookings/confirm/'.$result['token'];
  try {$delivery=$result['phone']?\App\Services\LiveBookingWhatsAppSender::confirmation((string)$result['phone'],$url):['status'=>'no_phone'];} catch(\Throwable){$delivery=['status'=>'failed'];}
  if(in_array($delivery['status'],['not_configured','failed','no_phone'],true))DB::table('live_bookings')->where('id',$id)->where('confirm_token_hash',$result['hash'])->where('status','pending_confirmation')->update(['status'=>'open','confirmed_vendor_id'=>null,'confirm_token_hash'=>null,'confirm_token_expires_at'=>null,'updated_at'=>now()]);
  return response()->json(['status'=>true,'message'=>$delivery['status']==='sent'?'Confirmation link sent. Your selected taxi stays free until the requester confirms.':'Requester confirmation is not complete. Your taxi is still free; no duty has been assigned.','whatsapp_delivery'=>$delivery['status'],'data'=>['confirmation_pending'=>$delivery['status']==='sent']]);
 }
 public function requesterConfirm(string $token){$hash=hash('sha256',$token);return DB::transaction(function()use($hash){$b=DB::table('live_bookings')->where('confirm_token_hash',$hash)->lockForUpdate()->first();abort_unless($b&&$b->status==='pending_confirmation'&&$b->confirm_token_expires_at&&now()->lt($b->confirm_token_expires_at)&&now()->lt($b->expires_at),404,'This confirmation link has expired or was already used.');$now=now();
 if(!empty($b->pending_free_vehicle_id)&&!empty($b->pending_free_post_id)){
  $vendor=TransporterProfile::find($b->confirmed_vendor_id);
  abort_unless($vendor&&$vendor->isActive(),409,'Vendor is no longer active.');
  $confirmed=clone $b;$confirmed->status='confirmed';
  \App\Services\PartnerFreeCabDuty::record($vendor,(int)$b->pending_free_vehicle_id,(int)$b->pending_free_post_id,'taxi_sathi',$confirmed);
 }
 DB::table('live_bookings')->where('id',$b->id)->update(['status'=>'confirmed','confirmed_at'=>$now,'confirm_token_hash'=>null,'confirm_token_expires_at'=>null,'updated_at'=>$now]);DB::table('live_booking_interests')->where('live_booking_id',$b->id)->where('vendor_id',$b->confirmed_vendor_id)->update(['status'=>'confirmed','updated_at'=>$now]);if(!DB::table('partner_market_free_cab_duties')->where('vendor_id',$b->confirmed_vendor_id)->where('live_booking_id',$b->id)->exists()){ DB::table('live_booking_vendor_cooldowns')->updateOrInsert(['vendor_id'=>$b->confirmed_vendor_id],['live_booking_id'=>$b->id,'cooldown_until'=>$now->copy()->addMinutes((int)config('live_bookings.cooldown_minutes',60)),'created_at'=>$now,'updated_at'=>$now]); }return response()->view('live-booking.confirmed');},3);}
}
