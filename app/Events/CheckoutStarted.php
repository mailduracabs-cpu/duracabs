<?php

namespace App\Events;

use App\Models\CustomerSearchActivity;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CheckoutStarted
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public CustomerSearchActivity $searchActivity,
        public array $checkoutData = []
    ) {
    }
}