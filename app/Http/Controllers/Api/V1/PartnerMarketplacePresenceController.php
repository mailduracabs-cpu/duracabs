<?php
namespace App\Http\Controllers\Api\V1;
use App\Models\FleetManagement\TransporterProfile;
use App\Services\PartnerMarketplacePlaces;
use App\Services\PartnerMarketplaceAlerts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
class PartnerMarketplacePresenceController extends PartnerController {
 private function vendor(Request $r): TransporterProfile {[$v,$role]=$this->context($r);abort_unless($role==='vendor'&&$v->isActive(),403);return $v;}
 public function show(Request $r){$v=$this->vendor($r);$p=DB::table('partner_market_presence')->where('vendor_id',$v->id)->first();return response()->json(['status'=>true,'data'=>['preferences'=>$p,'firebase_configured'=>is_file((string)config('partner_market.firebase_credentials')),'whatsapp_configured'=>(bool)config('partner_market.whatsapp_enabled'),'free_cabs'=>DB::table('partner_market_free_cabs')->where('vendor_id',$v->id)->where('is_free',true)->where('available_until','>',now())->get()]])->header('Cache-Control','no-store, private');}
 public function save(Request $r){
  $v=$this->vendor($r);$i=$r->validate(['app_alerts'=>['required','boolean'],'whatsapp_alerts'=>['required','boolean'],'gps_enabled'=>['required','boolean'],'expected_revision'=>['required','integer','min:0'],'search_place_id'=>['nullable','string','max:255'],'clear_search'=>['sometimes','boolean']]);
  $place=!empty($i['search_place_id'])?PartnerMarketplacePlaces::details($i['search_place_id']):null;
  if($place)abort_unless($place['latitude']!==null&&$place['longitude']!==null,422,'Google search city coordinates unavailable. Select another city suggestion.');
  DB::transaction(function()use($v,$i,$place){
   DB::table('fleet_transporter_profiles')->where('id',$v->id)->lockForUpdate()->first();$p=DB::table('partner_market_presence')->where('vendor_id',$v->id)->first();
   abort_unless(($p?(int)$p->revision:0)===(int)$i['expected_revision'],409,'Alert settings changed. Refresh before saving.');
   $values=['app_alerts'=>$i['app_alerts'],'whatsapp_alerts'=>$i['whatsapp_alerts'],'gps_enabled'=>$i['gps_enabled'],'revision'=>($p?(int)$p->revision:0)+1,'updated_at'=>now()];
   if(!$i['gps_enabled'])$values+=['latitude'=>null,'longitude'=>null,'accuracy'=>null,'located_at'=>null];
   if($place)$values+=['search_place_id'=>$i['search_place_id'],'search_city'=>$place['label'],'search_latitude'=>$place['latitude'],'search_longitude'=>$place['longitude'],'search_until'=>now()->addHours(12)];
   elseif(!empty($i['clear_search']))$values+=['search_place_id'=>null,'search_city'=>null,'search_latitude'=>null,'search_longitude'=>null,'search_until'=>null];
   if($p)DB::table('partner_market_presence')->where('vendor_id',$v->id)->update($values);else DB::table('partner_market_presence')->insert($values+['vendor_id'=>$v->id,'created_at'=>now()]);
  },3);return $this->show($r);
 }
 public function location(Request $r){$v=$this->vendor($r);$i=$r->validate(['latitude'=>['required','numeric','between:-90,90'],'longitude'=>['required','numeric','between:-180,180'],'accuracy'=>['required','numeric','min:0','max:10000'],'captured_at'=>['required','date','after:'.now()->subMinutes(2)->toIso8601String(),'before:'.now()->addMinute()->toIso8601String()]]);
  $captured=\Carbon\Carbon::parse($i['captured_at'])->min(now())->setTimezone(config('app.timezone'));
  DB::table('partner_market_presence')->where('vendor_id',$v->id)->where('gps_enabled',true)->where(function($q)use($captured){$q->whereNull('located_at')->orWhere('located_at','<=',$captured);})->update(['latitude'=>$i['latitude'],'longitude'=>$i['longitude'],'accuracy'=>$i['accuracy'],'located_at'=>$captured,'updated_at'=>now()]);
  abort_unless(DB::table('partner_market_presence')->where('vendor_id',$v->id)->where('gps_enabled',true)->exists(),409,'Automatic location is off. Enable it in Booking Alerts.');return response()->json(['status'=>true]);}
 public function stopLocation(Request $r){$v=$this->vendor($r);DB::table('partner_market_presence')->where('vendor_id',$v->id)->update(['latitude'=>null,'longitude'=>null,'accuracy'=>null,'located_at'=>null,'updated_at'=>now()]);return response()->json(['status'=>true]);}
 public function device(Request $r){$v=$this->vendor($r);$i=$r->validate(['token'=>['required','string','min:20','max:4096','regex:/^\S+$/'],'platform'=>['required','in:android,ios,web']]);DB::table('partner_market_devices')->upsert([['vendor_id'=>$v->id,'token_hash'=>hash('sha256',$i['token']),'encrypted_token'=>Crypt::encryptString($i['token']),'platform'=>$i['platform'],'seen_at'=>now()]],['token_hash'],['vendor_id','encrypted_token','platform','seen_at']);return response()->json(['status'=>true]);}
 public function removeDevice(Request $r){$v=$this->vendor($r);$i=$r->validate(['token'=>['required','string','max:4096']]);DB::table('partner_market_devices')->where('vendor_id',$v->id)->where('token_hash',hash('sha256',$i['token']))->delete();return response()->json(['status'=>true]);}
 public function alerts(Request $r){$v=$this->vendor($r);$p=DB::table('partner_market_presence')->where('vendor_id',$v->id)->first();$items=[];
  if($p&&$p->app_alerts){$rows=DB::table('partner_market_alerts as a')->join('partner_market_posts as b','b.id','=','a.post_id')->where('a.vendor_id',$v->id)->where('a.available_at','<=',now())->where('a.expires_at','>',now())->where('b.status','open')->whereColumn('b.revision','a.post_revision')->select('a.*','b.pickup_city','b.drop_city','b.vehicle_class','b.pickup_at')->orderByDesc('a.available_at')->orderByDesc('a.id')->limit(50)->get();$items=$rows->filter(function($row)use($p){$post=DB::table('partner_market_posts')->find($row->post_id);$match=$post?PartnerMarketplaceAlerts::eligible($post,$p):null;return $match&&((int)$row->tier!==0||$match['tier']===0);})->values()->all();}
  return response()->json(['status'=>true,'data'=>['items'=>$items,'server_time'=>now()->toIso8601String()]])->header('Cache-Control','no-store, private');}
 public function read(Request $r,int $alert){$v=$this->vendor($r);DB::table('partner_market_alerts')->where('id',$alert)->where('vendor_id',$v->id)->whereNull('read_at')->update(['read_at'=>now()]);return response()->json(['status'=>true]);}
}
