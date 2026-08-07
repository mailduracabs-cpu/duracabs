<?php

namespace App\Livewire;

use App\Models\Banners;
use App\Models\Product;
use App\Models\Reviews;
use App\Models\User;
use App\Models\WebsiteSetting;
use App\Services\SmartBannerService;
use App\Services\WhatsAppService;
use App\SEO\Services\SeoSchemaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Livewire\Component;
use App\Models\Brand;
use GuzzleHttp\Client;

use App\Models\CustomerSearchActivity;

class Page extends Component
{
	
	 public function detectCurrentLocation(
        $latitude = null,
        $longitude = null
    ): void {
        
    }

public ?string $query_search = '';
public ?string $query2_search = '';
    public $cities_from = [];
    public $cities_to = [];
    public $slug;

    public $bannerTab = 'one_way';

    public $showReview = false;

    public $selected_tab = "one_way";
    
    // Validation error properties
    public $validationErrors = [];
    public $showValidation = false;

    public $query;

    public $queryLocal;
    public $querySelfDrive = '';
    public $selfDrivePlaceId = '';
    public $selfDriveLatitude;
    public $selfDriveLongitude;
    public bool $selfDriveAutocompleteSearched = false;
    public $query_id;

    public $queryFrom;
    public $queryTo;

    public $queryFrom_search = '';
    public $queryTo_search = '';

    public $verifyOtpCheck = false;



    public $query2;
    public $query2_id;

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

    // Reusable Content Writer / SEO properties
    public string $contentType = 'page';
    public string $seoTitle = '';
    public string $seoDescription = '';
    public string $metaKeywords = '';
    public string $robots = 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
    public string $canonicalUrl = '';
    public string $imageMeta = '';
    public string $ogType = 'website';
    public string $ogTitle = '';
    public string $ogDescription = '';
    public string $twitterTitle = '';
    public string $twitterDescription = '';
    public array $breadcrumbSchema = [];
    public array $faqSchema = [];
    public array $pageSchema = [];
    public array $customSchemas = [];
    public array $schemaGraph = [];
    public array $contentLinks = [];
    public array $fareCards = [];

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

