<?php
namespace App\Services;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
class PartnerMarketplacePush {
 private static function access(): array {
  $path=(string)config('partner_market.firebase_credentials');
  if (!$path||!is_file($path)) throw new \RuntimeException('firebase_not_configured');
  $c=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);
  if (empty($c['project_id'])||empty($c['client_email'])||empty($c['private_key'])) throw new \RuntimeException('firebase_credentials_invalid');
  $token=Cache::remember('pm_fcm:'.hash('sha256',$c['client_email'].$c['private_key']),3000,function()use($c){
   $b64=static fn($s)=>rtrim(strtr(base64_encode($s),'+/','-_'),'='); $now=time();
   $input=$b64(json_encode(['alg'=>'RS256','typ'=>'JWT'])).'.'.$b64(json_encode(['iss'=>$c['client_email'],'scope'=>'https://www.googleapis.com/auth/firebase.messaging','aud'=>'https://oauth2.googleapis.com/token','iat'=>$now,'exp'=>$now+3600]));
   if (!openssl_sign($input,$sig,$c['private_key'],OPENSSL_ALGO_SHA256)) throw new \RuntimeException('firebase_signing_failed');
   $r=Http::asForm()->connectTimeout(5)->timeout(20)->post('https://oauth2.googleapis.com/token',['grant_type'=>'urn:ietf:params:oauth:grant-type:jwt-bearer','assertion'=>$input.'.'.$b64($sig)]);
   if (!$r->successful()||!$r->json('access_token')) throw new \RuntimeException('firebase_auth_failed'); return $r->json('access_token');
  }); return [$c['project_id'],$token];
 }
 public static function send(string $token,object $post,object $alert): array {
  try { [$project,$access]=self::access(); } catch(\Throwable) { return ['status'=>'not_configured','error'=>'firebase_setup_failed']; }
  try {
   $r=Http::withToken($access)->acceptJson()->connectTimeout(5)->timeout(20)->post('https://fcm.googleapis.com/v1/projects/'.rawurlencode($project).'/messages:send',['message'=>[
    'token'=>$token,'notification'=>['title'=>(int)$alert->tier===0?'Free cab ke liye booking!':'Matching booking available','body'=>$post->pickup_city.' → '.$post->drop_city.' • '.$post->vehicle_class.' • '.$post->pickup_at],
    'data'=>['type'=>'partner_market_booking','post_id'=>(string)$post->id,'alert_id'=>(string)$alert->id,'revision'=>(string)$post->revision],
    'android'=>['priority'=>'HIGH','ttl'=>'120s','notification'=>['channel_id'=>'market_bookings_v1','sound'=>'market_booking','tag'=>'market_'.$post->id]],
    'apns'=>['headers'=>['apns-priority'=>'10'],'payload'=>['aps'=>['sound'=>'market_booking.wav']]],
   ]]);
   if ($r->successful()) return ['status'=>'accepted'];
   $code=$r->json('error.details.0.errorCode')?:$r->json('error.status');
   return ['status'=>$code==='UNREGISTERED'?'invalid_token':'failed','error'=>substr((string)$code,0,100)];
  } catch(\Throwable $e) { return ['status'=>str_starts_with($e->getMessage(),'firebase_')?'not_configured':'unknown','error'=>str_starts_with($e->getMessage(),'firebase_')?$e->getMessage():'push_result_uncertain']; }
 }

 public static function sendLive(string $token,object $booking,object $alert): array {
  try { [$project,$access]=self::access(); } catch(\Throwable) { return ['status'=>'not_configured','error'=>'firebase_setup_failed']; }
  try { $r=Http::withToken($access)->acceptJson()->connectTimeout(5)->timeout(20)->post('https://fcm.googleapis.com/v1/projects/'.rawurlencode($project).'/messages:send',['message'=>[
   'token'=>$token,'notification'=>['title'=>'New Live Booking','body'=>$booking->from_city.' → '.$booking->to_city.' • '.$booking->car_type.' • '.$booking->pickup_at],
   'data'=>['type'=>'live_booking','live_booking_id'=>(string)$booking->id,'post_id'=>'live:'.(string)$booking->id,'alert_id'=>(string)$alert->id],
   'android'=>['priority'=>'HIGH','ttl'=>'120s','notification'=>['channel_id'=>'market_bookings_v1','sound'=>'market_booking','tag'=>'live_'.$booking->id]],'apns'=>['headers'=>['apns-priority'=>'10'],'payload'=>['aps'=>['sound'=>'market_booking.wav']]],
  ]]); if($r->successful())return ['status'=>'accepted'];$code=$r->json('error.details.0.errorCode')?:$r->json('error.status');return ['status'=>$code==='UNREGISTERED'?'invalid_token':'failed','error'=>substr((string)$code,0,100)];
  } catch(\Throwable $e){return ['status'=>'unknown','error'=>'push_result_uncertain'];}
 }
}
