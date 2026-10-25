<?php

namespace App\Console\Commands;

use App\Models\Vehicle;
use App\Services\VendorLifecycleDelivery;
use App\Services\VendorMonthlyBilling;
use App\Services\VendorVehicleLifecycle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessVendorVehicleLifecycle extends Command
{
    protected $signature = 'vendor-vehicles:process {--retry-failed : Retry explicitly unsent/configuration failures only} {--limit=100 : Maximum notification deliveries per run}';
    protected $description = 'Expire vehicle agreements, queue renewal reminders and generate configured monthly vendor bills';

    public function handle(): int
    {
        if (! config('vendor_lifecycle.enabled')) { $this->info('Vehicle lifecycle processing is disabled.'); return self::SUCCESS; }
        $result = app(VendorVehicleLifecycle::class)->run();
        $billCount = 0; $errors = 0;
        if (config('vendor_lifecycle.billing_enabled')) {
            Vehicle::query()->where('service_type', 'self_drive')->where('partner_payout_mode', 'monthly')
                ->chunkById(100, function ($vehicles) use (&$billCount, &$errors): void {
                    foreach ($vehicles as $vehicle) {
                        try { $billCount += app(VendorMonthlyBilling::class)->generateForVehicle($vehicle->id); }
                        catch (\Throwable $exception) {
                            $errors++;
                            Log::error('Monthly vendor bill needs review', ['vehicle_id' => $vehicle->id, 'message' => $exception->getMessage()]);
                            $this->error('Vehicle ' . $vehicle->id . ': ' . $exception->getMessage());
                        }
                    }
                });
        }
        $sent = app(VendorLifecycleDelivery::class)->dispatchPending((bool) $this->option('retry-failed'), max(1, min(1000, (int) $this->option('limit'))));
        $this->info('Expired: ' . $result['expired_vehicles'] . '; new bills: ' . $billCount . '; notifications sent: ' . $sent . '; billing errors: ' . $errors);
        return $errors ? self::FAILURE : self::SUCCESS;
    }
}
