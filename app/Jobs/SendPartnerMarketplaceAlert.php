<?php
namespace App\Jobs;
use App\Services\PartnerMarketplaceAlerts;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
class SendPartnerMarketplaceAlert implements ShouldQueue {
 use Dispatchable,InteractsWithQueue,Queueable,SerializesModels;
 public int $tries=1; public int $timeout=120;
 public function __construct(public int $alertId){}
 public function handle(): void { PartnerMarketplaceAlerts::deliver($this->alertId); }
}
