<?php

namespace App\Providers;

use App\Console\Commands\ProcessVendorVehicleLifecycle;
use Illuminate\Support\ServiceProvider;

class VendorLifecycleServiceProvider extends ServiceProvider
{
    public function register(): void { $this->mergeConfigFrom(config_path('vendor_lifecycle.php'), 'vendor_lifecycle'); }
    public function boot(): void
    {
        $this->loadRoutesFrom(base_path('routes/vendor_lifecycle.php'));
        $this->commands([ProcessVendorVehicleLifecycle::class]);
    }
}
