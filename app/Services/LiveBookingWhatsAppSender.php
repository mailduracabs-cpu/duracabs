<?php
declare(strict_types=1);
namespace App\Services;
use Illuminate\Support\Facades\Http;
final class LiveBookingWhatsAppSender {
 public static function confirmation(string $phone,string $url): array {
  $phone=preg_replace('/\D/','',$phone);$id=(string)config('live_bookings.phone_number_id');$token=(string)config('live_bookings.access_token');$template=(string)config('live_bookings.confirmation_template');$version=(string)config('live_bookings.api_version','v23.0');
  if(!$phone||!$id||!$token||!$template)return ['status'=>'not_configured'];
  try{$r=Http::withToken($token)->acceptJson()->timeout(10)->post("https://graph.facebook.com/{$version}/{$id}/messages",['messaging_product'=>'whatsapp','to'=>$phone,'type'=>'template','template'=>['name'=>$template,'language'=>['code'=>'en'],'components'=>[['type'=>'body','parameters'=>[['type'=>'text','text'=>$url]]]]]]);
   return $r->successful()?['status'=>'sent','message_id'=>$r->json('messages.0.id')]:['status'=>'failed','error'=>'provider_rejected'];
  }catch(\Throwable){return ['status'=>'unknown'];}
 }
}
