<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/*
|--------------------------------------------------------------------------
| Customer Journey Events
|--------------------------------------------------------------------------
*/

use App\Events\SearchPerformed;
use App\Events\CheckoutStarted;
use App\Events\PaymentStarted;
use App\Events\PaymentSucceeded;
use App\Events\PaymentFailed;
use App\Events\BookingCompleted;
use App\Events\BookingCancelled;

/*
|--------------------------------------------------------------------------
| Customer Journey Listeners
|--------------------------------------------------------------------------
*/

use App\Listeners\HandleSearchPerformed;
use App\Listeners\HandleCheckoutStarted;
use App\Listeners\HandlePaymentStarted;
use App\Listeners\HandlePaymentSucceeded;
use App\Listeners\HandlePaymentFailed;
use App\Listeners\HandleBookingCompleted;
use App\Listeners\HandleBookingCancelled;

use App\Listeners\GenerateInvoiceAfterBookingCompleted;
use App\Listeners\NotifyCustomerAfterBookingCompleted;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings.
     *
     * @var array<class-string, array<int,class-string>>
     */
    protected $listen = [

        SearchPerformed::class => [
            HandleSearchPerformed::class,
        ],

        CheckoutStarted::class => [
            HandleCheckoutStarted::class,
        ],

        PaymentStarted::class => [
            HandlePaymentStarted::class,
        ],

        PaymentSucceeded::class => [
            HandlePaymentSucceeded::class,
        ],

        PaymentFailed::class => [
            HandlePaymentFailed::class,
        ],

        BookingCompleted::class => [
            HandleBookingCompleted::class,
            GenerateInvoiceAfterBookingCompleted::class,
            NotifyCustomerAfterBookingCompleted::class,
        ],

        BookingCancelled::class => [
            HandleBookingCancelled::class,
        ],
    ];

    /**
     * Register any events.
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Disable auto discovery because we are using
     * explicit event mapping.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}