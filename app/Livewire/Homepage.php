<?php

namespace App\Livewire;

use App\Models\Banners;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CustomerSearchActivity;

use App\Models\Page as SeoPage;
use App\Models\Vehicle;
use App\Models\Reviews;
use App\Models\User;
use App\Services\WhatsAppService;
use App\Livewire\Concerns\HandlesOtpCustomerAuthentication;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;
use GuzzleHttp\Client;
use App\Services\SmartBannerService;


class Homepage extends Component
{
    use HandlesOtpCustomerAuthentication;

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

    // Google Places selected location data
    public $oneWayFromPlaceId;
    public $oneWayFromLatitude;
    public $oneWayFromLongitude;
    public $oneWayToPlaceId;
    public $oneWayToLatitude;
    public $oneWayToLongitude;
    public $roundFromPlaceId;
    public $roundFromLatitude;
    public $roundFromLongitude;
    public $roundToPlaceId;
    public $roundToLatitude;
    public $roundToLongitude;

    /**
     * Additional Round Trip destinations. The first destination continues to
     * use queryTo/queryTo_search for backward compatibility.
     */
    public array $tripCities = [];
    public int $maxTripCities = 19;
    public $localPlaceId;
    public $localLatitude;
    public $localLongitude;
    public $selfDrivePlaceId;
    public $selfDriveLatitude;
    public $selfDriveLongitude;
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
        $this->validate([
            'mobileNumber' => ['required', 'regex:/^[6-9]\d{9}$/'],
        ], [
            'mobileNumber.required' => 'Please enter your mobile number.',
            'mobileNumber.regex' => 'Please enter a valid 10 digit Indian mobile number.',
        ]);

        $this->mobileNumber = CustomerSearchActivity::normalizeMobile(
            $this->mobileNumber
        );

        /*
         * Save the inquiry BEFORE sending SMS/WhatsApp. This guarantees that
         * the record reaches the admin panel even when a notification provider
         * is slow or temporarily unavailable.
         */
        $lead = $this->saveCustomerLead([
            'stage' => CustomerSearchActivity::STAGE_INITIATED,
            'intent_score' => 30,
            'admin_notified' => false,
            'sms_notified' => false,
            'whatsapp_notified' => false,
            'admin_notified_at' => null,
            'sms_notified_at' => null,
            'whatsapp_notified_at' => null,
        ]);

        Log::info('Website inquiry saved before OTP delivery', [
            'customer_search_activity_id' => $lead->getKey(),
            'uuid' => $lead->uuid,
            'mobile' => $lead->mobile,
            'service_type' => $lead->service_type,
        ]);

        $this->sendOtp = false;
        $this->otp = rand(1000, 9999);
        $this->sendOtpVerify = true;

        $this->sendOtpMessage();
        $this->sendSMS();

        $lead->forceFill([
            'admin_notified' => true,
            'sms_notified' => true,
            'whatsapp_notified' => filled(env('ADMIN_MOBILE')),
            'admin_notified_at' => now(),
            'sms_notified_at' => now(),
            'whatsapp_notified_at' => filled(env('ADMIN_MOBILE')) ? now() : null,
        ])->save();

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
	public $bannerTab = 'one_way';

