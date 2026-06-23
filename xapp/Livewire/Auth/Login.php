<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Services\WhatsAppService;
use Auth;
use GuzzleHttp\Client;
use Livewire\Component;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;



class Login extends Component
{

    public $mobile;
    public $sendOtp = false;
    public $otp;

    public $verifyOtp;
    public $digit1;
    public $digit2;
    public $digit3;
    public $digit4;
    public $otpmessage = 0;

    public $otpError;


    public function save(){
        $this->validate([
            'mobile'=> 'required|max:10',
        ]);

        $otp = rand(1000, 9999);

        $moble_no = $this->mobile;
        $message = "This is your 4-digit OTP '" . $otp . "' for Mobile Number Verification on Duracabs.com. Valid for 5 Minutes only.From DURA CABS";
        $message = str_replace(' ', '%20', $message);

        $api_url = "http://manage.sambsms.com/app/smsapi/index.php?key=3627633B7AC9C6&entity=1701165124480381903&tempid=1507165596189427962&campaign=0&routeid=7&type=text&contacts=$moble_no&senderid=DURACB&msg=$message";

        $client = new Client();

        try {
            $client->get($api_url);
            $this->otp = $otp;
            $this->sendOtp = true;
            
            $this->otpmessage = 1;
            return true;
        } catch (\Exception $e) {
            // Handle any errors that occur during the API request
            return view('api_error', ['error' => $e->getMessage()]);
        }
    }
    
    public function updateMessage(){
        
    } 

    public function verifySubmitOtp()
    {
        $result = $this->otp == ($this->digit1 . $this->digit2 . $this->digit3 . $this->digit4);

        if (!$result) {
            $this->otpError = "Please enter valid OTP";
            return false;
        }

        if (Auth::guest()) {
            $user = User::where('mobile', $this->mobile)->first();  
            if(!$user) {
                $user = User::create([
                    'mobile' => $this->mobile,
                    'name' => $this->mobile,
                    'email' => $this->mobile.'@gmail.com',
                    'password' => encrypt($this->mobile),
                ]);

                // Send WhatsApp message to user after user creation
                $loginUrl = route('login');
                $message = "Dear User,\n\n";
                $message .= "We are happy to inform that your request for registration has been approved.\n\n";
                $message .= "Kindly find the user id details below :\n\n";
                $message .= "User ID : " . $user->email . "\n";
                $message .= "Password: " . $this->mobile . "\n\n";
                $message .= "Click and login: " . $loginUrl;

                WhatsAppService::send($this->mobile, $message);

                // Send WhatsApp message to admin
                $adminMobile = env('ADMIN_MOBILE');
                if ($adminMobile) {
                    $adminMessage = "Dear Duracabs,\n\n";
                    $adminMessage .= "We have received customer account  registration request \n\n";
                    $adminMessage .= "Kindly find the user id details below :\n\n";
                    $adminMessage .= "Name: " . $user->name . "\n";
                    $adminMessage .= "Mobile Number :" . $user->mobile . "\n";
                    $adminMessage .= "User ID : " . $user->email . "\n";
                    $adminMessage .= "Password: " . $this->mobile . "\n";
                    $adminMessage .= "Click   and login: " . $loginUrl;

                    WhatsAppService::send($adminMobile, $adminMessage);
                }
            }
            Auth::login($user);
        }

        return redirect()->intended();
    }

    /* public function save(){
        $this->validate([
            'email'=> 'required|email|max:255|exists:users,email',
            'password'=> 'required|max:255',
        ]);

        if(!auth()->attempt(['email' => $this->email, 'password' => $this->password,])){
           session()->flash('error', 'invalid credentials');
           return;
        }


        return redirect()->intended();
    } */
    public function render()
    {
       
        return view('livewire.auth.login');
    }
}
