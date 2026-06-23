<?php

namespace App\Livewire;

use App\Models\Banners;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Inquirys;
use App\Models\Product;
use App\Models\Reviews;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use GuzzleHttp\Client;


#[Title('Home Page -  Duracabs')]

class Homepage extends Component
{

    public $oneWayMsg;

    // Validation error properties
    public $validationErrors = [];
    public $showValidation = false;
    public $query;
    public $query_search;
    public $query_id;

    public $queryFrom_search;
    public $queryFrom;
    public $queryLocal;
    public $querySelfDrive;
    public $queryTo_search;
    public $queryTo;

    public $verifyOtpCheck = false;


    public $cities_from;

    public $query2;
    public $query2_search;
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

    public $designation;
    public $description;
    public $reviwerStar;

    public $showReview = false;

    public $digit1;
    public $digit2;
    public $digit3;
    public $digit4;

    public function updated($propertyName)
    {
        // When all digits are filled, combine them
        if ($this->digit1 && $this->digit2 && $this->digit3 && $this->digit4) {
            $this->verifyOtp = $this->digit1 . $this->digit2 . $this->digit3 . $this->digit4;
        }
    }

    public function changeStarValue($val)
    {

        return $this->reviwerStar = $val;
        //dd($this->reviwerStar);

    }

    public function reviewFunction($val)
    {
        $this->showReview = $val;
    }

