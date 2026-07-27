<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\FleetManagement\TransporterProfile;
use App\Models\SelfDriveVendor;
use App\Observers\TransporterProfileObserver;
use App\Observers\SelfDriveVendorObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if(env('APP_ENV') === 'production') {
            $url = \Request::url();
            $check = strstr($url, 'http://');
            if($check){
                $newUrl = str_replace('http', 'https', $url);
                header("Location:" . $newUrl);
            }
        }
		TransporterProfile::observe(TransporterProfileObserver::class);
		SelfDriveVendor::observe(SelfDriveVendorObserver::class);
		
		
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : null;
        });
    }
}
