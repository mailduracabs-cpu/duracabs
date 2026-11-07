<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
final class LiveBookingInterestNotification {
 public static function phone(?string $value): ?string {
  $n=preg_replace('/\D/','',(string)$value);
  if(strlen($n)===10 && preg_match('/^[6-9]/',$n))$n='91'.$n;
  return preg_match('/^[1-9][0-9]{7,14}$/',$n)?$n:null;
 }
 public static function text(object $booking, object $vendor): string {
  $name=trim((string)($vendor->company_name ?: ($vendor->contact_person ?? '')));
  $name=$name!==''?mb_substr((string)preg_replace('/[\r\n]+/u',' ',$name),0,120):'Vendor #'.$vendor->id;
  $pickup=\Carbon\Carbon::parse($booking->pickup_at,config('app.timezone'));
  $trip=ucwords(str_replace('_',' ',(string)$booking->trip_type));
  $phone=self::phone($vendor->mobile??null);
  $url='https://play.google.com/store/apps/details?id=com.duracabs.duracabs_vendor_app';
  $message="🚖 *DuraCabs Taxi Sathi | Booking Interest*\n\n";
  $message.="Namaste! Aapki booking par ek vendor ne interest dikhaya hai.\n\n";
  $message.="🆔 *Booking ID:* #{$booking->id}\n";
  $message.="📍 *Route:* {$booking->from_city} → {$booking->to_city}\n";
  $message.="🚘 *Vehicle:* {$booking->car_type}\n";
  $message.="🔄 *Trip:* {$trip}\n";
  $message.="📅 *Pickup:* ".$pickup->format('d/m/Y')."\n";
  $message.="⏰ *Time:* ".$pickup->format('h:i A')."\n";
  if(!empty($booking->return_at))$message.="🗓️ *Return Date:* ".\Carbon\Carbon::parse($booking->return_at,config('app.timezone'))->format('d/m/Y')."\n";
  $message.="\n👤 *Interested Vendor:* {$name}\n";
  if($phone)$message.="📞 *Contact:* +{$phone}\n";
  $message.="\n📲 *DuraCabs Taxi Sathi*\n\n";
  $message.="Booking manage karne aur interested vendors se connect hone ke liye app use karein.\n\n";
  $message.="🔗 *Download App:*\n{$url}";
  return $message;
 }
 public static function prepare(object $booking,object $vendor): int {
  $phone=self::phone($booking->requester_phone??null);
  DB::table('live_booking_interest_notifications')->insertOrIgnore([
   'live_booking_id'=>$booking->id,'vendor_id'=>$vendor->id,'recipient'=>$phone,
   'message'=>self::text($booking,$vendor),'status'=>$phone?'pending':'missing_contact',
   'created_at'=>now(),'updated_at'=>now()]);
  return (int)DB::table('live_booking_interest_notifications')->where('live_booking_id',$booking->id)->where('vendor_id',$vendor->id)->value('id');
 }
 public static function deliver(int $id): void {
  $row=DB::table('live_booking_interest_notifications')->find($id);
  if(!$row||$row->status!=='pending')return;
  $secret=(string)config('live_bookings.qr_secret');
  $base=rtrim((string)config('live_bookings.qr_bridge_url','http://127.0.0.1:3210'),'/');
  // The bridge remains a private local service; don't send its secret to arbitrary hosts.
  $u=parse_url($base);
  if(!config('live_bookings.qr_enabled')||strlen($secret)<32||!$u||!in_array($u['host']??'',['127.0.0.1','localhost','::1'],true)||($u['scheme']??'')!=='http'||isset($u['user'])||isset($u['pass'])||isset($u['query'])||isset($u['fragment'])) {
   DB::table('live_booking_interest_notifications')->where('id',$id)->where('status','pending')->update(['status'=>'not_configured','updated_at'=>now()]);return;
  }
  if(!DB::table('live_booking_interest_notifications')->where('id',$id)->where('status','pending')->update(['status'=>'sending','updated_at'=>now()]))return;
  $body=json_encode(['id'=>'live-interest:'.$id,'phone'=>$row->recipient,'text'=>$row->message,'timestamp'=>time()],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
  $status='unknown';$messageId=null;
  try {
   $r=Http::acceptJson()->timeout(30)->withHeaders(['X-Taxi-Sathi-Signature'=>'sha256='.hash_hmac('sha256',$body,$secret)])
    ->withBody($body,'application/json')->post($base.'/send-interest');
   $s=$r->json('status');
   if(in_array($s,['sent','failed','not_connected','unknown'],true))$status=$s;
   if($status==='sent')$messageId=mb_substr((string)$r->json('message_id'),0,191);
  }catch(\Throwable){ /* Unknown outcome must not be blindly resent. */ }
  DB::table('live_booking_interest_notifications')->where('id',$id)->where('status','sending')->update(['status'=>$status,'provider_message_id'=>$messageId,'updated_at'=>now()]);
 }
}