    function closeModal()
    {
        $this->sendOtp = false;
        $this->sendOtpVerify = false;
        $this->otp = false;
        $this->verifyOtp = null;
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

    public function sendOtpMessageToAdmin()
    {
        $moble_no = "708887331,708887332,9286240750,9837006905";
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

            // Send OTP via WhatsApp as well
            if ($this->mobileNumber && $this->otp) {
                $whatsappMessage = "🔐 *DURA CABS — OTP Verification*\n\n";
                $whatsappMessage .= "This is your *4-digit OTP:* " . $this->otp . "\n\n";
                $whatsappMessage .= "for *mobile number verification* on www.duracabs.com.\n\n";
                $whatsappMessage .= "⏰ Valid for 5 minutes only.\n\n";
                $whatsappMessage .= "From,\n\n";
                $whatsappMessage .= "*Dura Cabs Serives* 🚖";
                
                try {
                    WhatsAppService::send($this->mobileNumber, $whatsappMessage);
                } catch (\Exception $e) {
                    // Log WhatsApp error but don't fail the OTP process
                    \Log::error('WhatsApp OTP send failed: ' . $e->getMessage());
                }
            }

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
        $mobileval = '7088873332,7088873331,9286240750,9837006905';
        $templateid = '1507166123273276571';
        $message = ' Dear Admin, New OTP Verification Enquiry Received for mobile number ' . $this->mobileNumber . ' please log in to your account and check the Booking status From DURACABS ';

        $api_url = "http://manage.sambsms.com/app/smsapi/index.php?key=3627633B7AC9C6&entity=1701165124480381903&tempid=" . $templateid . "&campaign=0&routeid=7&type=text&contacts=" . $mobileval . "&senderid=DURACB&msg=" . $message;

        $client = new Client();

        try {
            // Make a GET request to the OpenWeather API
            $response = $client->get($api_url);

            // Format trip type
            $tripType = ucfirst(str_replace('_', ' ', $this->selected_tab ?? 'one_way'));
            
            // Get From and To locations based on trip type
            $fromLocation = '';
            $toLocation = '';
            
            if ($this->selected_tab === 'one_way') {
                $fromLocation = $this->query ?? 'N/A';
                $toLocation = $this->query2 ?? 'N/A';
            } elseif ($this->selected_tab === 'return') {
                $fromLocation = $this->queryFrom ?? 'N/A';
                $toLocation = $this->queryTo ?? 'N/A';
            } elseif ($this->selected_tab === 'local') {
                $fromLocation = $this->query ?? $this->queryLocal ?? 'N/A';
                $toLocation = 'N/A';
            } elseif ($this->selected_tab === 'self_drive') {
                $fromLocation = $this->query ?? $this->querySelfDrive ?? 'N/A';
                $toLocation = 'N/A';
            }
            
            // Format date
            $dateFormatted = $this->date ? date('d-m-Y', strtotime($this->date)) : 'N/A';
            
            // Format time
            $timeFormatted = 'N/A';
            if ($this->time) {
                $timeFormatted = date('h:i A', strtotime($this->time));
            }

            // Send WhatsApp message to admin
            $adminMobile = env('ADMIN_MOBILE');
            if ($adminMobile) {
                $adminWhatsAppMessage = "📩 *New OTP Verification Enquiry*\n\n";
                $adminWhatsAppMessage .= "Dear Admin,\n\n";
                $adminWhatsAppMessage .= "A new OTP verification request has been received.\n\n";
                $adminWhatsAppMessage .= "📱 *Mobile Number:* " . $this->mobileNumber . "\n\n";
                $adminWhatsAppMessage .= "🚘 *Trip Type:* " . $tripType . "\n\n";
                
                if ($fromLocation !== 'N/A') {
                    $adminWhatsAppMessage .= "📍 *From:* " . $fromLocation . "\n\n";
                }
                
                if ($toLocation !== 'N/A') {
                    $adminWhatsAppMessage .= "➡️ *To:* " . $toLocation . "\n\n";
                }
                
                $adminWhatsAppMessage .= "🗓️ *Date:* " . $dateFormatted . "\n\n";
                $adminWhatsAppMessage .= "⏰ *Time:* " . $timeFormatted . "\n\n";
                $adminWhatsAppMessage .= "From,\n\n";
                $adminWhatsAppMessage .= "*DURA CABS SYSTEM ALERT* 🚖";
                
                try {
                    WhatsAppService::send($adminMobile, $adminWhatsAppMessage);
                } catch (\Exception $e) {
                    \Log::error('WhatsApp admin OTP notification failed: ' . $e->getMessage());
                }
            }

            // Handle the retrieved weather data as needed (e.g., pass it to a view)
            return $this->sendOtpVerify = true;
        } catch (\Exception $e) {
            // Handle any errors that occur during the API request
            return view('api_error', ['error' => $e->getMessage()]);
        }
    }

    public function backButton()
    {
        $this->sendOtp = true;
        $this->sendOtpVerify = false;
        $this->otp = false;
        $this->verifyOtp = null;
    }




    public function sendOtpToBack()
    {
        $this->sendOtp = false;
        $otp = rand(1000, 9999);
        $this->otp = $otp;
        $this->sendOtpVerify = true;
        $this->sendOtpMessage();
        $this->sendSMS();
        $this->getDistance();
    }

    public function updateOtp()
    {

        if ($this->otp) {

            dd('hello');
        }
    }








    public $date;
    public $dateto;

    public $url;

    public $distanceData;



    public $search_city = "";

    public $selected_tab = "one_way";

    public function verifySubmitOtp()
    {
        $result = $this->otp == $this->verifyOtp;

        if ($result) {
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

            Inquirys::create([
                'mobile' => auth()->user()->mobile ?? $this->mobileNumber,
                'service' => $this->selected_tab,
                'type' => "otp_inquiry",
                'message' => $this->query . ' <-to-> ' . $this->query2 . ' Date: ' . $this->date . ' Time: ' . $this->time,
            ]);

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



        $calculatehours = $days * 24 + $hours;

        $hours = 24 < $calculatehours ? $calculatehours : 24;



        //dd($hours);


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

             Auth::login(user: $user);
          
             
        }


         Inquirys::create([
                'mobile' => auth()->user()->mobile ?? $this->mobileNumber,
                'service' => $this->selected_tab,
                'type' => "otp_inquiry",
                'message' => $this->query . ' <-to-> ' . $this->query2 . ' Date: ' . $this->date . ' Time: ' . $this->time,
            ]);

        if ($result) {
            redirect(route('rides') . '?cityFrom=' . $this->query_id . '&date=' . $this->date . '&dateto=' . $this->dateto . '&nameTo=' . $this->query . '&tab=' . $this->selected_tab . '&time=' . $this->time . '&endTime=' . $this->endTime . '&days=' . $hours);
        }
    }

    public function getDistance()
    {
        $origin = $this->queryFrom_search;
        $destination = $this->queryTo_search;


        $client = new Client();


        $apiUrl = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=$origin&destinations=$destination&key=AIzaSyBfhdi5cW9-MM3CYDG-YYOge_GWSjudXZ8";

        $response = $client->get($apiUrl);

        // dd($response); // Commented out for debugging

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

        $inquery =  Inquirys::create([
                'mobile' => auth()->user()->mobile ?? $this->mobileNumber,
                'service' => $this->selected_tab,
                'type' => "otp_inquiry",
                'message' => 'From ' . $this->queryFrom . ' To ' . $this->queryTo . ' Start Date: ' . $this->date . ' End Date: ' . $this->dateto . ' Start Time ' . $this->time . ' End Time ' . $this->endTime . ' Days ' . $dateDiff . ' Km ' . $km,
            ]);





        if ($inquery) {
           
            return redirect(route('rides') . '?nameTo=' . $this->queryFrom . '&date=' . $this->date . '&dateto=' . $this->dateto . '&cityFrom=' . $this->queryTo . '&tab=' . $this->selected_tab . '&km=' . $km . '&kmValue=' . $kmValue . '&time=' . $this->time . '&timeValue=' . $timeValue . '&days=' . $dateDiff);
        }
    }

    public function verifySubmitLocal()
    {
        $result = $this->otp == $this->verifyOtp;



      
        if (Auth::guest()) {
            $user = User::where('mobile', $this->mobileNumber)->first();
            if (!$user) {
                $user = User::create([
                    'mobile' => $this->mobileNumber,
                    'name' => $this->mobileNumber,
                    'email' => $this->mobileNumber . '@gmail.com',
                    'password' => encrypt($this->mobileNumber),
                ]);
            }
            Auth::login($user);
        }

                $inquery =  Inquirys::create([
                        'mobile' => auth()->user()->mobile ?? $this->mobileNumber,
                            'service' => $this->selected_tab,
                            'type' => "otp_inquiry",
                            'message' => 'From ' . $this->query . ' Start Date: ' . $this->date . ' Time ' . $this->time . ' Plan ' . $this->plan . ' Cars ' . $this->car,
                        ]);
     

             if ($inquery) {
                    redirect(route('rides') . '?cityFrom=' . $this->query_id . '&date=' . $this->date . '&nameTo=' . $this->query . '&tab=' . $this->selected_tab . '&time=' . $this->time . '&plan=' . $this->plan . '&cars=' . $this->car);
             }
    }





    public function updatedQuerySearch($query)
    {
        $this->oneWayMsg = "";
        if (strlen($this->query_search) >= 3) {
            $this->cities_from = Brand::where('name', 'like', '%' . $this->query_search . '%')->where('is_active', 1)->get();
        }
    }

    public function updatedQuerySelfDrive($querySelfDrive)
    {
        $this->cities_from = Brand::where('name', 'like', '%' . $this->querySelfDrive . '%')->where('is_active', 1)->where('is_selfdrive', 1)->get();
    }

    public function updatedQueryLocal($queryLocal)
    {
        $this->cities_from = Brand::where('name', 'like', '%' . $this->queryLocal . '%')->where('is_active', 1)->where('is_local', 1)->get();
    }


    public $apiKey = 'AIzaSyCLWE-Pcf8ykbr4xIvgikDV1vc1hkhjm0A';

    // Create a new Guzzle client instance


    public $apiUrl;

    public $dataFrom;
    public $dataTo;

    public $bannerTab = 'one_way';


    public function updatedQueryFromSearch($queryFrom_search)
    {
        $client = new Client();
        $apiUrl = "https://maps.googleapis.com/maps/api/place/queryautocomplete/json?input={$queryFrom_search}&key={$this->apiKey}";
        $response = $client->get($apiUrl);
        $data = json_decode($response->getBody(), true);
        $this->dataFrom = $data['predictions'];
    }

    public function updatedQueryToSearch($queryTo_search)
    {
        $client = new Client();
        $apiUrl = "https://maps.googleapis.com/maps/api/place/queryautocomplete/json?input={$queryTo_search}&key={$this->apiKey}";
        $response = $client->get($apiUrl);
        $data = json_decode($response->getBody(), true);
        $this->dataTo = $data['predictions'];
    }

    public function updatedQuery2Search($query2)
    {
        $this->oneWayMsg = "";
        if (strlen($this->query2_search) >= 3) {
            $this->cities_to = Brand::where('name', 'like', '%' . $this->query2_search . '%')->where('is_active', 1)->get();
        }
    }




    public function updateCityFrom($name)
    {
        $this->queryFrom = $name;
        $this->queryFrom_search = $name;
        $this->dataFrom = null;
    }

    public function updateCityTo($name)
    {
        $this->queryTo = $name;
        $this->queryTo_search = $name;
        $this->dataTo = null;
    }


    public function update1($name, $id)
    {

        $this->query = $name;
        $this->query_search = $name;
        $this->query_id = $id;
        $this->cities_from = null;
    }

    public function update2($name, $id)
    {

        $this->query2 = $name;
        $this->query2_search = $name;
        $this->query2_id = $id;
        $this->cities_to = null;
    }

    public function update3($name, $id)
    {

        $this->query = $name;
        $this->queryLocal = $name;
        $this->query_id = $id;
        $this->cities_from = null;
    }

    public function update4($name, $id)
    {

        $this->query = $name;
        $this->querySelfDrive = $name;
        $this->query_id = $id;
        $this->cities_from = null;
    }




    public function changeTab($value)
    {
        // Sync search values before changing tab
        $this->syncSearchValuesOnTabChange($value);
        $this->selected_tab = $value;
    }

    public function changeBanner($value): void
    {
        $this->bannerTab = $value;
    }

    /**
     * Sync search values when changing tabs in main search form
     */
    private function syncSearchValuesOnTabChange($newTab)
    {
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
     * Get current "from" value based on tab
     */
    private function getCurrentFromValue($tab)
    {
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
    private function getCurrentToValue($tab)
    {
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
    private function setFromValueForTab($tab, $value)
    {
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
    private function setToValueForTab($tab, $value)
    {
        switch ($tab) {
            case 'one_way':
                $this->query2 = $value;
                break;
            case 'return':
                $this->queryTo = $value;
                break;
        }
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

    // Clear validation errors when user starts typing
    public function updatedQuery()
    {
        $this->clearError('query');
    }

    public function updatedQuery2()
    {
        $this->clearError('query2');
    }

    public function updatedQueryFrom()
    {
        $this->clearError('queryFrom');
    }

    public function updatedQueryTo()
    {
        $this->clearError('queryTo');
    }

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
            $this->oneWayMsg = "Please fix the errors below and try again.";
            return;
        }

        // Clear validation errors if form is valid
        $this->validationErrors = [];
        $this->showValidation = false;
        $this->oneWayMsg = "";
        if (Auth::check()) {



            if ($this->selected_tab == 'local') {
                return $this->verifySubmitLocal();
            } else if ($this->selected_tab == 'self_drive') {
                return $this->verifySubmitOtpSelfDrive();
            } else if ($this->selected_tab == 'return') {
                $this->getDistance();
                return $this->verifySubmitOtpReturn();
            } else if ($this->selected_tab == 'one_way') {
                return $this->verifySubmitOtp();
            } else {
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


    public function render()
    {




        $brands = Brand::where('is_active', 1)->where('is_populer', 1)->get();
        $product = Product::where('is_featured', 1)->get();
        $carousel = Banners::where('ride_type', $this->bannerTab)->get();
        $tour = Banners::where('ride_type', "tour")->get();
        $reviews = Reviews::all();
        $categories = Category::where('in_return', 1)->get();



        return view('livewire.homepage', [
            'brands' => $brands,
            'categories' => $categories,
            'reviews' => $reviews,
            'carousel' => $carousel,
            'tours' => $tour,
            'products' => $product,

        ]);
    }
}
