<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Services\LiveBookingMessageParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
final class LiveBookingWebhookController extends Controller {
 public function verify(Request $r){$mode=$r->query('hub.mode');$token=$r->query('hub.verify_token');if($mode==='subscribe'&&hash_equals((string)config('live_bookings.webhook_verify_token'),(string)$token))return response($r->query('hub.challenge'),200);return response('Forbidden',403);}
 public function receive(Request $r){$secret=(string)config('live_bookings.webhook_app_secret');$signature=(string)$r->header('X-Hub-Signature-256');$expected='sha256='.hash_hmac('sha256',$r->getContent(),$secret);abort_if($secret===''||!hash_equals($expected,$signature),403,'Invalid WhatsApp webhook signature.');
  $payload=$r->json()->all();foreach(($payload['entry']??[]) as $entry)foreach(($entry['changes']??[]) as $change)foreach(($change['value']['messages']??[]) as $m){$group=(string)($m['group_id']??'');$allowed=(array)config('live_bookings.source_group_ids',[]);if($group===''||!$allowed||!in_array($group,$allowed,true)||($m['type']??'')!=='text')continue;$id=(string)($m['id']??'');$text=(string)($m['text']['body']??'');if($id===''||$text==='')continue;$parsed=LiveBookingMessageParser::parse($text);if(!$parsed)continue;
   try{$created=DB::table('live_bookings')->insertOrIgnore($parsed+['source_message_id'=>$id,'source_group_id'=>$group,'source_sender'=>(string)($m['from']??''),'status'=>'open','expires_at'=>now()->addHours((int)config('live_bookings.lead_hours',168)),'created_at'=>now(),'updated_at'=>now()]);if($created){$lead=(int)DB::table('live_bookings')->where('source_message_id',$id)->value('id');\App\Services\LiveBookingAlerts::plan($lead);}}catch(\Throwable $e){Log::warning('Live booking intake failed',['message_id'=>$id,'error'=>$e->getMessage()]);}
  }return response()->json(['status'=>'ok']);
 }
}
