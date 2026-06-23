<?php

namespace App\Livewire;

use App\Models\Inquirys;
use Livewire\Component;

class ContactUs extends Component
{

    public $name;
    public $email;
    public $mobile;
    public $message;

    public $toastUpdate = false;


    public function save(){
        $this->validate([
            'name' => 'required|max:255',
            'mobile' => 'required|max:15',
            'email' => 'required|email|max:255',
            'message' => 'required',
        ]);

        // save user 
    $contact = Inquirys::create([
        'name' => $this->name,
        'email' => $this->email,
        'mobile' => $this->mobile,
        'type' => "contact-us",
        'service' => "none",
        'message' => $this->message,
    ]);

    if($contact){
       $this->name = '';
       $this->email = '';
       $this->mobile = '';
       $this->message = '';
       $this->toastUpdate= 'Message Sent Succesfully!! Thank You :)';
    }

   


    }
    public function render()
    {
        return view('livewire.contact-us');
    }
}
