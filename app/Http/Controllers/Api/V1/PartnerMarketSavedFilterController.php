<?php
namespace App\Http\Controllers\Api\V1;
use App\Services\PartnerMarketplaceFilters as Filters;
use App\Services\PartnerMarketSavedAlerts as Alerts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
class PartnerMarketSavedFilterController extends PartnerController {
 private function vendor(Request $r): int {[$p,$role]=$this->context($r);abort_unless($role==='vendor'&&$p->isActive(),403);return (int)$p->id;}
 public function show(Request $r) { $id=$this->vendor($r);return response()->json(['status'=>true,'data'=>DB::table('partner_market_saved_filters')->where('vendor_id',$id)->first()]); }
 public function update(Request $r) {
  $id=$this->vendor($r);$data=$r->validate(['action'=>['required',Rule::in(['save','found','delete'])]]+Filters::rules());
  DB::transaction(function()use($id,$data){
   // Lock an existing owner row so first-save races cannot bypass the unique filter.
   DB::table('fleet_transporter_profiles')->where('id',$id)->lockForUpdate()->first();
   $q=DB::table('partner_market_saved_filters')->where('vendor_id',$id);$old=(clone $q)->first();
   if($data['action']==='delete'){if($old){DB::table('partner_market_events')->where('filter_id',$old->id)->delete();$q->delete();}return;}
   if($data['action']==='found'){if($old)$q->update(['active'=>false,'version'=>$old->version+1,'updated_at'=>now()]);return;}
   $spec=array_diff_key($data,array_flip(['action','post_status','search']));
   abort_if(!array_filter($spec),422,'Select a car type, city, date or trip type first.');
   $baseline=(int)DB::table('partner_market_posts')->max('id');
   $values=['start_post_id'=>$baseline,'filters'=>json_encode($spec,JSON_THROW_ON_ERROR),'active'=>true,'version'=>($old->version??0)+1,'cursor'=>$baseline,'updated_at'=>now()];
   if($old)$q->update($values);else DB::table('partner_market_saved_filters')->insert($values+['vendor_id'=>$id,'created_at'=>now()]);
  },3);
  return $this->show($r);
 }
 public function feed(Request $r) {
  $vendor=$this->vendor($r);$r->validate(['after'=>['sometimes','integer','min:0']]);
  $after=(int)$r->query('after',0);
  $rows=DB::table('partner_market_events')->where('vendor_id',$vendor)->where('id','>',$after)->orderBy('id')->limit(100)->get();
  $items=[];$cursor=$after;
  foreach($rows as $e){$cursor=(int)$e->id;if($e->available_at>now()->format('Y-m-d H:i:s')){break;}if(Alerts::valid($e))$items[]=Alerts::payload($e);}
  // Do not advance past a priority-delayed event. Later events are returned next poll.
  if(isset($e)&&$e->available_at>now()->format('Y-m-d H:i:s'))$cursor=max($after,(int)$e->id-1);
  return response()->json(['status'=>true,'data'=>['items'=>$items,'cursor'=>$cursor]]);
 }
}
