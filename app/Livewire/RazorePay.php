<?php

namespace App\Livewire;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Secure Payment - Dura Cabs')]
class RazorePay extends Component
{
    #[Url(history: true)]
    public ?int $id = null;

    public function render()
    {
        abort_unless(Auth::check(), 401);

        $order = Order::query()
            ->with('address')
            ->whereKey($this->id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        abort_if((float) $order->grand_total <= 0, 403, 'Invalid booking amount.');

        return view('livewire.razore-pay', [
            'order' => $order,
            'customerName' => $order->address?->full_name ?? Auth::user()?->name ?? '',
            'customerEmail' => $order->address?->email ?? Auth::user()?->email ?? '',
            'customerPhone' => $order->address?->phone ?? Auth::user()?->mobile ?? '',
        ]);
    }
}
