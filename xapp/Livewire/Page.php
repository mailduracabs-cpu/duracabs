<?php

namespace App\Livewire;

use App\Models\Banners;
use App\Models\Product;
use App\Models\Reviews;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use App\Models\Brand;
use GuzzleHttp\Client;
use App\Models\Inquirys;


class Page extends Component
{

    public $slug;

    public $bannerTab = 'one_way';

    public $showReview = false;

    public $selected_tab = "one_way";
    
    // Validation error properties
    public $validationErrors = [];
    public $showValidation = false;

    public $query;

    public $queryLocal;
    public $querySelfDrive;
    public $query_id;

    public $queryFrom;
    public $queryTo;

    public $verifyOtpCheck = false;


    public $cities_from;

    public $query2;
    public $query2_id;

    public $cities_to;
    public $sendOtp = false;
    public $sendOtpVerify = false;
    public $mobileNumber;
    public $verifyOtp;

    public $otp = false;
    public $time;
    public $endTime;

    public $plan = '4 Hour / 40 Km';
    public $car;

    public $name;




    public $date;
    public $dateto;

    public $url;

    public $distanceData;

    public $digit1;
    public $digit2;
    public $digit3;
    public $digit4;

    // Edit Query Popup Properties
    public $showEditModal = false;
    public $edit_ride_type = 'one_way';
    public $edit_query_search = '';
    public $edit_query2_search = '';
    public $edit_queryLocal = '';
    public $edit_querySelfDrive = '';
    public $edit_queryFrom_search = '';
    public $edit_queryTo_search = '';
    public $edit_date = '';
    public $edit_dateto = '';
    public $edit_time = '';
    public $edit_endTime = '';
    public $edit_plan = '4 Hour / 40 Km';
    public $edit_cars = 1;
    
    // Auto-complete data for popup
    public $edit_cities_from = [];
    public $edit_cities_to = [];
    public $edit_dataFrom = [];
    public $edit_dataTo = [];

    public function updated($propertyName)
    {
        // When all digits are filled, combine them
        if ($this->digit1 && $this->digit2 && $this->digit3 && $this->digit4) {
            $this->verifyOtp = $this->digit1 . $this->digit2 . $this->digit3 . $this->digit4;
        }
    }


    public $search_city = "";


    public $designation;
    public $description;
    public $reviwerStar;
    
    function closeModal()
    {
        $this->sendOtp = false;
        $this->sendOtpVerify = false;
        $this->otp = false;
        $this->verifyOtp = null;
    }

    public function mount($slug){
        $this->slug = $slug;
    }

    public function changeBanner($value): void
    {
        $this->bannerTab = $value;

    }


    public function changeTab($value)
    {
        // Sync search values before changing tab
        $this->syncSearchValuesOnTabChange($value);
        $this->selected_tab = $value;
    }
    public function reviewFunction($val)
    {
        $this->showReview = $val;
    }


    public function changeStarValue($val)
    {

        return $this->reviwerStar = $val;
        //dd($this->reviwerStar);

    }

  


    public function sendOtpMessageToAdmin()
    {
        $moble_no = "708887331,708887332,9286240750";
        $message = "New Enquiry From '" . $this->mobileNumber . "' ";
        $message = str_replace(' ', '%20', $message);

        $api_url = "http://manage.sambsms.com/app/smsapi/index.php?key=3627633B7AC9C6&entity=1701165124480381903&tempid=1507165596189427962&campaign=0&routeid=7&type=text&contacts=$moble_no&senderid=DURACB&msg=$message";

        $client = new Client();

        try {
            // Make a GET request to the OpenWeather API
            $response = $client->get($api_url);

            // Handle the retrieved weather data as needed (e.g., pass it to a view)
            return $this->sendOtpVerify = true;
        } catch (\Exception $e) {
            // Handle any errors that occur during the API request
            return view('api_error', ['error' => $e->getMessage()]);
        }


    }
    public function sendOtpMessage()
    {
        $moble_no = $this->mobileNumber;
        $message = "This is your 4-digit OTP '" . $this->otp . "' for Mobile Number Verification on Duracabs.com. Valid for 5 Minutes only.From DURA CABS";
        $message2 = "Dear Customer, your OTP for mobile verification is'" . $this->otp . "' Please enter this code to verify your mobile number. This OTP is valid for [time] minutes. Do not share it with anyone.";
        $message = str_replace(' ', '%20', $message);

        $api_url = "http://manage.sambsms.com/app/smsapi/index.php?key=3627633B7AC9C6&entity=1701165124480381903&tempid=1507165596189427962&campaign=0&routeid=7&type=text&contacts=$moble_no&senderid=DURACB&msg=$message";

        $client = new Client();

        try {
            // Make a GET request to the OpenWeather API
            $response = $client->get($api_url);

            // Handle the retrieved weather data as needed (e.g., pass it to a view)
            return $this->sendOtpVerify = true;

        } catch (\Exception $e) {
            // Handle any errors that occur during the API request
            return view('api_error', ['error' => $e->getMessage()]);
        }




    }


