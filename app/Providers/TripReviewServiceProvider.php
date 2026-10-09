<?php
namespace App\Providers;

use App\Http\Middleware\EnforceTripReviewRestrictions;
use App\Observers\TripRestrictionObserver;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class TripReviewServiceProvider extends ServiceProvider
{
    public function boot(Router $router): void
    {
        $this->loadRoutesFrom(base_path('routes/trip_reviews.php'));
        $router->pushMiddlewareToGroup('api', EnforceTripReviewRestrictions::class);
        $router->pushMiddlewareToGroup('web', EnforceTripReviewRestrictions::class);
        foreach ([\App\Models\Order::class, \App\Models\SelfDriveBooking::class,
            \App\Models\Vehicle::class, \App\Models\SelfDriveVehicle::class] as $model) {
            if (class_exists($model)) $model::observe(TripRestrictionObserver::class);
        }
    }
}
