<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
/** Recruiting expiry is separate from a paid/assigned trip and its payment reservation. */
class PartnerMarketplaceSchedule {
 public const GRACE_SECONDS=1800;
 private static function zone(): \DateTimeZone {return new \DateTimeZone(function_exists('config')?(config('app.timezone')?:'UTC'):'UTC');}
 public static function deadline(object $post): ?\DateTimeImmutable {
  if(($post->kind??'booking')!=='booking'||empty($post->pickup_at))return null;
  return (new \DateTimeImmutable((string)$post->pickup_at,self::zone()))->modify('+'.self::GRACE_SECONDS.' seconds');
 }
 public static function due(object $post,?\DateTimeInterface $at=null): bool { $end=self::deadline($post);return $end&&($at??now())->getTimestamp()>=$end->getTimestamp(); }
 public static function pastMinute(string $pickup, ?\DateTimeInterface $at=null):bool { return intdiv((new \DateTimeImmutable($pickup,self::zone()))->getTimestamp(),60) < intdiv(($at??now())->getTimestamp(),60); }
 public static function assertAccepting(object $post): void {abort_if(self::due($post),409,'Booking expired 30 minutes after pickup time. New edits, requests and assignments are closed.');}
 public static function queueExpiry(int $id):void {
  if(config('queue.default')==='sync')return;
  try {$post=DB::table('partner_market_posts')->find($id);$end=$post?self::deadline($post):null;if($post&&$post->status==='open'&&$end)\App\Jobs\ExpirePartnerMarketplaceBooking::dispatch($id)->delay($end);}
  catch(\Throwable){\Illuminate\Support\Facades\Log::warning('Booking expiry job deferred',['post_id'=>$id]);}
 }
 public static function expireDue(?int $post=null): int {
  $q=DB::table('partner_market_posts')->where('kind','booking')->where('status','open')->whereNull('active_payment_id')->whereNull('assigned_payment_id')->where('pickup_at','<=',now()->subSeconds(self::GRACE_SECONDS));
  if($post!==null)$q->where('id',$post);
  return $q->update(['status'=>'expired','updated_at'=>now()]);
 }
 public static function stamp(object $post): object {
  if(isset($post->drop_cities)&&is_string($post->drop_cities))$post->drop_cities=json_decode($post->drop_cities,true)??[];
  $post->booking_expires_at=self::deadline($post)?->format(DATE_ATOM);
  if(($post->status??'')==='open'&&empty($post->active_payment_id)&&empty($post->assigned_payment_id)&&self::due($post))$post->status='expired';
  foreach(['pickup_at','return_at'] as $key)if(!empty($post->$key))$post->$key=(new \DateTimeImmutable((string)$post->$key,self::zone()))->format(DATE_ATOM);
  return $post;
 }
 private static function tripZone(): \DateTimeZone {return new \DateTimeZone(function_exists('config')?(config('partner_market.trip_timezone')?:'Asia/Kolkata'):'Asia/Kolkata');}
 public static function days(string $pickup,string $return): int {
  $a=(new \DateTimeImmutable($pickup,self::zone()))->setTimezone(self::tripZone())->setTime(0,0);$b=(new \DateTimeImmutable($return,self::zone()))->setTimezone(self::tripZone())->setTime(0,0);
  if($b<$a)throw new \InvalidArgumentException('End date must be on or after pickup date.');return (int)$a->diff($b)->days+1;
 }
 public static function returnAt(string $pickup,int $days): string {
  if($days<1||$days>365)throw new \InvalidArgumentException('Total Days must be between 1 and 365.');
  return (new \DateTimeImmutable($pickup,self::zone()))->setTimezone(self::tripZone())->setTime(0,0)->modify('+'.($days-1).' days')->setTime(23,59,59)->format(DATE_ATOM);
 }
 public static function expireLiveDue(): int {
  // Existing expiry timestamps are normalized once by migration, not on every refresh.
  return DB::table('live_bookings')->whereIn('status',['open','pending_confirmation'])->where('expires_at','<=',now())->update(['status'=>'expired','confirmed_vendor_id'=>null,'confirm_token_hash'=>null,'confirm_token_expires_at'=>null,'updated_at'=>now()]);
 }
}
