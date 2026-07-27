<?php

namespace App\Livewire\Partials;

use Livewire\Attributes\On;
use Livewire\Component;

class Navbar extends Component
{
    public int $total_count = 0;

    public function mount(): void
    {
        /*
         * The old cart system has been removed.
         * Keep this property for Blade compatibility so the navbar view
         * does not fail if it still displays the cart count.
         */
        $this->total_count = session()->has('booking_draft') ? 1 : 0;
    }

    #[On('update-cart-count')]
    public function updateCartCount(mixed $total_count = 0): void
    {
        $this->total_count = max(0, (int) $total_count);
    }

    #[On('booking-draft-updated')]
    public function refreshBookingDraftCount(): void
    {
        $this->total_count = session()->has('booking_draft') ? 1 : 0;
    }

    public function render()
    {
        return view('livewire.partials.navbar');
    }
}