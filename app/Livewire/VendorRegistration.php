<?php

namespace App\Livewire;

use App\Models\address;
use App\Models\FleetManagement\TransporterProfile;
use App\Models\Inquirys;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class VendorRegistration extends Component
{
    public string $step = 'mobile';
    public string $mode = 'register';

    public $partnerType = 'vendor';
    public $mobile;
    public $otp;
    public $generatedOtp;

    public $name;
    public $email;
    public $password;
    public $companyName;
    public $city;
    public $state;
    public $address;

    public function sendOtp()
    {
        $this->validate([
            'partnerType' => 'required|in:host,vendor,both',
            'mobile' => 'required|digits_between:10,15',
        ]);

        if (User::where('mobile', $this->mobile)->exists()) {
            $this->addError('mobile', 'This mobile number is already registered. Please login.');
            return;
        }

        $this->generatedOtp = (string) rand(100000, 999999);
        session(['partner_registration_otp' => $this->generatedOtp]);

        $message = "*Dura Cabs Partner Registration OTP*\n\n";
        $message .= "Your OTP is: *{$this->generatedOtp}*\n\n";
        $message .= "Do not share this OTP with anyone.";

        WhatsAppService::send($this->mobile, $message);

        $this->step = 'otp';
    }

    public function verifyOtp()
    {
        $this->validate([
            'otp' => 'required|digits:6',
        ]);

        if ($this->otp !== session('partner_registration_otp')) {
            $this->addError('otp', 'Invalid OTP.');
            return;
        }

        $this->step = 'profile';
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|max:255',
            'companyName' => 'required|max:255',
            'email' => 'required|email|unique:users,email|max:255',
            'password' => 'required|min:6|max:255',
            'city' => 'required|max:255',
            'state' => 'required|max:255',
            'address' => 'required|max:255',
        ]);

        if (User::where('mobile', $this->mobile)->exists()) {
            $this->addError('mobile', 'This mobile number is already registered. Please login.');
            return;
        }

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'company_name' => $this->companyName,
            'password' => Hash::make($this->password),
        ]);

        $user->assignRole(2);

        TransporterProfile::create([
            'user_id' => $user->id,
            'partner_type' => $this->partnerType,
            'company_name' => $this->companyName,
            'contact_person' => $this->name,
            'mobile' => $this->mobile,
            'email' => $this->email,
            'office_address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'status' => true,
            'verification_status' => 'pending',
        ]);

        Inquirys::create([
            'mobile' => $this->mobile,
            'service' => 'none',
            'type' => 'vendor',
            'message' => 'Partner Type: ' . $this->partnerType . ' | Company: ' . $this->companyName . ' | Name: ' . $this->name,
        ]);

        address::create([
            'full_name' => $this->name,
            'phone' => $this->mobile,
            'state' => $this->state,
            'city' => $this->city,
            'pickup_address' => $this->address,
            'user_id' => $user->id,
        ]);

        auth()->login($user);

        session()->forget('partner_registration_otp');

        return redirect('/partner/dashboard');
    }

    public function render()
    {
        return view('livewire.vendor-registration');
    }
}