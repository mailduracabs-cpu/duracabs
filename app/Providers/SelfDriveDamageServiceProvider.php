<?php
namespace App\Providers;
use Illuminate\Support\ServiceProvider;
use App\Console\Commands\ProcessSelfDriveDamage;
class SelfDriveDamageServiceProvider extends ServiceProvider {
    public function register(): void { $this->mergeConfigFrom(config_path('self_drive_damage.php'),'self_drive_damage'); }
    public function boot(): void {
        $this->loadRoutesFrom(base_path('routes/self_drive_damage.php'));
        $this->commands([ProcessSelfDriveDamage::class]);
    }
}
