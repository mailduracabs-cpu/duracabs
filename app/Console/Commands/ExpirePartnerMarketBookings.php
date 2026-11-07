<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Services\PartnerMarketplaceSchedule;
class ExpirePartnerMarketBookings extends Command {
 protected $signature='partner-market:expire-bookings';
 protected $description='Expire unassigned marketplace and live bookings one minute after pickup';
 public function handle():int {$market=PartnerMarketplaceSchedule::expireDue();$live=PartnerMarketplaceSchedule::expireLiveDue();$this->info("Expired marketplace: $market; live: $live. Pending payments and assigned trips retained.");return self::SUCCESS;}
}