    public function verifySubmitOtp()
    {
        $result = $this->otp == $this->verifyOtp;

        if ($result) {
            $this->authenticateOtpCustomer();

            
            $this->saveCustomerLead();

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


          $this->authenticateOtpCustomer();


         

        if ($result) {
            $this->saveCustomerLead([
                'rental_hours' => $hours,
            ]);

            redirect(route('rides') . '?cityFrom=' . $this->query_id . '&date=' . $this->date . '&dateto=' . $this->dateto . '&nameTo=' . $this->query . '&tab=' . $this->selected_tab . '&time=' . $this->time . '&endTime=' . $this->endTime . '&days=' . $hours . '&place_id=' . urlencode((string) $this->selfDrivePlaceId) . '&lat=' . urlencode((string) $this->selfDriveLatitude) . '&lng=' . urlencode((string) $this->selfDriveLongitude) . '&address=' . urlencode((string) $this->querySelfDrive));
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
            auth()->user()->mobile ?? $this->mobileNumber
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
            'pickup_city' => $serviceType === CustomerSearchActivity::SERVICE_ROUND_TRIP
                ? $this->queryFrom
                : $this->query,
            'pickup_latitude' => $this->selected_tab === 'return'
                ? $this->roundFromLatitude
                : ($this->selected_tab === 'self_drive'
                    ? $this->selfDriveLatitude
                    : ($this->selected_tab === 'local'
                        ? $this->localLatitude
                        : $this->oneWayFromLatitude)),
            'pickup_longitude' => $this->selected_tab === 'return'
                ? $this->roundFromLongitude
                : ($this->selected_tab === 'self_drive'
                    ? $this->selfDriveLongitude
                    : ($this->selected_tab === 'local'
                        ? $this->localLongitude
                        : $this->oneWayFromLongitude)),
            'pickup_place_id' => $this->selected_tab === 'return'
                ? $this->roundFromPlaceId
                : ($this->selected_tab === 'self_drive'
                    ? $this->selfDrivePlaceId
                    : ($this->selected_tab === 'local'
                        ? $this->localPlaceId
                        : $this->oneWayFromPlaceId)),
            'drop_city' => $serviceType === CustomerSearchActivity::SERVICE_ROUND_TRIP
                ? $this->queryTo
                : ($serviceType === CustomerSearchActivity::SERVICE_ONE_WAY ? $this->query2 : null),
            'drop_latitude' => $this->selected_tab === 'return'
                ? $this->roundToLatitude
                : $this->oneWayToLatitude,
            'drop_longitude' => $this->selected_tab === 'return'
                ? $this->roundToLongitude
                : $this->oneWayToLongitude,
            'drop_place_id' => $this->selected_tab === 'return'
                ? $this->roundToPlaceId
                : $this->oneWayToPlaceId,
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
                'trip_cities' => $serviceType === CustomerSearchActivity::SERVICE_ROUND_TRIP
                    ? $this->selectedTripCities()
                    : [],
                'return_to_pickup' => $serviceType === CustomerSearchActivity::SERVICE_ROUND_TRIP,
            ],
        ], $extra);