    public function submitReview()
    {

        if ($this->reviwerStar > 3) {

            redirect('https://g.page/r/CTGafymLAOMYEBM/review');


        } else {
            $this->validate([
                'name' => 'required|max:255',
                'designation' => 'required|max:255',
                'description' => 'required',

            ]);

            // save user 
            $review = Reviews::create([
                'name' => $this->name,
                'designation' => $this->designation,
                'description' => $this->description,
                'star' => $this->reviwerStar,
            ]);

            if ($review) {
                $this->showReview = false;
            }
        }



    }


    public function sendSMS()
    {

        $mobileval = '7088873332,7088873331,9286240750';
        $templateid = '1507166123273276571';
        $message = ' Dear Admin, New OTP Verification Enquiry Received for mobile number ' . $this->mobileNumber . ' please log in to your account and check the Booking status From DURACABS ';

        $api_url = "http://manage.sambsms.com/app/smsapi/index.php?key=3627633B7AC9C6&entity=1701165124480381903&tempid=" . $templateid . "&campaign=0&routeid=7&type=text&contacts=" . $mobileval . "&senderid=DURACB&msg=" . $message;

        $client = new Client();

        try {
            // Make a GET request to the OpenWeather API
            $response = $client->get($api_url);

            // Handle the retrieved weather data as needed (e.g., pass it to a view)
            return $this->sendOtpVerify = true;

        } catch (\Exception $e) {
            // Handle any errors that occur during the API request
            return view('api_error', ['error' => $e->getMessage()]);
        }

    }




    public function sendOtpToBack()
    {

        $this->sendOtp = false;

        $otp = rand(1111, 9999);
        $this->otp = $otp;
        $this->sendOtpVerify = true;
        $this->sendOtpMessage(); 
        $this->sendSMS();
        $this->getDistance();


    }
    
    public function resendOtp()
    {
        $this->sendOtp = false;
        $otp = rand(1111, 9999);
        $this->otp = $otp;
        $this->sendOtpVerify = true;
        $this->sendOtpMessage(); 
        $this->getDistance();
    }

    public function updateOtp()
    {

        if ($this->otp) {

            dd('hello');
        }
    }


    public function verifySubmitOtp()
    {
        $result = $this->otp == $this->verifyOtp;

        if ($result) {
            
            $inquiry = Inquirys::create([
                'mobile' => $this->mobileNumber,
                'service' => $this->selected_tab,
                'message' => $this->query .' <-to-> '.$this->query2.' Date: '.$this->date.' Time: '.$this->time,
            ]);
           if (Auth::guest()) {
            $user = User::where('mobile', $this->mobileNumber)->first();
            if (!$user) {
                $user = User::create([
                    'mobile' => $this->mobileNumber,
                    'name' => $this->mobileNumber,
                    'email' => $this->mobileNumber . '@gmail.com',
                    'password' => encrypt($this->mobileNumber),
                ]);

                  $loginUrl = route('login');
                $message = "Dear User,\n\n";
                $message .= "We are happy to inform that your request for registration has been approved.\n\n";
                $message .= "Kindly find the user id details below :\n\n";
                $message .= "User ID : " . $user->email . "\n";
                $message .= "Password: " . $this->mobileNumber . "\n\n";
                $message .= "Click and login: " . $loginUrl;

                WhatsAppService::send($this->mobileNumber, $message);

                 // Send WhatsApp message to admin
                $adminMobile = env('ADMIN_MOBILE');
                if ($adminMobile) {
                    $adminMessage = "Dear Duracabs,\n\n";
                    $adminMessage .= "We have received customer account  registration request \n\n";
                    $adminMessage .= "Kindly find the user id details below :\n\n";
                    $adminMessage .= "Name: " . $user->name . "\n";
                    $adminMessage .= "Mobile Number :" . $user->mobile . "\n";
                    $adminMessage .= "User ID : " . $user->email . "\n";
                    $adminMessage .= "Password: " . $this->mobileNumber . "\n";
                    $adminMessage .= "Click   and login: " . $loginUrl;

                    WhatsAppService::send($adminMobile, $adminMessage);
                }
            }
            Auth::login($user);
        }
            redirect(route('rides') . '?cityFrom=' . $this->query_id . '&cityTo=' . $this->query2_id . '&date=' . $this->date . '&nameTo=' . $this->query . '&nameFrom=' . $this->query2 . '&tab=' . $this->selected_tab . '&time=' . $this->time);
        }
    }

