<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Url;
use illuminate\Http\Request;
use Razorpay\Api\Api;
use Illuminate\Http\RedirectResponse;



class RazorePay extends Component
{

    #[Url(history: true)]
    public $amount  ;
    

    #[Url(history: true)]
    public $name ;

    #[Url(history: true)]
    public $email ;

    #[Url(history: true)]
    public $phone ;

    #[Url(history: true)]
    public $id ;



    public function render()
    {
        return view('livewire.razore-pay');
    }

    
}
