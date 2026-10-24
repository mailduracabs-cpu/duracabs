<?php
namespace App\Console\Commands;
use App\Services\CustomerReferralService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
class SyncCustomerReferralRewards extends Command {
    protected $signature = 'customer-referrals:sync';
    protected $description = 'Reconcile completed referral bookings and refund reversals';
    public function handle(CustomerReferralService $service): int {
        if (! Schema::hasTable('customer_referrals')) { $this->warn('Referral migration is not installed.'); return self::FAILURE; }
        foreach (['order' => ['orders', 'user_id'], 'self_drive' => ['self_drive_bookings', 'customer_id']] as $source => [$table, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) continue;
            DB::table($table)->whereIn($column, DB::table('customer_referrals')->select('customer_id'))->select('id')->orderBy('id')->chunkById(200, function ($rows) use ($service, $source) {
                foreach ($rows as $row) $service->syncBooking($source, (int) $row->id);
            });
        }
        $this->info('Referral rewards reconciled.'); return self::SUCCESS;
    }
}
