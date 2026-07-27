<?php

namespace App\Events;

use App\Models\CustomerSearchActivity;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SearchPerformed
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public CustomerSearchActivity $searchActivity
    ) {
    }
}