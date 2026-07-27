<?php

namespace App\Livewire;

use App\Models\Order;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

class MyOrdersPage extends Component
{
    use WithPagination;

    public function render()
    {
        abort_unless(auth()->check(), 401);

        $my_orders = Order::query()
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(10);
        return view('livewire.my-orders-page', [
            'orders' => $my_orders,
        ]);
    }
}