    public function mount(string $slug): void
    {
        $this->slug = $slug;

        $page = \App\Models\Page::query()
            ->select(['id', 'content_type', 'self_drive_settings'])
            ->where('slug', $slug)
            ->first();

        if ($page?->content_type === 'service_page') {
            $this->selected_tab = 'self_drive';
            $this->bannerTab = 'self_drive';
            $this->edit_ride_type = 'self_drive';
        }
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
        $mobileval = '7088873332,7088873331,9286240750';
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




    public function sendOtpToBack()
    {
        $this->validate([
            'mobileNumber' => ['required', 'regex:/^[6-9]\\d{9}$/'],
        ], [
            'mobileNumber.required' => 'Please enter your mobile number.',
            'mobileNumber.regex' => 'Please enter a valid 10 digit Indian mobile number.',
        ]);

        $this->mobileNumber = CustomerSearchActivity::normalizeMobile(
            $this->mobileNumber
        );

        $this->saveCustomerLead([
            'stage' => CustomerSearchActivity::STAGE_INITIATED,
            'intent_score' => 30,
            'admin_notified' => false,
            'sms_notified' => false,
            'whatsapp_notified' => false,
            'admin_notified_at' => null,
            'sms_notified_at' => null,
            'whatsapp_notified_at' => null,
        ]);

        $this->sendOtp = true;
        $this->sendOtpVerify = false;
        $this->otp = random_int(1111, 9999);

        $this->sendOtpMessage();
        $this->sendSMS();

        if ($this->selected_tab === 'return') {
            $this->getDistance();
        }

        $this->sendOtpVerify = true;
    }

    public function resendOtp()
    {
        $this->validate([
            'mobileNumber' => ['required', 'digits:10'],
        ]);

        $this->otp = random_int(1111, 9999);
        $this->sendOtpMessage();
        $this->sendOtpVerify = true;
    }

    public function updateOtp()
    {

        if ($this->otp) {

            dd('hello');
        }
    }





    private function saveCustomerLead(array $extra = []): CustomerSearchActivity
    {
        $serviceType = match ($this->selected_tab) {
            'return' => CustomerSearchActivity::SERVICE_ROUND_TRIP,
            'local' => CustomerSearchActivity::SERVICE_LOCAL,
            'self_drive' => CustomerSearchActivity::SERVICE_SELF_DRIVE,
            default => CustomerSearchActivity::SERVICE_ONE_WAY,
        };

        $pickupLocation = match ($serviceType) {
            CustomerSearchActivity::SERVICE_ROUND_TRIP => $this->queryFrom,
            CustomerSearchActivity::SERVICE_LOCAL => $this->queryLocal ?: $this->query,
            CustomerSearchActivity::SERVICE_SELF_DRIVE => $this->querySelfDrive ?: $this->query,
            default => $this->query_search ?: $this->query,
        };

        $dropLocation = match ($serviceType) {
            CustomerSearchActivity::SERVICE_ROUND_TRIP => $this->queryTo,
            CustomerSearchActivity::SERVICE_ONE_WAY => $this->query2_search ?: $this->query2,
            default => null,
        };

        $startDateTime = filled($this->date)
            ? $this->date . ' ' . ($this->time ?: '00:00:00')
            : null;

        $endDateTime = filled($this->dateto)
            ? $this->dateto . ' ' . ($this->endTime ?: $this->time ?: '00:00:00')
            : null;

        $mobile = CustomerSearchActivity::normalizeMobile(
            auth()->user()?->mobile ?? $this->mobileNumber
        );

        if (blank($mobile)) {
            throw new \RuntimeException(
                'Customer inquiry cannot be saved without a mobile number.'
            );
        }

        $identity = [
            'mobile' => $mobile,
            'service_type' => $serviceType,
            'pickup_location' => $pickupLocation,
            'drop_location' => $dropLocation,
            'start_datetime' => $startDateTime,
        ];

        $data = array_merge([
            'user_id' => auth()->id(),
            'customer_name' => auth()->user()?->name ?: $mobile,
            'customer_email' => auth()->user()?->email,
            'source' => CustomerSearchActivity::SOURCE_WEBSITE,
            'platform' => 'website',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'module' => $serviceType === CustomerSearchActivity::SERVICE_SELF_DRIVE
                ? CustomerSearchActivity::MODULE_SELF_DRIVE
                : CustomerSearchActivity::MODULE_TAXI,
            'stage' => CustomerSearchActivity::STAGE_SEARCHED,
            'pickup_city' => match ($serviceType) {
                CustomerSearchActivity::SERVICE_ROUND_TRIP => $this->queryFrom,
                CustomerSearchActivity::SERVICE_LOCAL => $this->queryLocal ?: $this->query,
                CustomerSearchActivity::SERVICE_SELF_DRIVE => $this->querySelfDrive ?: $this->query,
                default => $this->query,
            },
            'drop_city' => match ($serviceType) {
                CustomerSearchActivity::SERVICE_ROUND_TRIP => $this->queryTo,
                CustomerSearchActivity::SERVICE_ONE_WAY => $this->query2,
                default => null,
            },
            'end_datetime' => $serviceType === CustomerSearchActivity::SERVICE_SELF_DRIVE
                ? $endDateTime
                : null,
            'return_datetime' => $serviceType === CustomerSearchActivity::SERVICE_ROUND_TRIP
                ? $endDateTime
                : null,
            'intent_score' => 50,
            'lead_status' => CustomerSearchActivity::LEAD_NEW,
            'search_status' => CustomerSearchActivity::SEARCH_STATUS_ACTIVE,
            'checkout_status' => CustomerSearchActivity::CHECKOUT_NOT_STARTED,
            'payment_status' => CustomerSearchActivity::PAYMENT_NOT_STARTED,
            'is_converted' => false,
            'is_abandoned' => false,
            'admin_notified' => true,
            'sms_notified' => true,
            'whatsapp_notified' => filled(env('ADMIN_MOBILE')),
            'admin_notified_at' => now(),
            'sms_notified_at' => now(),
            'whatsapp_notified_at' => filled(env('ADMIN_MOBILE')) ? now() : null,
            'searched_at' => now(),
            'last_activity_at' => now(),
            'search_data' => [
                'selected_tab' => $this->selected_tab,
                'date' => $this->date,
                'time' => $this->time,
                'end_date' => $this->dateto,
                'end_time' => $this->endTime,
                'plan' => $this->plan,
                'cars' => $this->car,
                'page_slug' => $this->slug,
            ],
        ], $extra);

        try {
            return CustomerSearchActivity::query()->updateOrCreate(
                $identity,
                $data
            );
        } catch (\Throwable $exception) {
            Log::error('Page customer inquiry persistence failed', [
                'mobile' => $mobile,
                'service_type' => $serviceType,
                'pickup_location' => $pickupLocation,
                'drop_location' => $dropLocation,
                'start_datetime' => $startDateTime,
                'error' => $exception->getMessage(),
                'exception' => get_class($exception),
            ]);

            throw $exception;
        }
    }

    public function verifySubmitOtp()
    {
        $result = $this->otp == $this->verifyOtp;

        if ($result) {
            
            $inquiry = $this->saveCustomerLead();
           if (Auth::guest()) {
            $user = User::where('mobile', $this->mobileNumber)->first();
            if (!$user) {
                $user = User::create([
                    'mobile' => $this->resolvedInquiryMobile(),
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
        if ((string) $this->otp !== (string) $this->verifyOtp) {
            $this->addError('verifyOtp', 'Invalid OTP. Please try again.');

            return;
        }



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

        $inquiry = $this->saveCustomerLead([
            'rental_hours' => $hours,
        ]);


        if (Auth::guest()) {
            $user = User::where('mobile', $this->mobileNumber)->first();
            if (!$user) {
                $user = User::create([
                    'mobile' => $this->resolvedInquiryMobile(),
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
            return redirect(route('rides') . '?cityFrom=' . $this->query_id . '&date=' . $this->date . '&dateto=' . $this->dateto . '&nameTo=' . $this->query . '&tab=' . $this->selected_tab . '&time=' . $this->time . '&endTime=' . $this->endTime . '&days=' . $hours);
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
                    'mobile' => $this->resolvedInquiryMobile(),
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

        $inquiry = $this->saveCustomerLead([
            'trip_days' => $dateDiff,
            'estimated_distance_km' => round(((float) $kmValue) / 1000, 2),
            'estimated_duration_minutes' => (int) round(((float) $timeValue) / 60),
        ]);

        if ($inquiry) {
            return redirect(route('rides') . '?nameTo=' . $this->queryFrom . '&date=' . $this->date . '&dateto=' . $this->dateto . '&cityFrom=' . $this->queryTo . '&tab=' . $this->selected_tab . '&km=' . $km . '&kmValue=' . $kmValue . '&time=' . $this->time . '&timeValue=' . $timeValue . '&days=' . $dateDiff);
        }
    }

    public function verifySubmitLocal()
    {
        $result = $this->otp == $this->verifyOtp;

        $inquiry = $this->saveCustomerLead();

        if (Auth::guest()) {
            $user = User::where('mobile', $this->mobileNumber)->first();
            if (!$user) {
                $user = User::create([
                    'mobile' => $this->resolvedInquiryMobile(),
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
    
    public function updatedQuerySelfDrive($querySelfDrive): void
    {
        $this->clearError('query');
        $this->resetSelfDriveSelectedPlace();

        $input = trim((string) $querySelfDrive);

        if (mb_strlen($input) < 3) {
            $this->cities_from = [];
            $this->selfDriveAutocompleteSearched = false;

            return;
        }

        $this->selfDriveAutocompleteSearched = true;
        $this->cities_from = $this->googleSelfDriveAutocomplete($input);
    }

    public function selectGooglePlace(string $type, string $placeId): void
    {
        if ($type !== 'self_drive' || trim($placeId) === '') {
            return;
        }

        $place = $this->googlePlaceDetails($placeId);

        if ($place === null) {
            $this->cities_from = [];
            $this->addSelfDriveLocationError('Unable to read this location. Please select another suggestion.');

            return;
        }

        $brand = $this->resolveSelfDriveBrand($place);

        if ($brand === null) {
            $this->cities_from = [];
            $this->addSelfDriveLocationError('Self Drive service is not available in this city yet.');

            return;
        }

        $description = trim((string) ($place['formatted_address'] ?? $place['name'] ?? $brand->name));
        $location = data_get($place, 'geometry.location', []);

        $this->querySelfDrive = $description !== '' ? $description : $brand->name;
        $this->query = $brand->name;
        $this->query_id = $brand->id;
        $this->selfDrivePlaceId = $placeId;
        $this->selfDriveLatitude = data_get($location, 'lat');
        $this->selfDriveLongitude = data_get($location, 'lng');
        $this->cities_from = [];
        $this->selfDriveAutocompleteSearched = false;
        $this->clearError('query');
    }

    private function googleSelfDriveAutocomplete(string $input): array
{
    $apiKey = $this->googleMapsApiKey();

    if ($apiKey === '') {
        Log::warning(
            'Google Maps API key is missing for self-drive autocomplete.'
        );

        return [];
    }

    try {
        $response = (new Client([
            'timeout' => 8,
        ]))->get(
            'https://maps.googleapis.com/maps/api/place/autocomplete/json',
            [
                'query' => [
                    'input' => $input,
                    'key' => $apiKey,
                    'components' => 'country:in',
                    'language' => 'en',
                ],
            ]
        );

        $payload = json_decode(
            (string) $response->getBody(),
            true
        );

        $status = (string) ($payload['status'] ?? '');

        if (!in_array($status, ['OK', 'ZERO_RESULTS'], true)) {
            Log::warning(
                'Google self-drive autocomplete returned an error.',
                [
                    'status' => $status,
                    'error_message' => $payload['error_message'] ?? null,
                ]
            );

            return [];
        }

        return collect($payload['predictions'] ?? [])
            ->filter(function ($prediction) {
                return is_array($prediction)
                    && filled($prediction['place_id'] ?? null)
                    && filled($prediction['description'] ?? null);
            })
            ->map(function (array $prediction) {
                return (object) [
                    /*
                     * id is included for compatibility with any old Blade
                     * code that still reads $city->id.
                     */
                    'id' => (string) $prediction['place_id'],

                    'place_id' => (string) $prediction['place_id'],

                    'name' => (string) $prediction['description'],

                    'description' => (string) $prediction['description'],

                    'structured_formatting' => (object) [
                        'main_text' => (string) data_get(
                            $prediction,
                            'structured_formatting.main_text',
                            $prediction['description']
                        ),

                        'secondary_text' => (string) data_get(
                            $prediction,
                            'structured_formatting.secondary_text',
                            ''
                        ),
                    ],
                ];
            })
            ->values()
            ->all();

    } catch (\Throwable $exception) {
        Log::error(
            'Google self-drive autocomplete failed.',
            [
                'input' => $input,
                'error' => $exception->getMessage(),
            ]
        );

        return [];
    }
}

    private function googlePlaceDetails(string $placeId): ?array
    {
        $apiKey = $this->googleMapsApiKey();

        if ($apiKey === '') {
            return null;
        }

        try {
            $response = (new Client(['timeout' => 8]))->get(
                'https://maps.googleapis.com/maps/api/place/details/json',
                [
                    'query' => [
                        'place_id' => $placeId,
                        'key' => $apiKey,
                        'language' => 'en',
                        'fields' => 'place_id,name,formatted_address,address_components,geometry',
                    ],
                ]
            );

            $payload = json_decode((string) $response->getBody(), true);

            if (($payload['status'] ?? null) !== 'OK' || !is_array($payload['result'] ?? null)) {
                Log::warning('Google place details returned an error.', [
                    'place_id' => $placeId,
                    'status' => $payload['status'] ?? null,
                    'error_message' => $payload['error_message'] ?? null,
                ]);

                return null;
            }

            return $payload['result'];
        } catch (\Throwable $exception) {
            Log::error('Google place details failed.', [
                'place_id' => $placeId,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function resolveSelfDriveBrand(array $place): ?Brand
    {
        $locationNames = [];

        foreach (($place['address_components'] ?? []) as $component) {
            if (!is_array($component)) {
                continue;
            }

            $types = $component['types'] ?? [];

            if (array_intersect([
                'locality',
                'administrative_area_level_3',
                'administrative_area_level_2',
                'postal_town',
            ], $types)) {
                $locationNames[] = (string) ($component['long_name'] ?? '');
                $locationNames[] = (string) ($component['short_name'] ?? '');
            }
        }

        $locationNames[] = (string) ($place['name'] ?? '');
        $locationNames[] = (string) ($place['formatted_address'] ?? '');
        $locationNames = array_values(array_unique(array_filter(array_map('trim', $locationNames))));

        $brands = Brand::query()
            ->where('is_active', 1)
            ->where('is_selfdrive', 1)
            ->get(['id', 'name']);

        foreach ($brands as $brand) {
            $brandCity = trim((string) Str::before((string) $brand->name, ','));
            $normalisedBrandCity = $this->normaliseLocationName($brandCity);

            if ($normalisedBrandCity === '') {
                continue;
            }

            foreach ($locationNames as $locationName) {
                $normalisedLocation = $this->normaliseLocationName($locationName);

                if ($normalisedLocation === $normalisedBrandCity
                    || str_contains($normalisedLocation, $normalisedBrandCity)) {
                    return $brand;
                }
            }
        }

        return null;
    }

    private function normaliseLocationName(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->toString();
    }

    private function googleMapsApiKey(): string
    {
        return trim((string) (
            config('services.google_maps.key')
            ?: env('GOOGLE_MAPS_API_KEY')
            ?: $this->apiKey
        ));
    }

    private function resetSelfDriveSelectedPlace(): void
    {
        $this->selfDrivePlaceId = '';
        $this->selfDriveLatitude = null;
        $this->selfDriveLongitude = null;
        $this->query = null;
        $this->query_id = null;
    }

    private function addSelfDriveLocationError(string $message): void
    {
        $this->validationErrors['query'] = $message;
        $this->showValidation = true;
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
    public function updateSelfDriveCity($name, $id): void
    {
        $this->querySelfDrive = $name;
        $this->query = $name;
        $this->query_id = $id;
        $this->selfDrivePlaceId = 'brand-' . $id;
        $this->cities_from = [];
        $this->selfDriveAutocompleteSearched = false;
        $this->clearError('query');
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


    private function resolvedInquiryMobile(): string
    {
        $mobile = auth()->user()?->mobile
            ?? $this->mobileNumber;

        if (blank($mobile)) {
            throw new \RuntimeException('A valid mobile number is required to continue.');
        }

        return (string) $mobile;
    }

    public function searchPackage()
    {
        if (!$this->validateForm()) {
            return;
        }

        $this->validationErrors = [];
        $this->showValidation = false;

        if (!Auth::check() || blank(auth()->user()?->mobile)) {
            $this->mobileNumber = auth()->user()?->mobile ?? $this->mobileNumber;
            $this->sendOtp = true;

            return;
        }

        if ($this->selected_tab === 'local') {
            return $this->verifySubmitLocal();
        }

        if ($this->selected_tab === 'self_drive') {
            return $this->verifySubmitOtpSelfDrive();
        }

        if ($this->selected_tab === 'return') {
            $this->getDistance();

            return $this->verifySubmitOtpReturn();
        }

        if ($this->selected_tab === 'one_way') {
            return $this->verifySubmitOtp();
        }
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
        $page = \App\Models\Page::query()
            ->where('slug', $this->slug)
            ->firstOrFail();

        $this->prepareContentWriterData($page);

        $carousel = Banners::query()
            ->where('ride_type', $this->bannerTab)
            ->get();

        $smartHeroBanners = app(SmartBannerService::class)
            ->getSection('hero_banners');

        $reviews = Reviews::query()->latest()->get();

        $products = Product::query()
            ->where('is_featured', 1)
            ->get();

        $viewData = [
            'page' => $page,
            'carousel' => $carousel,
            'smartHeroBanners' => $smartHeroBanners,
            'reviews' => $reviews,
            'products' => $products,
            'imageMeta' => $this->imageMeta,
            'seoTitle' => $this->seoTitle,
            'seoDescription' => $this->seoDescription,
            'metaKeywords' => $this->metaKeywords,
            'robots' => $this->robots,
            'canonicalUrl' => $this->canonicalUrl,
            'ogType' => $this->ogType,
            'ogTitle' => $this->ogTitle,
            'ogDescription' => $this->ogDescription,
            'twitterTitle' => $this->twitterTitle,
            'twitterDescription' => $this->twitterDescription,
            'breadcrumbSchema' => $this->breadcrumbSchema,
            'faqSchema' => $this->faqSchema,
            'pageSchema' => $this->pageSchema,
            'customSchemas' => $this->customSchemas,
            'schemaGraph' => $this->schemaGraph,
            'contentLinks' => $this->contentLinks,
            'fareCards' => $this->fareCards,
            'contentType' => $this->contentType,
            'selfDriveSettings' => is_array($page->self_drive_settings)
                ? $page->self_drive_settings
                : [],
        ];

        $selfDrivePageEnabled = $page->content_type === 'service_page'
            && (bool) data_get($page->self_drive_settings, 'page_enabled', true);

        if ($selfDrivePageEnabled) {
            return view('livewire.self-drive-page', $viewData);
        }

        return view('livewire.page', $viewData);
    }

    private function prepareContentWriterData(object $page): void
    {
        $title = $this->firstFilled($page, [
            'seo_title', 'meta_title', 'title', 'name',
        ]) ?: Str::headline($this->slug);

        $description = $this->firstFilled($page, [
            'seo_description', 'meta_description', 'excerpt', 'short_description', 'description',
        ]);

        $plainDescription = Str::of((string) $description)
            ->stripTags()
            ->squish()
            ->limit(160, '')
            ->toString();

        if ($plainDescription === '') {
            $plainDescription = "Explore {$title} with Dura Cabs. Check service details, availability and booking information.";
        }

        $this->contentType = strtolower((string) ($this->firstFilled($page, ['content_type', 'type', 'page_type']) ?: 'page'));
        $this->seoTitle = Str::limit(trim((string) $title), 60, '');
        $this->seoDescription = $plainDescription;
        $this->metaKeywords = $this->normaliseKeywords($this->firstFilled($page, ['meta_keywords', 'seo_keywords', 'keywords']));
        $this->robots = (string) ($this->firstFilled($page, ['robots', 'meta_robots']) ?: $this->robots);
        $this->canonicalUrl = $this->resolveCanonicalUrl($page);
        $this->imageMeta = $this->resolveSeoImage($page);
        $this->ogType = $this->contentType === 'blog' ? 'article' : 'website';
        $this->ogTitle = (string) ($this->firstFilled($page, ['og_title']) ?: $this->seoTitle);
        $this->ogDescription = Str::limit((string) ($this->firstFilled($page, ['og_description']) ?: $this->seoDescription), 200, '');
        $this->twitterTitle = (string) ($this->firstFilled($page, ['twitter_title']) ?: $this->ogTitle);
        $this->twitterDescription = Str::limit((string) ($this->firstFilled($page, ['twitter_description']) ?: $this->ogDescription), 200, '');
        $this->contentLinks = $this->normaliseArray($this->attributeValue($page, 'content_links'));
        $this->fareCards = $this->normaliseArray($this->attributeValue($page, 'fare_cards'));
        $this->breadcrumbSchema = $this->buildBreadcrumbSchema($page);
        $this->faqSchema = $this->buildFaqSchema($page);
        $this->pageSchema = $this->buildPageSchema($page);
        $this->customSchemas = $this->normaliseCustomSchemas($this->attributeValue($page, 'custom_schema'));
        $this->schemaGraph = app(SeoSchemaService::class)->pageModelGraph($page);
    }

    private function firstFilled(object $model, array $keys): mixed
    {
        foreach ($keys as $key) {
            $value = $this->attributeValue($model, $key);

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function attributeValue(object $model, string $key): mixed
    {
        if (method_exists($model, 'getAttribute')) {
            return $model->getAttribute($key);
        }

        return $model->{$key} ?? null;
    }

    private function resolveCanonicalUrl(object $page): string
    {
        $canonical = $this->firstFilled($page, ['canonical', 'canonical_url']);

        if (is_string($canonical) && trim($canonical) !== '') {
            return Str::startsWith($canonical, ['http://', 'https://'])
                ? $canonical
                : url('/' . ltrim($canonical, '/'));
        }

        return route('pages.show', ['slug' => $this->slug]);
    }

    private function resolveSeoImage(object $page): string
    {
        $image = $this->firstFilled($page, [
            'og_image', 'seo_image', 'featured_image', 'image',
        ]);

        if (!is_string($image) || trim($image) === '') {
            return asset('images/logo.png');
        }

        if (Str::startsWith($image, ['http://', 'https://'])) {
            return $image;
        }

        $path = ltrim($image, '/');

        if (Str::startsWith($path, ['storage/', 'images/', 'cab_images/'])) {
            return asset($path);
        }

        return asset('storage/' . $path);
    }

    private function normaliseKeywords(mixed $keywords): string
    {
        if (is_array($keywords)) {
            return collect($keywords)->filter()->map(fn ($item) => trim((string) $item))->implode(', ');
        }

        if (!is_string($keywords)) {
            return '';
        }

        $decoded = json_decode($keywords, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return collect($decoded)->filter()->map(fn ($item) => trim((string) $item))->implode(', ');
        }

        return collect(preg_split('/[,\n]+/', $keywords) ?: [])
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->unique()
            ->implode(', ');
    }

    private function normaliseArray(mixed $value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? array_values($decoded) : [];
        }

        return [];
    }

    private function buildBreadcrumbSchema(object $page): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            '@id' => $this->canonicalUrl . '#breadcrumb',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Home',
                    'item' => rtrim(url('/'), '/'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => (string) (
                        $this->firstFilled($page, ['title', 'name'])
                        ?: $this->seoTitle
                    ),
                    'item' => $this->canonicalUrl,
                ],
            ],
        ];
    }

    private function buildFaqSchema(object $page): array
    {
        $faqs = $this->normaliseArray($this->firstFilled($page, ['faqs', 'faq_schema', 'faq']));
        $entities = [];

        foreach ($faqs as $faq) {
            if (!is_array($faq)) {
                continue;
            }

            $question = trim((string) ($faq['question'] ?? $faq['title'] ?? ''));
            $answer = trim(strip_tags((string) ($faq['answer'] ?? $faq['description'] ?? '')));

            if ($question === '' || $answer === '') {
                continue;
            }

            $entities[] = [
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $answer,
                ],
            ];
        }

        if ($entities === []) {
            return [];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            '@id' => $this->canonicalUrl . '#faq',
            'url' => $this->canonicalUrl,
            'isPartOf' => [
                '@id' => $this->canonicalUrl . '#webpage',
            ],
            'mainEntity' => $entities,
        ];
    }

    private function buildPageSchema(object $page): array
    {
        $schemaType = match ($this->contentType) {
            'blog', 'article' => 'Article',
            'product' => 'Product',
            default => 'WebPage',
        };

        /** @var WebsiteSetting $settings */
        $settings = \App\Support\SiteCache::settings();

        $homeUrl = rtrim(url('/'), '/') . '/';
        $organizationId = $homeUrl . '#organization';
        $websiteId = $homeUrl . '#website';
        $pageId = $this->canonicalUrl . '#webpage';
        $imageId = $this->canonicalUrl . '#primaryimage';

        $pageName = trim((string) (
            $this->firstFilled($page, ['title', 'name'])
            ?: $this->seoTitle
        ));

        $aboutName = trim((string) (
            data_get($page, 'brand.name')
            ?: $pageName
        ));

        $schema = array_filter([
            '@context' => 'https://schema.org',
            '@type' => $schemaType,
            '@id' => $pageId,
            'url' => $this->canonicalUrl,
            'name' => $this->seoTitle,
            'description' => $this->seoDescription,
            'inLanguage' => str_replace(
                '_',
                '-',
                app()->getLocale()
            ),
            'mainEntityOfPage' => [
                '@id' => $pageId,
            ],
            'isPartOf' => [
                '@id' => $websiteId,
            ],
            'publisher' => [
                '@id' => $organizationId,
            ],
            'breadcrumb' => [
                '@id' => $this->canonicalUrl . '#breadcrumb',
            ],
            'about' => $aboutName !== ''
                ? [
                    '@type' => filled(data_get($page, 'brand.name'))
                        ? 'Place'
                        : 'Thing',
                    'name' => $aboutName,
                ]
                : null,
            'image' => filled($this->imageMeta)
                ? [
                    '@type' => 'ImageObject',
                    '@id' => $imageId,
                    'url' => $this->imageMeta,
                    'contentUrl' => $this->imageMeta,
                    'caption' => $this->seoTitle,
                ]
                : null,
            'primaryImageOfPage' => filled($this->imageMeta)
                ? [
                    '@id' => $imageId,
                ]
                : null,
            'dateModified' => $this->normaliseSchemaDate(
                $this->attributeValue($page, 'updated_at')
            ),
        ], static fn (mixed $value): bool =>
            $value !== null
            && $value !== ''
            && $value !== []
        );

        if ($schemaType === 'Article') {
            $schema['headline'] = $this->seoTitle;
            $schema['datePublished'] = $this->normaliseSchemaDate(
                $this->attributeValue($page, 'created_at')
            );
            $schema['author'] = [
                '@id' => $organizationId,
            ];

            return array_filter(
                $schema,
                static fn (mixed $value): bool =>
                    $value !== null
                    && $value !== ''
                    && $value !== []
            );
        }

        /*
         * Keep one page-specific Service entity nested as the WebPage's
         * mainEntity. Global business information remains in the single
         * Organization/TaxiService entity rendered by the main layout.
         */
        $schema['mainEntity'] = array_filter([
            '@type' => 'Service',
            '@id' => $this->canonicalUrl . '#service',
            'name' => $this->pageServiceName($page),
            'url' => $this->canonicalUrl,
            'description' => $this->seoDescription,
            'serviceType' => $this->pageServiceType($page),
            'provider' => [
                '@id' => $organizationId,
            ],
            'areaServed' => $this->pageServiceAreas($page),
            'image' => filled($this->imageMeta)
                ? $this->imageMeta
                : null,
            'aggregateRating' =>
                $settings->aggregateRatingSchema(),
        ], static fn (mixed $value): bool =>
            $value !== null
            && $value !== ''
            && $value !== []
        );

        return array_filter(
            $schema,
            static fn (mixed $value): bool =>
                $value !== null
                && $value !== ''
                && $value !== []
        );
    }

    /**
     * Build a page-specific service name.
     */
    private function pageServiceName(object $page): string
    {
        $name = trim((string) (
            $this->firstFilled($page, ['title', 'name'])
            ?: $this->seoTitle
        ));

        if ($name === '') {
            $name = Str::headline($this->slug);
        }

        return Str::contains(
            Str::lower($name),
            ['service', 'taxi', 'cab', 'rental', 'tour']
        )
            ? $name
            : $name . ' Taxi Service';
    }

    /**
     * Resolve service category from the current page only.
     */
    private function pageServiceType(object $page): string
    {
        $source = Str::lower(implode(' ', array_filter([
            (string) $this->contentType,
            (string) $this->slug,
            (string) $this->firstFilled($page, ['title', 'name']),
        ])));

        return match (true) {
            Str::contains($source, ['self-drive', 'self drive']) =>
                'Self Drive Car Rental',
            Str::contains($source, ['airport']) =>
                'Airport Taxi Service',
            Str::contains($source, ['local']) =>
                'Local Taxi Service',
            Str::contains($source, ['tour']) =>
                'Tour Taxi Service',
            Str::contains($source, ['round-trip', 'round trip', 'return']) =>
                'Round Trip Taxi Service',
            default => 'One Way Taxi Service',
        };
    }

    /**
     * Build service areas from the current page title/brand only.
     *
     * @return array<int, array<string, string>>
     */
    private function pageServiceAreas(object $page): array
    {
        $title = trim((string) (
            $this->firstFilled($page, ['title', 'name'])
            ?: Str::headline($this->slug)
        ));

        $cleanTitle = Str::of($title)
            ->replaceMatches(
                '/\b(taxi|cab|service|booking|rental|one way|round trip)\b/i',
                ' '
            )
            ->squish()
            ->toString();

        $areas = [];

        if (preg_match('/^(.+?)\s+to\s+(.+?)$/i', $cleanTitle, $matches)) {
            $areas = [
                trim($matches[1]),
                trim($matches[2]),
            ];
        } else {
            $brandName = trim((string) data_get($page, 'brand.name'));

            if ($brandName !== '') {
                $areas[] = $brandName;
            } elseif ($cleanTitle !== '') {
                $areas[] = $cleanTitle;
            }
        }

        return collect($areas)
            ->map(static fn (string $area): array => [
                '@type' => 'City',
                'name' => $area,
            ])
            ->unique('name')
            ->values()
            ->all();
    }

    private function normaliseSchemaDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toAtomString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function normaliseCustomSchemas(mixed $schema): array
    {
        if (is_string($schema) && trim($schema) !== '') {
            $schema = json_decode($schema, true);
        }

        if (!is_array($schema)) {
            return [];
        }

        if (isset($schema['@type']) || isset($schema['@context'])) {
            return [$schema];
        }

        return array_values(array_filter($schema, fn ($item) => is_array($item)));
    }

}