<?php
declare(strict_types=1);
namespace App\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
final class PartnerMarketplacePostPolicy {
 public const MIN_COMMISSION = 100;
 public static function prepare(array $input, ?object $existing = null): array {
  if (($input['kind'] ?? '') !== 'booking') {$input['communication_mode']='chat_only';return $input;}
  $mode=$input['communication_mode']??($existing?->communication_mode??($existing?'chat_only':null));
  if(!in_array($mode,['chat_only','call_chat'],true))throw ValidationException::withMessages(['communication_mode'=>'Choose In-app chat only or Call + In-app chat before confirming this booking.']);
  $input['communication_mode']=$mode;
  if(!empty($input['secure_booking'])) {
   self::requireCommission($input['vendor_commission']??null);
   if(($input['pricing_mode']??'quote')!=='fixed')throw ValidationException::withMessages(['pricing_mode'=>'Choose fixed booking amount for a paid post. Quote bookings request payment after selecting a vendor.']);
  }
  return $input;
 }
 public static function requireCommission(mixed $commission): void {
  if(!is_numeric($commission)||!is_finite((float)$commission)||(float)$commission<self::MIN_COMMISSION)
   throw ValidationException::withMessages(['vendor_commission'=>'Payment booking requires at least ₹100 commission.']);
 }
 /** Called only after existing detail authorization and priority checks. No phone in list/push payloads. */
 public static function contact(object $post,int $viewer): array {
  $mode=($post->communication_mode??'chat_only')==='call_chat'?'call_chat':'chat_only';
  $result=['mode'=>$mode,'can_call'=>false];
  if($mode!=='call_chat'||($post->kind??'')!=='booking'||(int)$post->transporter_profile_id===$viewer
   ||!in_array($post->status,['open','payment_pending','assigned'],true))return $result;
  if($post->status==='open'&&PartnerMarketplaceSchedule::due($post))return $result;
  $owner=DB::table('fleet_transporter_profiles')->where('id',$post->transporter_profile_id)->whereNull('deleted_at')
   ->where('status',true)->where('verification_status','verified')->whereIn('partner_type',['vendor','both'])->first(['mobile']);
  $phone=preg_replace('/[\s().-]+/','',(string)($owner->mobile??''));
  if(!preg_match('/^\+?[0-9]{8,15}$/',$phone))return $result;
  return array_replace($result,['can_call'=>true,'phone'=>$phone,'owner_vendor_id'=>(string)$post->transporter_profile_id,'label'=>'Call posting vendor']);
 }
}
