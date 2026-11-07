<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Services\PartnerMarketSavedAlerts;
class DispatchSavedMarketAlerts extends Command {
 protected $signature='partner-market:dispatch-saved-alerts';
 protected $description='Match saved vendor filters and deliver chat, booking and assignment alerts';
 public function handle(): int {PartnerMarketSavedAlerts::tick();return self::SUCCESS;}
}