    public function verifySubmitOtpSelfDrive()
    {
        $result = $this->otp == $this->verifyOtp;



        // function dateDiffInDays($date1, $date2)
        // {
        //     $diff = strtotime($date2) - strtotime($date1);
        //     return abs(round($diff / 86400));
        // }




        $date1 = sprintf("%s %s", $this->date, $this->time);

        $date2 = sprintf("%s %s", $this->dateto, $this->endTime);

        $diff = abs(strtotime($date2) - strtotime($date1));

        $years = floor($diff / (365 * 60 * 60 * 24));
        $months = floor(($diff - $years * 365 * 60 * 60 * 24) / (30 * 60 * 60 * 24));
        $days = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24) / (60 * 60 * 24));

        $hours = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24 - $days * 60 * 60 * 24) / (60 * 60));

        $minuts = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24 - $days * 60 * 60 * 24 - $hours * 60 * 60) / 60);

        $seconds = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24 - $days * 60 * 60 * 24 - $hours * 60 * 60 - $minuts * 60));

        //dd($years, $months, $days, $hours, $minuts);

       

        $calculatehours =  $days * 24 + $hours;

        $hours = 24 < $calculatehours ?  $calculatehours : 24;



        //dd($hours);

        $inquiry = Inquirys::create([
            'mobile' => $this->mobileNumber,
            'service' => $this->selected_tab,
            'type' => "otp_inquiry",
            'message' => $this->query .' Start Date: '.$this->date.' End Date: '.$this->dateto.'Start Time'. $this->time. 'End Time'.$this->endTime.'Hours=' . $hours  ,
        ]);


        if (Auth::guest()) {
            $user = User::where('mobile', $this->mobileNumber)->first();
            if (!$user) {
                $user = User::create([
                    'mobile' => $this->mobileNumber,
                    'name' => $this->mobileNumber,
                    'email' => $this->mobileNumber . '@gmail.com',
                    'password' => encrypt($this->mobileNumber),
                ]);

                  $loginUrl = route('login');
                $message = "Dear User,\n\n";
                $message .= "We are happy to inform that your request for registration has been approved.\n\n";
                $message .= "Kindly find the user id details below :\n\n";
                $message .= "User ID : " . $user->email . "\n";
                $message .= "Password: " . $this->mobileNumber . "\n\n";
                $message .= "Click and login: " . $loginUrl;

                WhatsAppService::send($this->mobileNumber, $message);

                 // Send WhatsApp message to admin
                $adminMobile = env('ADMIN_MOBILE');
                if ($adminMobile) {
                    $adminMessage = "Dear Duracabs,\n\n";
                    $adminMessage .= "We have received customer account  registration request \n\n";
                    $adminMessage .= "Kindly find the user id details below :\n\n";
                    $adminMessage .= "Name: " . $user->name . "\n";
                    $adminMessage .= "Mobile Number :" . $user->mobile . "\n";
                    $adminMessage .= "User ID : " . $user->email . "\n";
                    $adminMessage .= "Password: " . $this->mobileNumber . "\n";
                    $adminMessage .= "Click   and login: " . $loginUrl;

                    WhatsAppService::send($adminMobile, $adminMessage);
                }
            }
            Auth::login($user);
        }

        if ($inquiry) {
            redirect(route('rides') . '?cityFrom=' . $this->query_id . '&date=' . $this->date . '&dateto=' . $this->dateto . '&nameTo=' . $this->query . '&tab=' . $this->selected_tab . '&time=' . $this->time . '&endTime=' . $this->endTime . '&days=' . $hours);
        }
    }
    public function getDistance()
    {
        $origin = $this->queryFrom;
        $destination = $this->queryTo;


        $client = new Client();


        $apiUrl = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=$origin&destinations=$destination&key=AIzaSyBfhdi5cW9-MM3CYDG-YYOge_GWSjudXZ8";

        $response = $client->get($apiUrl);

        // Get the response body as an array
        $this->distanceData = json_decode($response->getBody(), true);

    }
    
     function dateDiffInDays($date1, $date2)
        {

            // Calculating the difference in timestamps
            $diff = strtotime($date2) - strtotime($date1);

            // 1 day = 24 hours
            // 24 * 60 * 60 = 86400 seconds
            return abs(round($diff / 86400));
        }

   public function verifySubmitOtpReturn()
    {
        $result = $this->otp == $this->verifyOtp;

        $km = $this->distanceData['rows'][0]['elements'][0]['distance']['text'];
        $kmValue = $this->distanceData['rows'][0]['elements'][0]['distance']['value'];
        //$time = $this->distanceData['rows'][0]['elements'][0]['duration']['text'];
        $timeValue = $this->distanceData['rows'][0]['elements'][0]['duration']['value'];


      



        $date1 = $this->date;

        // End date
        $date2 = $this->dateto;

        // Function call to find date difference
        $dateDiff = $this->dateDiffInDays($date1, $date2);

       if (Auth::guest()) {
            $user = User::where('mobile', $this->mobileNumber)->first();
            if (!$user) {
                $user = User::create([
                    'mobile' => $this->mobileNumber,
                    'name' => $this->mobileNumber,
                    'email' => $this->mobileNumber . '@gmail.com',
                    'password' => encrypt($this->mobileNumber),
                ]);

                  $loginUrl = route('login');
                $message = "Dear User,\n\n";
                $message .= "We are happy to inform that your request for registration has been approved.\n\n";
                $message .= "Kindly find the user id details below :\n\n";
                $message .= "User ID : " . $user->email . "\n";
                $message .= "Password: " . $this->mobileNumber . "\n\n";
                $message .= "Click and login: " . $loginUrl;

                WhatsAppService::send($this->mobileNumber, $message);

                 // Send WhatsApp message to admin
                $adminMobile = env('ADMIN_MOBILE');
                if ($adminMobile) {
                    $adminMessage = "Dear Duracabs,\n\n";
                    $adminMessage .= "We have received customer account  registration request \n\n";
                    $adminMessage .= "Kindly find the user id details below :\n\n";
                    $adminMessage .= "Name: " . $user->name . "\n";
                    $adminMessage .= "Mobile Number :" . $user->mobile . "\n";
                    $adminMessage .= "User ID : " . $user->email . "\n";
                    $adminMessage .= "Password: " . $this->mobileNumber . "\n";
                    $adminMessage .= "Click   and login: " . $loginUrl;

                    WhatsAppService::send($adminMobile, $adminMessage);
                }
            }
            Auth::login($user);
        }

        $inquiry = Inquirys::create([
            'mobile' => $this->mobileNumber,
            'service' => $this->selected_tab,
             'type' => "otp_inquiry",
            'message' => 'From '.$this->queryFrom .' To '.$this->queryTo. ' Start Date: '.$this->date.' End Date: '.$this->dateto.' Start Time '. $this->time. ' End Time '.$this->endTime.' Days ' . $dateDiff.' Km '.$km,
        ]);

        if ($inquiry) {
            return redirect(route('rides') . '?nameTo=' . $this->queryFrom . '&date=' . $this->date . '&dateto=' . $this->dateto . '&cityFrom=' . $this->queryTo . '&tab=' . $this->selected_tab . '&km=' . $km . '&kmValue=' . $kmValue . '&time=' . $this->time . '&timeValue=' . $timeValue . '&days=' . $dateDiff);
        }
    }

    public function verifySubmitLocal()
    {
        $result = $this->otp == $this->verifyOtp;

        $inquiry = Inquirys::create([
            'mobile' => $this->mobileNumber,
            'service' => $this->selected_tab,
             'type' => "otp_inquiry",
            'message' => 'From '.$this->query .' Start Date: '.$this->date.' Time '. $this->time.  ' Plan ' . $this->plan . ' Cars ' . $this->car ,
        ]);

        if (Auth::guest()) {
            $user = User::where('mobile', $this->mobileNumber)->first();
            if (!$user) {
                $user = User::create([
                    'mobile' => $this->mobileNumber,
                    'name' => $this->mobileNumber,
                    'email' => $this->mobileNumber . '@gmail.com',
                    'password' => encrypt($this->mobileNumber),
                ]);

                  $loginUrl = route('login');
                $message = "Dear User,\n\n";
                $message .= "We are happy to inform that your request for registration has been approved.\n\n";
                $message .= "Kindly find the user id details below :\n\n";
                $message .= "User ID : " . $user->email . "\n";
                $message .= "Password: " . $this->mobileNumber . "\n\n";
                $message .= "Click and login: " . $loginUrl;

                WhatsAppService::send($this->mobileNumber, $message);

                 // Send WhatsApp message to admin
                $adminMobile = env('ADMIN_MOBILE');
                if ($adminMobile) {
                    $adminMessage = "Dear Duracabs,\n\n";
                    $adminMessage .= "We have received customer account  registration request \n\n";
                    $adminMessage .= "Kindly find the user id details below :\n\n";
                    $adminMessage .= "Name: " . $user->name . "\n";
                    $adminMessage .= "Mobile Number :" . $user->mobile . "\n";
                    $adminMessage .= "User ID : " . $user->email . "\n";
                    $adminMessage .= "Password: " . $this->mobileNumber . "\n";
                    $adminMessage .= "Click   and login: " . $loginUrl;

                    WhatsAppService::send($adminMobile, $adminMessage);
                }
            }
            Auth::login($user);
        }

      
      
        if ($this->plan) {
            redirect(route('rides') . '?cityFrom=' . $this->query_id . '&date=' . $this->date . '&nameTo=' . $this->query . '&tab=' . $this->selected_tab . '&time=' . $this->time . '&plan=' . $this->plan . '&cars=' . $this->car);
        }
    }





   public function updatedQuery($query)
    {
        $this->cities_from = Brand::where('name', 'like', '%' . $this->query . '%')->where('is_active', 1)->get();
        $this->clearError('query');
    }
    
    public function updatedQuerySelfDrive($querySelfDrive)
    {
        $this->cities_from = Brand::where('name', 'like', '%' . $this->querySelfDrive . '%')->where('is_active', 1)->where('is_selfdrive', 1)->get();
        $this->clearError('query');
    }
    
    public function updatedQueryLocal($queryLocal)
    {
        $this->cities_from = Brand::where('name', 'like', '%' . $this->queryLocal . '%')->where('is_active', 1)->where('is_local', 1)->get();
        $this->clearError('query');
    }

    public $apiKey = 'AIzaSyCLWE-Pcf8ykbr4xIvgikDV1vc1hkhjm0A';

    // Create a new Guzzle client instance


    public $apiUrl;

    public $dataFrom;
    public $dataTo;


    public function updatedQueryFrom($queryFrom)
    {

        $client = new Client();


        $apiUrl = "https://maps.googleapis.com/maps/api/place/queryautocomplete/json?input={$queryFrom}&key={$this->apiKey}";

        $response = $client->get($apiUrl);

        // Get the response body as an array
        $data = json_decode($response->getBody(), true);

        $this->dataFrom = $data['predictions'];
        $this->clearError('queryFrom');

    }

    public function updatedQueryTo($queryTo)
    {

        $client = new Client();


        $apiUrl = "https://maps.googleapis.com/maps/api/place/queryautocomplete/json?input={$queryTo}&key={$this->apiKey}";

        $response = $client->get($apiUrl);

        // Get the response body as an array
        $data = json_decode($response->getBody(), true);

        $this->dataTo = $data['predictions'];
        $this->clearError('queryTo');

    }

    public function updatedQuery2($query2)
    {
        $this->cities_to = Brand::where('name', 'like', '%' . $this->query2 . '%')->get();
        $this->clearError('query2');
    }




    public function updateCityFrom($name)
    {

        $this->queryFrom = $name;
        $this->dataFrom = null;

    }
    public function updateCityTo($name)
    {

        $this->queryTo = $name;
        $this->dataTo = null;
    }


    public function update1($name, $id)
    {

        $this->query = $name;
        $this->query_id = $id;
        $this->cities_from = null;


    }

    public function update2($name, $id)
    {

        $this->query2 = $name;
        $this->query2_id = $id;
        $this->cities_to = null;


    }



 

    public function validateForm()
    {
        $this->validationErrors = [];
        $this->showValidation = true;
        
        switch ($this->selected_tab) {
            case 'one_way':
                return $this->validateOneWay();
            case 'return':
                return $this->validateReturn();
            case 'local':
                return $this->validateLocal();
            case 'self_drive':
                return $this->validateSelfDrive();
            default:
                return false;
        }
    }
    
    private function validateOneWay()
    {
        $isValid = true;
        
        if (empty($this->query)) {
            $this->validationErrors['query'] = 'From City is required';
            $isValid = false;
        }
        
        if (empty($this->query2)) {
            $this->validationErrors['query2'] = 'To City is required';
            $isValid = false;
        }
        
        if (empty($this->date)) {
            $this->validationErrors['date'] = 'Pickup Date is required';
            $isValid = false;
        } elseif ($this->date < now()->format('Y-m-d')) {
            $this->validationErrors['date'] = 'Pickup Date cannot be in the past';
            $isValid = false;
        }
        
        if (empty($this->time)) {
            $this->validationErrors['time'] = 'Pickup Time is required';
            $isValid = false;
        }
        
        return $isValid;
    }
    
    private function validateReturn()
    {
        $isValid = true;
        
        if (empty($this->queryFrom)) {
            $this->validationErrors['queryFrom'] = 'From City is required';
            $isValid = false;
        }
        
        if (empty($this->queryTo)) {
            $this->validationErrors['queryTo'] = 'To City is required';
            $isValid = false;
        }
        
        if (empty($this->date)) {
            $this->validationErrors['date'] = 'Start Date is required';
            $isValid = false;
        } elseif ($this->date < now()->format('Y-m-d')) {
            $this->validationErrors['date'] = 'Start Date cannot be in the past';
            $isValid = false;
        }
        
        if (empty($this->dateto)) {
            $this->validationErrors['dateto'] = 'End Date is required';
            $isValid = false;
        } elseif (!empty($this->date) && $this->dateto <= $this->date) {
            $this->validationErrors['dateto'] = 'End Date must be after Start Date';
            $isValid = false;
        }
        
        if (empty($this->time)) {
            $this->validationErrors['time'] = 'Pickup Time is required';
            $isValid = false;
        }
        
        return $isValid;
    }
    
    private function validateLocal()
    {
        $isValid = true;
        
        if (empty($this->query)) {
            $this->validationErrors['query'] = 'From City is required';
            $isValid = false;
        }
        
        if (empty($this->date)) {
            $this->validationErrors['date'] = 'Pickup Date is required';
            $isValid = false;
        } elseif ($this->date < now()->format('Y-m-d')) {
            $this->validationErrors['date'] = 'Pickup Date cannot be in the past';
            $isValid = false;
        }
        
        if (empty($this->time)) {
            $this->validationErrors['time'] = 'Pickup Time is required';
            $isValid = false;
        }
        
        if (empty($this->plan)) {
            $this->validationErrors['plan'] = 'Plan is required';
            $isValid = false;
        }
        
        if (empty($this->car) || !is_numeric($this->car) || $this->car < 1) {
            $this->validationErrors['car'] = 'Number of cars must be at least 1';
            $isValid = false;
        }
        
        return $isValid;
    }
    
    private function validateSelfDrive()
    {
        $isValid = true;
        
        if (empty($this->query)) {
            $this->validationErrors['query'] = 'From City is required';
            $isValid = false;
        }
        
        if (empty($this->date)) {
            $this->validationErrors['date'] = 'Pickup Date is required';
            $isValid = false;
        } elseif ($this->date < now()->format('Y-m-d')) {
            $this->validationErrors['date'] = 'Pickup Date cannot be in the past';
            $isValid = false;
        }
        
        if (empty($this->dateto)) {
            $this->validationErrors['dateto'] = 'Return Date is required';
            $isValid = false;
        } elseif (!empty($this->date) && $this->dateto < $this->date) {
            $this->validationErrors['dateto'] = 'Return Date cannot be before Pickup Date';
            $isValid = false;
        }
        
        if (empty($this->time)) {
            $this->validationErrors['time'] = 'Pickup Time is required';
            $isValid = false;
        }
        
        if (empty($this->endTime)) {
            $this->validationErrors['endTime'] = 'End Time is required';
            $isValid = false;
        }
        
        return $isValid;
    }
    
    public function hasError($field)
    {
        return $this->showValidation && isset($this->validationErrors[$field]);
    }
    
    public function getError($field)
    {
        return $this->validationErrors[$field] ?? '';
    }
    
    public function clearError($field)
    {
        if (isset($this->validationErrors[$field])) {
            unset($this->validationErrors[$field]);
        }
    }
    
    // Clear validation errors when user starts typing - merged with existing methods above
    
    public function updatedDate()
    {
        $this->clearError('date');
    }
    
    public function updatedDateto()
    {
        $this->clearError('dateto');
    }
    
    public function updatedTime()
    {
        $this->clearError('time');
    }
    
    public function updatedEndTime()
    {
        $this->clearError('endTime');
    }
    
    public function updatedCar()
    {
        $this->clearError('car');
    }
    
    public function updatedPlan()
    {
        $this->clearError('plan');
    }

    public function searchPackage()
    {
        // Validate form first
        if (!$this->validateForm()) {
            return;
        }
        
        // Clear validation errors if form is valid
        $this->validationErrors = [];
        $this->showValidation = false;

        if(Auth::check()){
            if($this->selected_tab == 'return'){
                $inquiry = Inquirys::create([
                    'mobile' => auth()->user()->mobile,
                    'service' => $this->selected_tab,
                    'message' => 'From '.$this->queryFrom .' To '.$this->queryTo. ' Start Date: '.$this->date.' End Date: '.$this->dateto.' Start Time '. $this->time. ' End Time '.$this->endTime,
                ]);
            } else {
                $inquiry = Inquirys::create([
                    'mobile' => auth()->user()->mobile,
                    'service' => $this->selected_tab,
                    'message' => 'From '.$this->query .' Start Date: '.$this->date.' Time '. $this->time.  ' Plan ' . $this->plan . ' Cars ' . $this->car ,
                ]);
            }
             if($this->selected_tab == 'local'){
                    return $this->verifySubmitLocal();
                } else if($this->selected_tab == 'self_drive'){      
                    return $this->verifySubmitOtpSelfDrive(); 
                } else if($this->selected_tab == 'return'){   
                     $this->getDistance();   
                    return $this->verifySubmitOtpReturn(); 
                } else if($this->selected_tab == 'one_way'){      
                    return $this->verifySubmitOtp();
                } else{
                    $this->sendOtp = true;
                }
                return;
        }
        $this->sendOtp = true;

    }



    public function searchPackageSelf()
    {

        $this->sendOtp = true;

    }

    // Edit Query Popup Methods
    public function showEditQueryModal() {
        $this->showEditModal = true;
        $this->edit_ride_type = $this->selected_tab ?: 'one_way';
        
        // Pre-populate current values
        $this->edit_date = $this->date;
        $this->edit_dateto = $this->dateto;
        $this->edit_time = $this->time;
        $this->edit_endTime = $this->endTime;
        $this->edit_plan = $this->plan ?: '4 Hour / 40 Km';
        $this->edit_cars = $this->car ?: 1;
        
        if ($this->edit_ride_type == 'one_way') {
            $this->edit_query_search = $this->query;
            $this->edit_query2_search = $this->query2;
        } elseif ($this->edit_ride_type == 'return') {
            $this->edit_queryFrom_search = $this->queryFrom;
            $this->edit_queryTo_search = $this->queryTo;
        } elseif ($this->edit_ride_type == 'local') {
            $this->edit_queryLocal = $this->queryLocal;
        } elseif ($this->edit_ride_type == 'self_drive') {
            $this->edit_querySelfDrive = $this->querySelfDrive;
        }
    }

    public function changeEditTab($value) {
        // Sync current values before switching
        $this->syncEditSearchValuesOnTabChange($value);
        $this->edit_ride_type = $value;
    }

    private function resetEditSearchFields() {
        // Clear dropdown data but keep search values
        $this->edit_cities_from = [];
        $this->edit_cities_to = [];
        $this->edit_dataFrom = [];
        $this->edit_dataTo = [];
    }

    /**
     * Sync search values when changing tabs in main search form
     */
    private function syncSearchValuesOnTabChange($newTab) {
        $currentTab = $this->selected_tab;
        
        // Get current search values based on current tab
        $fromValue = $this->getCurrentFromValue($currentTab);
        $toValue = $this->getCurrentToValue($currentTab);
        
        // Set values for new tab if they exist
        if ($fromValue) {
            $this->setFromValueForTab($newTab, $fromValue);
        }
        if ($toValue) {
            $this->setToValueForTab($newTab, $toValue);
        }
    }

    /**
     * Sync search values when changing tabs in edit modal
     */
    private function syncEditSearchValuesOnTabChange($newTab) {
        $currentTab = $this->edit_ride_type;
        
        // Get current search values based on current tab
        $fromValue = $this->getCurrentEditFromValue($currentTab);
        $toValue = $this->getCurrentEditToValue($currentTab);
        
        // Set values for new tab if they exist and are compatible
        if ($fromValue) {
            if ($this->isValueCompatibleWithTab($fromValue, $newTab)) {
                $this->setEditFromValueForTab($newTab, $fromValue);
                $this->setEditFromSearchValueForTab($newTab, $fromValue);
            } else {
                // Clear the search field if the value won't work in the new tab
                $this->setEditFromSearchValueForTab($newTab, '');
            }
        }
        if ($toValue) {
            $this->setEditToValueForTab($newTab, $toValue);
            $this->setEditToSearchValueForTab($newTab, $toValue);
        }
    }

    /**
     * Get current "from" value based on tab
     */
    private function getCurrentFromValue($tab) {
        switch ($tab) {
            case 'one_way':
                return $this->query;
            case 'return':
                return $this->queryFrom;
            case 'local':
                return $this->queryLocal;
            case 'self_drive':
                return $this->querySelfDrive;
            default:
                return null;
        }
    }

    /**
     * Get current "to" value based on tab
     */
    private function getCurrentToValue($tab) {
        switch ($tab) {
            case 'one_way':
                return $this->query2;
            case 'return':
                return $this->queryTo;
            default:
                return null;
        }
    }

    /**
     * Set "from" value for specific tab
     */
    private function setFromValueForTab($tab, $value) {
        switch ($tab) {
            case 'one_way':
                $this->query = $value;
                break;
            case 'return':
                $this->queryFrom = $value;
                break;
            case 'local':
                $this->queryLocal = $value;
                break;
            case 'self_drive':
                $this->querySelfDrive = $value;
                break;
        }
    }

    /**
     * Set "to" value for specific tab
     */
    private function setToValueForTab($tab, $value) {
        switch ($tab) {
            case 'one_way':
                $this->query2 = $value;
                break;
            case 'return':
                $this->queryTo = $value;
                break;
        }
    }

    /**
     * Get current edit "from" value based on tab
     */
    private function getCurrentEditFromValue($tab) {
        switch ($tab) {
            case 'one_way':
                return $this->edit_query_search;
            case 'return':
                return $this->edit_queryFrom_search;
            case 'local':
                return $this->edit_queryLocal;
            case 'self_drive':
                return $this->edit_querySelfDrive;
            default:
                return null;
        }
    }

    /**
     * Get current edit "to" value based on tab
     */
    private function getCurrentEditToValue($tab) {
        switch ($tab) {
            case 'one_way':
                return $this->edit_query2_search;
            case 'return':
                return $this->edit_queryTo_search;
            default:
                return null;
        }
    }

    /**
     * Set edit "from" value for specific tab
     */
    private function setEditFromValueForTab($tab, $value) {
        switch ($tab) {
            case 'one_way':
                $this->edit_query_search = $value;
                break;
            case 'return':
                $this->edit_queryFrom_search = $value;
                break;
            case 'local':
                $this->edit_queryLocal = $value;
                break;
            case 'self_drive':
                $this->edit_querySelfDrive = $value;
                break;
        }
    }

    /**
     * Set edit "to" value for specific tab
     */
    private function setEditToValueForTab($tab, $value) {
        switch ($tab) {
            case 'one_way':
                $this->edit_query2_search = $value;
                break;
            case 'return':
                $this->edit_queryTo_search = $value;
                break;
        }
    }

    /**
     * Set edit "from" search field value for specific tab (updates input field)
     */
    private function setEditFromSearchValueForTab($tab, $value) {
        switch ($tab) {
            case 'one_way':
                $this->edit_query_search = $value;
                break;
            case 'return':
                $this->edit_queryFrom_search = $value;
                break;
            case 'local':
                $this->edit_queryLocal = $value;
                break;
            case 'self_drive':
                $this->edit_querySelfDrive = $value;
                break;
        }
    }

    /**
     * Set edit "to" search field value for specific tab (updates input field)
     */
    private function setEditToSearchValueForTab($tab, $value) {
        switch ($tab) {
            case 'one_way':
                $this->edit_query2_search = $value;
                break;
            case 'return':
                $this->edit_queryTo_search = $value;
                break;
        }
    }

    /**
     * Check if a search value is compatible with a specific tab
     */
    private function isValueCompatibleWithTab($value, $tab) {
        if (empty($value)) {
            return true;
        }

        switch ($tab) {
            case 'local':
                // Check if there are local cities matching this value
                return Brand::where('name', 'like', '%' . $value . '%')
                    ->where('is_active', 1)
                    ->where('is_local', 1)
                    ->exists();
            
            case 'self_drive':
                // Check if there are self_drive cities matching this value
                return Brand::where('name', 'like', '%' . $value . '%')
                    ->where('is_active', 1)
                    ->where('is_selfdrive', 1)
                    ->exists();
            
            case 'one_way':
            case 'return':
            default:
                // One way and return accept all active cities
                return Brand::where('name', 'like', '%' . $value . '%')
                    ->where('is_active', 1)
                    ->exists();
        }
    }

    // One Way search handlers for popup
    public function updatedEditQuerySearch($query) {
        if (strlen($this->edit_query_search) >= 3) {
            $this->edit_cities_from = Brand::where('name', 'like', '%' . $this->edit_query_search . '%')
                ->where('is_active', 1)
                ->get();
        }
    }

    public function updatedEditQuery2Search($query2) {
        if (strlen($this->edit_query2_search) >= 3) {
            $this->edit_cities_to = Brand::where('name', 'like', '%' . $this->edit_query2_search . '%')
                ->where('is_active', 1)
                ->get();
        }
    }

    // Local search handler for popup
    public function updatedEditQueryLocal($queryLocal) {
        $this->edit_cities_from = Brand::where('name', 'like', '%' . $this->edit_queryLocal . '%')
            ->where('is_active', 1)
            ->where('is_local', 1)
            ->get();
    }

    // Self Drive search handler for popup
    public function updatedEditQuerySelfDrive($querySelfDrive) {
        $this->edit_cities_from = Brand::where('name', 'like', '%' . $this->edit_querySelfDrive . '%')
            ->where('is_active', 1)
            ->where('is_selfdrive', 1)
            ->get();
    }

    // Return trip Google Places API handlers for popup
    public function updatedEditQueryFromSearch($queryFrom) {
        $client = new Client();
        $apiUrl = "https://maps.googleapis.com/maps/api/place/queryautocomplete/json?input={$queryFrom}&key=AIzaSyCLWE-Pcf8ykbr4xIvgikDV1vc1hkhjm0A";
        $response = $client->get($apiUrl);
        $data = json_decode($response->getBody(), true);
        $this->edit_dataFrom = $data['predictions'];
    }

    public function updatedEditQueryToSearch($queryTo) {
        $client = new Client();
        $apiUrl = "https://maps.googleapis.com/maps/api/place/queryautocomplete/json?input={$queryTo}&key=AIzaSyCLWE-Pcf8ykbr4xIvgikDV1vc1hkhjm0A";
        $response = $client->get($apiUrl);
        $data = json_decode($response->getBody(), true);
        $this->edit_dataTo = $data['predictions'];
    }

    // City selection methods for popup
    public function editUpdateCityFrom($name) {
        $this->edit_queryFrom_search = $name;
        $this->edit_dataFrom = [];
    }

    public function editUpdateCityTo($name) {
        $this->edit_queryTo_search = $name;
        $this->edit_dataTo = [];
    }

    public function editUpdate1($name, $id) {
        $this->edit_query_search = $name;
        $this->edit_cities_from = [];
    }

    public function editUpdate2($name, $id) {
        $this->edit_query2_search = $name;
        $this->edit_cities_to = [];
    }

    public function editUpdate3($name, $id) {
        $this->edit_queryLocal = $name;
        $this->edit_cities_from = [];
    }

    public function editUpdate4($name, $id) {
        $this->edit_querySelfDrive = $name;
        $this->edit_cities_from = [];
    }

    public function updateQuery() {
        // Validation based on ride type
        $this->validate($this->getEditValidationRules());

        // Build the redirect URL with updated parameters
        $params = $this->buildEditRedirectParams();
        
        $this->showEditModal = false;
        
        return redirect()->to(route('rides') . '?' . http_build_query($params));
    }

    private function getEditValidationRules() {
        $baseRules = [
            'edit_date' => 'required|date|after_or_equal:today',
            'edit_time' => 'required',
        ];

        switch ($this->edit_ride_type) {
            case 'one_way':
                return array_merge($baseRules, [
                    'edit_query_search' => 'required',
                    'edit_query2_search' => 'required',
                ]);
            case 'return':
                return array_merge($baseRules, [
                    'edit_queryFrom_search' => 'required',
                    'edit_queryTo_search' => 'required',
                    'edit_dateto' => 'required|date|after:edit_date',
                ]);
            case 'local':
                return array_merge($baseRules, [
                    'edit_queryLocal' => 'required',
                    'edit_plan' => 'required',
                    'edit_cars' => 'required|integer|min:1',
                ]);
            case 'self_drive':
                return array_merge($baseRules, [
                    'edit_querySelfDrive' => 'required',
                    'edit_dateto' => 'required|date|after_or_equal:edit_date',
                    'edit_endTime' => 'required',
                ]);
            default:
                return $baseRules;
        }
    }

    private function buildEditRedirectParams() {
        $params = [
            'tab' => $this->edit_ride_type,
            'date' => $this->edit_date,
            'time' => $this->edit_time,
        ];

        switch ($this->edit_ride_type) {
            case 'one_way':
                $params['nameTo'] = $this->edit_query_search;
                $params['nameFrom'] = $this->edit_query2_search;
                break;
            case 'return':
                $params['nameTo'] = $this->edit_queryFrom_search;
                $params['nameFrom'] = $this->edit_queryTo_search;
                $params['dateto'] = $this->edit_dateto;
                break;
            case 'local':
                $params['nameTo'] = $this->edit_queryLocal;
                $params['plan'] = $this->edit_plan;
                $params['cars'] = $this->edit_cars;
                break;
            case 'self_drive':
                $params['nameTo'] = $this->edit_querySelfDrive;
                $params['dateto'] = $this->edit_dateto;
                $params['endTime'] = $this->edit_endTime;
                break;
        }

        return $params;
    }

    public function render()
    {

        $page = \App\Models\Page::where('slug', $this->slug)->firstOrFail();
        $carousel = Banners::where('ride_type', $this->bannerTab)->get();
        $reviews = Reviews::all();
        $product = Product::where('is_featured', 1)->get();
        
        $imageMeta = url('storage'). "/" . $page->image;

        return view('livewire.page', [
            'page' => $page,
            'carousel' => $carousel,
            'reviews' => $reviews,
            'products' => $product,
             'imageMeta'=> $imageMeta
        ]);
    }
}
