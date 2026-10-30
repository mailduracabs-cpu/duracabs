<?php
namespace App\Console\Commands;
use App\Services\PartnerMarketplaceAlerts;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class DispatchPartnerMarketAlerts extends Command {
 protected $signature='partner-market:dispatch-alerts {--limit=200}';
 protected $description='Plan location matches and dispatch due booking alerts';
 public function handle(): int {
  $limit=max(1,min(1000,(int)$this->option('limit')));
  DB::table('partner_market_alert_devices')->where('status','checking')->where('claimed_at','<',now()->subMinutes(5))->update(['status'=>'unknown','error'=>'worker_interrupted']);
  DB::table('partner_market_alerts')->where('whatsapp_status','checking')->where('updated_at','<',now()->subMinutes(5))->update(['whatsapp_status'=>'unknown','last_error'=>'worker_interrupted']);
  foreach(DB::table('partner_market_posts')->where('kind','booking')->where('status','open')->whereNull('alerts_planned_at')->whereNotNull('pickup_latitude')->where('pickup_at','>',now())->orderByDesc('id')->limit($limit)->pluck('id') as $id)try{PartnerMarketplaceAlerts::plan((int)$id);}catch(\Throwable){Log::warning('Marketplace alert plan deferred',['post_id'=>$id]);}
  foreach(DB::table('partner_market_alerts')->where('available_at','<=',now())->where('expires_at','>',now())->orderBy('last_checked_at')->orderBy('id')->limit($limit)->pluck('id') as $id)try{PartnerMarketplaceAlerts::deliver((int)$id);}catch(\Throwable){Log::warning('Marketplace alert send deferred',['alert_id'=>$id]);}
  return self::SUCCESS;
 }
}