        try {
            $lead = CustomerSearchActivity::query()->updateOrCreate(
                $identity,
                $data
            );

            Log::info('Customer inquiry persisted', [
                'customer_search_activity_id' => $lead->getKey(),
                'uuid' => $lead->uuid,
                'mobile' => $lead->mobile,
                'service_type' => $lead->service_type,
                'stage' => $lead->stage,
                'lead_status' => $lead->lead_status,
            ]);

            return $lead;
        } catch (\Throwable $exception) {
            Log::error('Customer inquiry persistence failed', [
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

    public function getDistance(): void
    {
        $routePoints = $this->roundTripRoutePoints();

        if (count($routePoints) < 3) {
            $this->distanceData = null;
            return;
        }

        $apiKey = $this->googleMapsApiKey();
        if (!$apiKey) {
            $this->distanceData = null;
            return;
        }

        try {
            // Directions returns every route leg in one request and supports
            // the additional stops used by Multi-City Round Trip.
            $origin = array_shift($routePoints);
            $destination = array_pop($routePoints);

            $query = [
                'origin' => $origin,
                'destination' => $destination,
                'key' => $apiKey,
                'language' => 'en',
            ];

            if ($routePoints !== []) {
                $query['waypoints'] = implode('|', $routePoints);
            }

            $response = (new Client())->get('https://maps.googleapis.com/maps/api/directions/json', [
                'query' => $query,
                'timeout' => 20,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $legs = data_get($data, 'routes.0.legs', []);

            if (($data['status'] ?? null) !== 'OK' || empty($legs)) {
                $this->distanceData = null;
                Log::warning('Google Directions route unavailable', [
                    'status' => $data['status'] ?? null,
                    'error_message' => $data['error_message'] ?? null,
                ]);
                return;
            }

            $distanceValue = (int) collect($legs)->sum(fn (array $leg) => (int) data_get($leg, 'distance.value', 0));
            $durationValue = (int) collect($legs)->sum(fn (array $leg) => (int) data_get($leg, 'duration.value', 0));

            $this->distanceData = [
                'rows' => [[
                    'elements' => [[
                        'status' => 'OK',
                        'distance' => [
                            'text' => number_format($distanceValue / 1000, 1) . ' km',
                            'value' => $distanceValue,
                        ],
                        'duration' => [
                            'text' => $this->formatDuration($durationValue),
                            'value' => $durationValue,
                        ],
                    ]],
                ]],
                'route_legs' => $legs,
                'route_points' => $this->roundTripRoutePoints(false),
            ];
        } catch (\Throwable $e) {
            Log::error('Google Directions calculation failed', ['message' => $e->getMessage()]);
            $this->distanceData = null;
        }
    }

    private function roundTripRoutePoints(bool $preferCoordinates = true): array
    {
        $points = [];

        $points[] = $this->routePoint(
            $this->roundFromLatitude,
            $this->roundFromLongitude,
            $this->queryFrom_search ?: $this->queryFrom,
            $preferCoordinates
        );

        $points[] = $this->routePoint(
            $this->roundToLatitude,
            $this->roundToLongitude,
            $this->queryTo_search ?: $this->queryTo,
            $preferCoordinates
        );

        foreach ($this->selectedTripCities() as $city) {
            $points[] = $this->routePoint(
                $city['latitude'] ?? null,
                $city['longitude'] ?? null,
                $city['address'] ?? $city['search'] ?? null,
                $preferCoordinates
            );
        }

        // Round Trip always returns to its pickup location.
        $points[] = $points[0] ?? null;

        return array_values(array_filter($points, fn ($point) => filled($point)));
    }

    private function routePoint($latitude, $longitude, $address, bool $preferCoordinates): ?string
    {
        if ($preferCoordinates && is_numeric($latitude) && is_numeric($longitude)) {
            return $latitude . ',' . $longitude;
        }

        $address = trim((string) $address);
        return $address !== '' ? $address : null;
    }

    private function selectedTripCities(): array
    {
        return array_values(array_filter($this->tripCities, static function (array $city): bool {
            return filled($city['place_id'] ?? null)
                && filled($city['address'] ?? null)
                && is_numeric($city['latitude'] ?? null)
                && is_numeric($city['longitude'] ?? null);
        }));
    }

    private function formatDuration(int $seconds): string
    {
        $minutes = (int) round($seconds / 60);
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        return trim(($hours > 0 ? $hours . ' hr ' : '') . ($remainingMinutes > 0 ? $remainingMinutes . ' min' : ''));
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

        if (!$result) {
            $this->oneWayMsg = 'Invalid OTP. Please try again.';
            return;
        }

        if (!$this->distanceData) {
            $this->oneWayMsg = 'Unable to calculate route distance. Please select both places again.';
            return;
        }

        $km = $this->distanceData['rows'][0]['elements'][0]['distance']['text'];
        $kmValue = $this->distanceData['rows'][0]['elements'][0]['distance']['value'];
        //$time = $this->distanceData['rows'][0]['elements'][0]['duration']['text'];
        $timeValue = $this->distanceData['rows'][0]['elements'][0]['duration']['value'];






        $date1 = $this->date;

        // End date
        $date2 = $this->dateto;

        // Function call to find date difference
        $dateDiff = $this->dateDiffInDays($date1, $date2);

       $this->authenticateOtpCustomer();

        





        $this->saveCustomerLead([
            'estimated_distance_km' => round(((float) $kmValue) / 1000, 2),
            'estimated_duration_minutes' => (int) round(((float) $timeValue) / 60),
            'trip_days' => $dateDiff,
        ]);

        $tripCities = array_map(
            static fn (array $city): array => [
                'address' => $city['address'],
                'place_id' => $city['place_id'],
                'latitude' => $city['latitude'],
                'longitude' => $city['longitude'],
            ],
            $this->selectedTripCities()
        );

        return redirect(route('rides') . '?' . http_build_query([
            'nameTo' => $this->queryFrom,
            'date' => $this->date,
            'dateto' => $this->dateto,
            'cityFrom' => $this->queryTo,
            'tab' => $this->selected_tab,
            'km' => $km,
            'kmValue' => $kmValue,
            'time' => $this->time,
            'timeValue' => $timeValue,
            'days' => $dateDiff,
            'tripCities' => json_encode($tripCities, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'returnToPickup' => 1,
        ]));
    }

    public function verifySubmitLocal()
    {
        $result = $this->otp == $this->verifyOtp;

        if (!$result) {
            $this->oneWayMsg = 'Invalid OTP. Please try again.';
            return;
        }



      
        $this->authenticateOtpCustomer();

               

             $this->saveCustomerLead([
                 'package_name' => $this->plan,
             ]);

             if ($inquery) {
                    redirect(route('rides') . '?cityFrom=' . $this->query_id . '&date=' . $this->date . '&nameTo=' . $this->query . '&tab=' . $this->selected_tab . '&time=' . $this->time . '&plan=' . $this->plan . '&cars=' . $this->car);
             }
    }





    public function updatedQuerySearch($value)
    {
        $this->oneWayMsg = '';
        $this->clearSelectedPlace('one_way_from');
        $this->cities_from = $this->googleAutocomplete($value);
    }

    public function updatedQuery2Search($value)
    {
        $this->oneWayMsg = '';
        $this->clearSelectedPlace('one_way_to');
        $this->cities_to = $this->googleAutocomplete($value);
    }

    public function updatedQueryLocal($value)
    {
        $this->clearSelectedPlace('local');
        $this->cities_from = $this->googleAutocomplete($value);
    }

    public function updatedQuerySelfDrive($value)

{
    // Agar Google Place already select ho chuka hai to
    // dobara autocomplete mat chalao.
    if (!empty($this->selfDrivePlaceId)) {
        $this->cities_from = null;
        return;
    }

    $this->clearSelectedPlace('self_drive');

    $value = trim((string) $value);

    if (mb_strlen($value) < 3) {
        $this->cities_from = null;
        return;
    }

    $this->cities_from = $this->googleAutocomplete($value);
}

    public function updatedQueryFromSearch($value)
    {
        $this->clearSelectedPlace('round_from');
        $this->dataFrom = $this->googleAutocomplete($value);
    }

    public function updatedQueryToSearch($value)
    {
        $this->clearSelectedPlace('round_to');
        $this->dataTo = $this->googleAutocomplete($value);
    }

    public function addTripCity(): void
    {
        if (count($this->tripCities) >= $this->maxTripCities) {
            $this->validationErrors['tripCities'] = 'You can add up to 20 destination cities.';
            $this->showValidation = true;
            return;
        }

        $this->tripCities[] = $this->emptyTripCity();
        $this->clearError('tripCities');
    }

    public function removeTripCity(int $index): void
    {
        if (!array_key_exists($index, $this->tripCities)) {
            return;
        }

        unset($this->tripCities[$index]);
        $this->tripCities = array_values($this->tripCities);
        $this->clearError('tripCities');
    }

    public function updatedTripCities($value, string $key): void
    {
        [$index, $field] = array_pad(explode('.', $key, 2), 2, null);
        $index = filter_var($index, FILTER_VALIDATE_INT);

        if ($index === false || $field !== 'search' || !isset($this->tripCities[$index])) {
            return;
        }

        $search = trim((string) $value);
        $this->tripCities[$index]['address'] = null;
        $this->tripCities[$index]['place_id'] = null;
        $this->tripCities[$index]['latitude'] = null;
        $this->tripCities[$index]['longitude'] = null;
        $this->tripCities[$index]['suggestions'] = mb_strlen($search) >= 3
            ? $this->googleAutocomplete($search)
            : [];

        $this->clearError("tripCities.$index");
        $this->clearError('tripCities');
    }

    public function selectTripCity(int $index, string $placeId): void
    {
        if (!isset($this->tripCities[$index])) {
            return;
        }

        $place = $this->googlePlaceDetails($placeId);
        if (!$place) {
            $this->validationErrors["tripCities.$index"] = 'Selected city could not be loaded. Please try again.';
            $this->showValidation = true;
            return;
        }

        $this->tripCities[$index] = [
            'search' => $place['formatted_address'],
            'address' => $place['formatted_address'],
            'place_id' => $placeId,
            'latitude' => $place['latitude'],
            'longitude' => $place['longitude'],
            'suggestions' => [],
        ];

        $this->clearError("tripCities.$index");
        $this->clearError('tripCities');
    }

    private function emptyTripCity(): array
    {
        return [
            'search' => '',
            'address' => null,
            'place_id' => null,
            'latitude' => null,
            'longitude' => null,
            'suggestions' => [],
        ];
    }

    public function selectGooglePlace(string $field, string $placeId): void
    {
        $place = $this->googlePlaceDetails($placeId);

        if (!$place) {
            $this->oneWayMsg = 'Selected location could not be loaded. Please try again.';
            return;
        }

        $address = $place['formatted_address'];
        $city = $place['city'];
        $latitude = $place['latitude'];
        $longitude = $place['longitude'];

        switch ($field) {
            case 'one_way_from':
                $brand = $this->matchBrandCity($city, $address);
                $this->query = $brand?->name ?? $city ?? $address;
                $this->query_search = $address;
                $this->query_id = $brand?->id;
                $this->oneWayFromPlaceId = $placeId;
                $this->oneWayFromLatitude = $latitude;
                $this->oneWayFromLongitude = $longitude;
                $this->cities_from = null;
				// Force dropdown close
				$this->dispatch('$refresh');
                if (!$brand) {
                    $this->validationErrors['query'] = 'Service is not available from this city.';
                    $this->showValidation = true;
                } else {
                    $this->clearError('query');
                }
                break;

            case 'one_way_to':
                $brand = $this->matchBrandCity($city, $address);
                $this->query2 = $brand?->name ?? $city ?? $address;
                $this->query2_search = $address;
                $this->query2_id = $brand?->id;
                $this->oneWayToPlaceId = $placeId;
                $this->oneWayToLatitude = $latitude;
                $this->oneWayToLongitude = $longitude;
                $this->cities_to = null;
                if (!$brand) {
                    $this->validationErrors['query2'] = 'Service is not available for this destination city.';
                    $this->showValidation = true;
                } else {
                    $this->clearError('query2');
                }
                break;

            case 'round_from':
                $this->queryFrom = $address;
                $this->queryFrom_search = $address;
                $this->roundFromPlaceId = $placeId;
                $this->roundFromLatitude = $latitude;
                $this->roundFromLongitude = $longitude;
                $this->dataFrom = null;
                $this->clearError('queryFrom');
                break;

            case 'round_to':
                $this->queryTo = $address;
                $this->queryTo_search = $address;
                $this->roundToPlaceId = $placeId;
                $this->roundToLatitude = $latitude;
                $this->roundToLongitude = $longitude;
                $this->dataTo = null;
                $this->clearError('queryTo');
                break;

            case 'local':
                $brand = $this->matchBrandCity($city, $address, 'local');
                $this->query = $brand?->name ?? $city ?? $address;
                $this->queryLocal = $address;
                $this->query_id = $brand?->id;
                $this->localPlaceId = $placeId;
                $this->localLatitude = $latitude;
                $this->localLongitude = $longitude;
                $this->cities_from = null;
                if (!$brand) {
                    $this->validationErrors['query'] = 'Local service is not available in this city.';
                    $this->showValidation = true;
                } else {
                    $this->clearError('query');
                }
                break;

            case 'self_drive':
                $brand = $this->matchBrandCity($city, $address, 'self_drive');
                $this->query = $brand?->name ?? $city ?? $address;
                $this->querySelfDrive = $address;
                $this->query_id = $brand?->id;
                $this->selfDrivePlaceId = $placeId;
                $this->selfDriveLatitude = $latitude;
                $this->selfDriveLongitude = $longitude;
                $this->cities_from = null;
                if (!$brand) {
                    $this->validationErrors['query'] = 'Self Drive service is not available in this city.';
                    $this->showValidation = true;
                } else {
                    $this->clearError('query');
                }
                break;
        }
    }

    public function detectCurrentLocation(float $latitude, float $longitude): void
    {
        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            return;
        }

        $apiKey = $this->googleMapsApiKey();
        if (!$apiKey) {
            Log::warning('Current location detection skipped: Google Maps API key is missing.');
            return;
        }

        try {
            $response = (new Client())->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'query' => [
                    'latlng' => $latitude . ',' . $longitude,
                    'language' => 'en',
                    'key' => $apiKey,
                ],
                'timeout' => 10,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $result = data_get($data, 'results.0');

            if (($data['status'] ?? null) !== 'OK' || !$result) {
                return;
            }

            $address = trim((string) ($result['formatted_address'] ?? ''));
            $placeId = $result['place_id'] ?? null;
            $city = $this->extractCity($result['address_components'] ?? []);

            if ($address === '') {
                return;
            }

            // One Way pickup
            $oneWayBrand = $this->matchBrandCity($city, $address);
            if ($oneWayBrand) {
                $this->query = $oneWayBrand->name;
                $this->query_search = $address;
                $this->query_id = $oneWayBrand->id;
                $this->oneWayFromPlaceId = $placeId;
                $this->oneWayFromLatitude = $latitude;
                $this->oneWayFromLongitude = $longitude;
                $this->clearError('query');
            }

            // Round Trip pickup keeps the precise Google address.
            if (blank($this->queryFrom_search)) {
                $this->queryFrom = $address;
                $this->queryFrom_search = $address;
                $this->roundFromPlaceId = $placeId;
                $this->roundFromLatitude = $latitude;
                $this->roundFromLongitude = $longitude;
                $this->clearError('queryFrom');
            }

            // Local pickup is filled only where Local service is enabled.
            $localBrand = $this->matchBrandCity($city, $address, 'local');
            if ($localBrand) {
                if ($this->selected_tab === 'local' || blank($this->queryLocal)) {
                    $this->queryLocal = $address;
                }
                if ($this->selected_tab === 'local') {
                    $this->query = $localBrand->name;
                    $this->query_id = $localBrand->id;
                }
                $this->localPlaceId = $placeId;
                $this->localLatitude = $latitude;
                $this->localLongitude = $longitude;
            }

            // Self Drive pickup is filled only where Self Drive is enabled.
            $selfDriveBrand = $this->matchBrandCity($city, $address, 'self_drive');
            if ($selfDriveBrand) {
                if ($this->selected_tab === 'self_drive' || blank($this->querySelfDrive)) {
                    $this->querySelfDrive = $address;
                }
                if ($this->selected_tab === 'self_drive') {
                    $this->query = $selfDriveBrand->name;
                    $this->query_id = $selfDriveBrand->id;
                }
                $this->selfDrivePlaceId = $placeId;
                $this->selfDriveLatitude = $latitude;
                $this->selfDriveLongitude = $longitude;
            }

            $this->cities_from = null;
            $this->dataFrom = null;
        } catch (\Throwable $e) {
            Log::error('Google reverse geocoding failed', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function googleAutocomplete(?string $input): array
    {
        $input = trim((string) $input);
        if (mb_strlen($input) < 3) {
            return [];
        }

        $apiKey = $this->googleMapsApiKey();
        if (!$apiKey) {
            return [];
        }

        try {
            $response = (new Client())->get('https://maps.googleapis.com/maps/api/place/autocomplete/json', [
                'query' => [
                    'input' => $input,
                    'components' => 'country:in',
                    'language' => 'en',
                    'key' => $apiKey,
                ],
                'timeout' => 10,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return ($data['status'] ?? null) === 'OK' ? ($data['predictions'] ?? []) : [];
        } catch (\Throwable $e) {
            Log::error('Google Places autocomplete failed', ['message' => $e->getMessage()]);
            return [];
        }
    }

    private function googlePlaceDetails(string $placeId): ?array
    {
        $apiKey = $this->googleMapsApiKey();
        if (!$apiKey || !$placeId) {
            return null;
        }

        try {
            $response = (new Client())->get('https://maps.googleapis.com/maps/api/place/details/json', [
                'query' => [
                    'place_id' => $placeId,
                    'fields' => 'place_id,formatted_address,address_components,geometry',
                    'language' => 'en',
                    'key' => $apiKey,
                ],
                'timeout' => 10,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $result = $data['result'] ?? null;
            if (($data['status'] ?? null) !== 'OK' || !$result) {
                return null;
            }

            return [
                'formatted_address' => $result['formatted_address'] ?? '',
                'city' => $this->extractCity($result['address_components'] ?? []),
                'latitude' => data_get($result, 'geometry.location.lat'),
                'longitude' => data_get($result, 'geometry.location.lng'),
            ];
        } catch (\Throwable $e) {
            Log::error('Google Place details failed', ['message' => $e->getMessage()]);
            return null;
        }
    }

    private function extractCity(array $components): ?string
    {
        $priority = ['locality', 'postal_town', 'administrative_area_level_3', 'administrative_area_level_2'];
        foreach ($priority as $type) {
            foreach ($components as $component) {
                if (in_array($type, $component['types'] ?? [], true)) {
                    return trim($component['long_name'] ?? '') ?: null;
                }
            }
        }
        return null;
    }

    private function matchBrandCity(?string $city, string $address, ?string $service = null): ?Brand
    {
        $query = Brand::query()->where('is_active', 1);
        if ($service === 'local') {
            $query->where('is_local', 1);
        } elseif ($service === 'self_drive') {
            $query->where('is_selfdrive', 1);
        }

        $brands = $query->get();
        $cityNormalized = $this->normalizeLocationName($city);
        $addressNormalized = $this->normalizeLocationName($address);

        return $brands->first(function (Brand $brand) use ($cityNormalized, $addressNormalized) {
            $brandName = $this->normalizeLocationName($brand->name);
            return $brandName !== '' && (
                $brandName === $cityNormalized ||
                Str::contains($addressNormalized, $brandName) ||
                ($cityNormalized !== '' && Str::contains($brandName, $cityNormalized))
            );
        });
    }

    private function normalizeLocationName(?string $value): string
    {
        return Str::of((string) $value)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->toString();
    }

    private function googleMapsApiKey(): ?string
    {
        return config('services.google_maps.key') ?: env('GOOGLE_MAPS_API_KEY');
    }

    private function clearSelectedPlace(string $field): void
    {
        switch ($field) {
            case 'one_way_from':
                $this->query = null; $this->query_id = null; $this->oneWayFromPlaceId = null;
                break;
            case 'one_way_to':
                $this->query2 = null; $this->query2_id = null; $this->oneWayToPlaceId = null;
                break;
            case 'round_from':
                $this->queryFrom = null; $this->roundFromPlaceId = null;
                break;
            case 'round_to':
                $this->queryTo = null; $this->roundToPlaceId = null;
                break;
            case 'local':
                $this->query = null; $this->query_id = null; $this->localPlaceId = null;
                break;
            case 'self_drive':
                $this->query = null; $this->query_id = null; $this->selfDrivePlaceId = null;
                break;
        }
    }

    // Backward-compatible methods retained for any old Blade/cache references.
    public function update1($name, $id) { $this->query = $name; $this->query_search = $name; $this->query_id = $id; $this->cities_from = null; }
    public function update2($name, $id) { $this->query2 = $name; $this->query2_search = $name; $this->query2_id = $id; $this->cities_to = null; }
    public function update3($name, $id) { $this->query = $name; $this->queryLocal = $name; $this->query_id = $id; $this->cities_from = null; }
    public function update4($name, $id) { $this->query = $name; $this->querySelfDrive = $name; $this->query_id = $id; $this->cities_from = null; }
    public function updateCityFrom($name) { $this->queryFrom = $name; $this->queryFrom_search = $name; $this->dataFrom = null; }
    public function updateCityTo($name) { $this->queryTo = $name; $this->queryTo_search = $name; $this->dataTo = null; }

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

        if (!empty($this->query) && empty($this->query_id)) {
            $this->validationErrors['query'] = 'Please select a supported From City from Google suggestions.';
            $isValid = false;
        }
        if (!empty($this->query2) && empty($this->query2_id)) {
            $this->validationErrors['query2'] = 'Please select a supported To City from Google suggestions.';
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

        if (count($this->tripCities) > $this->maxTripCities) {
            $this->validationErrors['tripCities'] = 'You can add up to 20 destination cities.';
            $isValid = false;
        }

        $seenPlaceIds = array_filter([$this->roundFromPlaceId, $this->roundToPlaceId]);
        foreach ($this->tripCities as $index => $city) {
            $search = trim((string) ($city['search'] ?? ''));
            $placeId = $city['place_id'] ?? null;

            if ($search === '' || blank($placeId)) {
                $this->validationErrors["tripCities.$index"] = 'Please select this city from Google suggestions.';
                $isValid = false;
                continue;
            }

            if (in_array($placeId, $seenPlaceIds, true)) {
                $this->validationErrors["tripCities.$index"] = 'Duplicate city is not allowed in the route.';
                $isValid = false;
                continue;
            }

            $seenPlaceIds[] = $placeId;
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

        if (!empty($this->query) && empty($this->query_id)) {
            $this->validationErrors['query'] = 'Please select a supported city from Google suggestions.';
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

        if (empty($this->selfDrivePlaceId) || $this->selfDriveLatitude === null || $this->selfDriveLongitude === null) {
            $this->validationErrors['query'] = 'Please select a location from Google suggestions.';
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
    // SEO is loaded in fallback-only mode.
    // Existing homepage SEO remains unchanged when no matching CMS page exists.
    $seoPage = SeoPage::query()
        ->whereIn('slug', ['home', 'homepage'])
        ->first();

    $brands = Brand::where('is_active', 1)
        ->where('is_populer', 1)
        ->get();

    $product = Vehicle::query()
        ->with(['frontMedia', 'transporter'])
        ->availableForRental()
        ->selfDrive()
        ->cars()
        ->where('daily_price', '>', 0)
        ->latest('id')
        ->take(12)
        ->get();

    // Legacy banner fallback
    $carousel = Banners::where('ride_type', $this->bannerTab)->get();

    // CMS-driven Smart Hero banners
    $smartHeroBanners = app(SmartBannerService::class)
        ->getSection('hero_banners');

    $tour = Banners::where('ride_type', 'tour')->get();
    $reviews = Reviews::all();
    $categories = Category::where('in_return', 1)->get();

    return view('livewire.homepage', [
        'brands' => $brands,
        'categories' => $categories,
        'reviews' => $reviews,
        'carousel' => $carousel,
        'smartHeroBanners' => $smartHeroBanners,
        'tours' => $tour,
        'products' => $product,
        'seoPage' => $seoPage,
    ])->title(
        filled($seoPage?->meta_title)
            ? $seoPage->meta_title
            : 'Home Page - Duracabs'
    );
}
}