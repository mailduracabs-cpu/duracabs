<?php
namespace App\Http\Controllers\Api\V1;
use Illuminate\Http\Request;
use App\Services\PartnerFreeCabDuty;
use App\Services\PartnerMarketplaceAlerts;
use App\Services\LiveBookingMatching;
use Illuminate\Support\Facades\DB;
final class PartnerFreeCabDutyController extends PartnerController {
 private function vendor(Request $r){[$v,$role]=$this->context($r);abort_unless($role==='vendor'&&$v->isActive(),403,'Active vendor required.');return $v;}
 public function index(Request $r){
  $v=$this->vendor($r);$input=$r->validate(['live_booking_id'=>['nullable','integer','min:1']]);
  $b=!empty($input['live_booking_id'])?DB::table('live_bookings')->where('id',$input['live_booking_id'])->first():null;
  $items=[];foreach(PartnerMarketplaceAlerts::freeCabs((int)$v->id)as$cab){$items[]=['vehicle_id'=>$cab['vehicle_id'],'post_id'=>$cab['post_id'],'taxi_name'=>$cab['taxi_name'],'vehicle_class'=>$cab['car_classification'],'city'=>$cab['city'],'matches_booking'=>$b?LiveBookingMatching::cab($b,$cab):true];}
  return response()->json(['status'=>true,'data'=>['items'=>$items]])->header('Cache-Control','private, no-store');
 }
 public function store(Request $r){
  $v=$this->vendor($r);$i=$r->validate(['vehicle_id'=>['required','integer','min:1'],'expected_post_id'=>['required','integer','min:1'],'source'=>['required',\Illuminate\Validation\Rule::in(['taxi_sathi','other'])]]);
  $duty=PartnerFreeCabDuty::record($v,(int)$i['vehicle_id'],(int)$i['expected_post_id'],$i['source']);
  return response()->json(['status'=>true,'message'=>'Duty source saved. This car is no longer listed as free.','data'=>['duty_id'=>$duty->id,'source'=>$duty->source]]);
 }
}
