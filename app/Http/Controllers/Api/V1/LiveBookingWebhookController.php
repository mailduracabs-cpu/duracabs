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
  $payload = $r->json()->all(); $imported = 0; $ignored = 0; $failed = false; $reasons = [];
  $skip = function (string $reason) use (&$ignored, &$reasons) { $ignored++; $reasons[$reason] = ($reasons[$reason] ?? 0) + 1; };
  foreach (($payload['entry'] ?? []) as $entry) foreach (($entry['changes'] ?? []) as $change) foreach (($change['value']['messages'] ?? []) as $m) {
   $group = (string) ($m['group_id'] ?? '');
   $allowed = (array) config('live_bookings.source_group_ids', []);
   // QR bridge selection is managed by its authenticated, loopback-only admin UI.
   // Cloud API messages continue to require the existing server-side allowlist.
   $sourceAllowed = $qr ? str_ends_with($group, '@g.us') : ($allowed && in_array($group, $allowed, true));
   $timestamp = (int) ($m['timestamp'] ?? 0);
   if ($group === '' || strlen($group)>190 || !$sourceAllowed || ($m['type'] ?? '') !== 'text' ||
       ($qr && ($timestamp < time()-300 || $timestamp > time()+30))) { $skip('source_or_time'); continue; }
   $id = (string) ($m['id'] ?? ''); $text = (string) ($m['text']['body'] ?? '');
   if ($id === '' || strlen($id)>191 || $text === '' || strlen((string)($m['from'] ?? ''))>40) { $skip('invalid_payload'); continue; }
   $receivedAt = $qr ? \Carbon\Carbon::createFromTimestamp($timestamp, config('app.timezone')) : null;
   $parsed = LiveBookingMessageParser::parse($text, $receivedAt);
   if (!$parsed) $parsed = LiveBookingAiParser::parse($text, $receivedAt);
   if (!$parsed) { $skip('incomplete_booking'); continue; }
   if (\App\Services\PartnerMarketplaceSchedule::due((object) $parsed)) { $skip('expired_booking'); continue; }
   try {
    $created = DB::table('live_bookings')->insertOrIgnore($parsed + [
     'source_message_id'=>$id, 'source_group_id'=>$group, 'source_sender'=>(string)($m['from'] ?? ''),
     'status'=>'open', 'expires_at'=>\App\Services\PartnerMarketplaceSchedule::deadline((object)$parsed)->format('Y-m-d H:i:s'),
     'created_at'=>now(), 'updated_at'=>now()]);
    if ($created) { $imported++; $lead=(int)DB::table('live_bookings')->where('source_message_id',$id)->value('id'); \App\Services\LiveBookingAlerts::plan($lead); }
    else { $skip('duplicate'); }
   } catch (\Throwable $e) { $failed=true; Log::warning('Live booking intake failed', ['message_id'=>$id, 'error'=>$e->getMessage()]); }
  }
  return response()->json(['status'=>$failed?'retry':'ok', 'imported'=>$imported, 'ignored'=>$ignored, 'reasons'=>$reasons], $failed?503:200);
 }
}
