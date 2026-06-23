<?php

namespace App\Livewire;

use App\Models\address;
use App\Models\Inquirys;
use App\Services\WhatsAppService;
use Livewire\Component;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserRegister;
use Illuminate\Support\Facades\Hash;
use App\Models\User;




class VendorRegistration extends Component
{

    public $name;
    public $email;
    public $mobile;
    public $password;
    public $companyName;
    public $city;
    public $state;
    public $address;

    public function save(){
        $this->validate([
            'name' => 'required|max:255',
            'companyName' => 'required',
            'mobile' => 'required|max:15',
            'email' => 'required|email|unique:users|max:255',
            'password' => 'required|min:6|max:255',
            'city' => 'required|max:255',
            'state' => 'required|max:255',
            'address' => 'required|max:255',
        ]);

        // save user 
    $user = User::create([
        'name' => $this->name,
        'email' => $this->email,
        'mobile' => $this->mobile,
        'company_name' => $this->companyName,
        'password' => Hash::make($this->password),
        
        
    ]);

         $inquiry = Inquirys::create([
            'mobile' => $this->mobile,
            'service' => 'none',
            'type' => "vendor",
            'message' => 'Company Name: ' . $this->companyName .' Name: '.$this->name,  
        ]);

    $address = address::create([
        'full_name' => $this->name,
        'phone' => $this->mobile,
        'state' => $this->state,
        'city' => $this->city,
        'pickup_address' => $this->address,
        'user_id' => $user->id,
    ]);


    $user->assignRole( 2 );

    // Send WhatsApp message to vendor after registration
    if ($this->mobile) {
        $loginUrl = route('login');
        $message = "Dear User,\n\n";
        $message .= "We are happy to inform that your request for  registration has been approved.\n\n";
        $message .= "Kindly find the user id details below :\n\n";
        $message .= "User ID : " . $this->email . "\n";
        $message .= "Password: " . $this->password . "\n\n";
        $message .= "Click   and login: " . $loginUrl;

        WhatsAppService::send($this->mobile, $message);
    }

    // Send WhatsApp message to admin about new vendor registration
    $adminMobile = env('ADMIN_MOBILE');
    if ($adminMobile) {
        $adminMessage = "Congratulation! Have received New Vendor ship  Form \n\n\n";
        $adminMessage .= "Kindly find the user id details below :\n\n";
        $adminMessage .= "Name : " . $this->name . "\n\n";
        $adminMessage .= "Mobile Number:" . $this->mobile . "\n\n";
        $adminMessage .= "Email Id:" . $this->email . "\n\n";
        $adminMessage .= "Address: " . ($this->address . ', ' . $this->city . ', ' . $this->state) . "\n\n";
        $adminMessage .= "Company Name : " . $this->companyName . "\n";

        WhatsAppService::send($adminMobile, $adminMessage);
    }

   
    // login user
    // auth()->login($user);

    //     Mail::to(users: request()->user())->send(new UserRegister(user: $user));
       return redirect('/admin');
    }
    public function render()
    {
        return view('livewire.vendor-registration');
    }
}
