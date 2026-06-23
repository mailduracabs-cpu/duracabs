<?php

namespace App\Livewire;

use App\Models\Banners;
use Livewire\Component;

class CaroselCard extends Component
{
    public $images = [
        'bg3.jpg',
        'banner.webp',
        'banner2.webp',
    ];

    public $bannerTab;

    public function changeBanner($value): void
    {
        $this->bannerTab = $value;

    }

    

  

    public function render()
    {
        $carousel = Banners::where('ride_type',  $this->bannerTab)->get();
        return view('livewire.carosel-card',[
            'carousel' => $carousel,
        ]);
    }
}
