<?php
namespace App\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
class ExpirePartnerMarketplaceBooking implements ShouldQueue {
 use Dispatchable,InteractsWithQueue,Queueable,SerializesModels;
 public int $tries=3; public int $timeout=30;
 public function __construct(public int $postId){}
 public function handle():void {\App\Services\PartnerMarketplaceSchedule::expireDue($this->postId);}
}
