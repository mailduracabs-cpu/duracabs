<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Services\LiveBookingMessageParser;
use App\Services\LiveBookingAiParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
final class LiveBookingWebhookController extends Controller {
 public function verify(Request $r){$mode=$r->query('hub.mode');$token=$r->query('hub.verify_token');if($mode==='subscribe'&&hash_equals((string)config('live_bookings.webhook_verify_token'),(string)$token))return response($r->query('hub.challenge'),200);return response('Forbidden',403);}
 public function receive(Request $r) {
  $qr = $r->header('X-Taxi-Sathi-Bridge') === 'qr-v1';
  if ($qr) abort_unless(config('live_bookings.qr_enabled'), 403, 'QR intake is disabled.');
  $secret = (string) config($qr ? 'live_bookings.qr_secret' : 'live_bookings.webhook_app_secret');
  $signature = (string) $r->header('X-Hub-Signature-256');
  $expected = 'sha256='.hash_hmac('sha256', $r->getContent(), $secret);
  abort_if($secret === '' || ($qr && strlen($secret) < 32) || !hash_equals($expected, $signature), 403, 'Invalid WhatsApp webhook signature.');
  abort_if(strlen($r->getContent()) > 1048576, 413, 'Message payload too large.');
  $payload = $r->json()->all(); $imported = 0; $ignored = 0; $reviewed = 0; $failed = false; $reasons = [];
  $skip = function (string $reason) use (&$ignored, &$reasons) { $ignored++; $reasons[$reason] = ($reasons[$reason] ?? 0) + 1; };
  foreach (($payload['entry'] ?? []) as $entry) foreach (($entry['changes'] ?? []) as $change) foreach (($change['value']['messages'] ?? []) as $m) {
   $group = (string) ($m['group_id'] ?? '');
   $allowed = (array) config('live_bookings.source_group_ids', []);
   // QR bridge selection is managed by its authenticated, loopback-only admin UI.
   // Cloud API messages continue to require the existing server-side allowlist.
   $sourceAllowed = $qr ? str_ends_with($group, '@g.us') : ($allowed && in_array($group, $allowed, true));
   $timestamp = (int) ($m['timestamp'] ?? 0);
   if ($group === '' || strlen($group)>190 || !$sourceAllowed) { $skip('source_group_rejected'); continue; }
   if (($m['type'] ?? '') !== 'text') { $skip('non_text_message'); continue; }
   $id = (string) ($m['id'] ?? ''); $text = (string) ($m['text']['body'] ?? '');
   if ($id === '' || strlen($id)>191 || $text === '' || strlen((string)($m['from'] ?? ''))>40) { $skip('invalid_payload'); continue; }
   $receivedAt = null;
   if($timestamp>0){try{$receivedAt=\Carbon\Carbon::createFromTimestamp($timestamp,'Asia/Kolkata');}catch(\Throwable){}}
   $timeReason=$qr?\App\Services\LiveBookingIntakeTiming::reason($timestamp,time()):null;
   if($timeReason!==null){
    Log::notice('Live booking intake time rejected', ['message_hash'=>substr(hash('sha256',$id),0,16), 'reason'=>$timeReason,'whatsapp_timestamp'=>$timestamp,'server_timestamp'=>time(),'age_seconds'=>time()-$timestamp]);
    if(\App\Services\LiveBookingIntakeReview::isCandidate($text)){
     if(!\App\Services\LiveBookingIntakeReview::record($m,$timeReason,$receivedAt)){$failed=true;continue;}
     $reviewed++;$reasons['needs_review_'.$timeReason]=($reasons['needs_review_'.$timeReason]??0)+1;
    }else{$skip($timeReason);}
    continue;
   }
   $parsed = \App\Services\LiveBookingIntakeGuard::parse($text, $receivedAt);
   if (!$parsed) $parsed = \App\Services\LiveBookingIntakeGuard::parseWithAi($text, $receivedAt);
   // Both paths already pass the same source-evidence validation in the Guard.
   // Do not run the old regex minimum-field gate again after a validated AI result.
   if (!$parsed) {
    $reason=\App\Services\LiveBookingIntakeGuard::lastReason();
    if(\App\Services\LiveBookingIntakeReview::isCandidate($text)){
     if(!\App\Services\LiveBookingIntakeReview::record($m,$reason,$receivedAt)){$failed=true;continue;}
     $reviewed++;$reasons['needs_review_'.$reason]=($reasons['needs_review_'.$reason]??0)+1;
     // A malformed/empty provider response is a technical failure, not a booking rejection.
     if(in_array($reason,['ai_invalid_json','ai_incomplete_json','ai_empty_response','ai_provider_error','ai_ambiguous_response'],true))$failed=true;
    }else{$skip($reason);}
    continue;
   }
   if (\App\Services\PartnerMarketplaceSchedule::due((object) $parsed)) { $skip('expired_booking'); continue; }
   // Canonicalize once at intake, before deduplication and persistence. Never on list reads.
   $locations=\App\Services\LiveBookingLocationVerifier::verify($parsed);
   if($locations['data']===null){
    $reason=$locations['reason'];
    if(!\App\Services\LiveBookingIntakeReview::record($m,$reason,$receivedAt))$failed=true;
    if($locations['retry']){$failed=true;$reasons[$reason]=($reasons[$reason]??0)+1;}
    else{$skip($reason);}
    continue;
   }
   $parsed=$locations['data'];
   try {
    $created = \App\Services\LiveBookingDeduplicator::forMessage($text, $receivedAt)->insertOrIgnore($parsed + [
     'source_message_id'=>$id, 'source_group_id'=>$group, 'source_sender'=>(string)($m['from'] ?? ''),
     'status'=>'open', 'expires_at'=>\App\Services\PartnerMarketplaceSchedule::deadline((object)$parsed)->format('Y-m-d H:i:s'),
     'created_at'=>now(), 'updated_at'=>now()]);
    \App\Services\LiveBookingIntakeReview::record($m,$created?'booking_created':'duplicate_booking',$receivedAt,$created?'imported':'duplicate');
    if ($created) { $imported++; $lead=(int)DB::table('live_bookings')->where('source_message_id',$id)->value('id'); \App\Services\LiveBookingAlerts::plan($lead); }
    else { $skip('duplicate_booking'); }
   } catch (\Throwable $e) { $failed=true; Log::warning('Live booking intake failed', ['message_id'=>$id, 'error'=>$e->getMessage()]); }
  }
  return response()->json(['status'=>$failed?'retry':'ok', 'imported'=>$imported, 'ignored'=>$ignored, 'reviewed'=>$reviewed, 'reasons'=>$reasons], $failed?503:200);
 }
}
