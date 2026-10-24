<?php
namespace App\Providers;
use App\Services\CustomerReferralService;
use App\Console\Commands\SyncCustomerReferralRewards;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class CustomerWalletReferralServiceProvider extends ServiceProvider {
    public function register(): void { $this->mergeConfigFrom(config_path('customer_referrals.php'), 'customer_referrals'); }
    public function boot(): void {
        $this->loadRoutesFrom(base_path('routes/customer_referrals.php'));
        $this->commands([SyncCustomerReferralRewards::class]);
        foreach ([\App\Models\Order::class => 'order', \App\Models\SelfDriveBooking::class => 'self_drive'] as $class => $source) {
            $class::saved(function ($booking) use ($source) {
                $id = (int) $booking->id;
                DB::afterCommit(function () use ($source, $id) {
                    try { app(CustomerReferralService::class)->syncBooking($source, $id); }
                    catch (\Throwable $e) { Log::error('Referral reward reconciliation failed', ['source' => $source, 'booking_id' => $id, 'error' => $e->getMessage()]); }
                });
            });
        }
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('customer-referrals:sync')->everyFiveMinutes()->withoutOverlapping();
        });
    }
}
