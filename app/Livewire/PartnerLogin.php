<?php

namespace App\Livewire;

use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PartnerLogin extends Component
{
    public string $step = 'mobile';

    public $mobile;
    public $otp;
    public $generatedOtp;

    public function sendOtp()
    {
        $this->validate([
            'mobile' => 'required|digits_between:10,15',
        ]);

        $user = User::where('mobile', $this->mobile)->first();

        if (! $user) {
            $this->addError('mobile', 'This mobile number is not registered. Please register first.');
            return;
        }

        $this->generatedOtp = (string) rand(100000, 999999);

        session([
            'partner_login_mobile' => $this->mobile,
            'partner_login_otp' => $this->generatedOtp,
        ]);

        $message = "*Dura Cabs Partner Login OTP*\n\n";
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

        if ($this->mobile != session('partner_login_mobile')) {
            $this->addError('otp', 'Session expired. Please try again.');
            $this->step = 'mobile';
            return;
        }

        if ($this->otp != session('partner_login_otp')) {
            $this->addError('otp', 'Invalid OTP.');
            return;
        }

        $user = User::where('mobile', $this->mobile)->first();

        if (! $user) {
            $this->addError('mobile', 'User not found.');
            $this->step = 'mobile';
            return;
        }

        Auth::login($user);

        session()->forget([
            'partner_login_mobile',
            'partner_login_otp',
        ]);

        return redirect('/partner/dashboard');
    }

    public function backToMobile()
    {
        $this->step = 'mobile';
        $this->otp = null;
    }

    public function render()
    {
        return view('livewire.partner-login');
    }
}