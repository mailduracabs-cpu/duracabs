<?php
namespace App\Http\Controllers\Api\V1;
use Illuminate\Http\Request;
class PartnerMarketPostPolicyController extends PartnerController {
 public function show(Request $request) {
  [$profile,$role]=$this->context($request);abort_unless($role==='vendor'&&$profile->isActive(),403);
  return response()->json(['status'=>true,'data'=>['post_policy_version'=>1,'minimum_commission'=>100,'communication_modes'=>['chat_only','call_chat']]])->header('Cache-Control','private, no-store');
 }
}
