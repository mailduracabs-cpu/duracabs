<?php

namespace App\Livewire;

use App\Services\FareOtpService;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Page as SeoPage;
use App\Models\RideInquiry;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use DateTime;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Vehicle;
use App\Models\SelfDriveBooking;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;



class RidesPage extends Component
{
    use WithPagination;
    use LivewireAlert;

   
    #[Url(history: true)]
    public $selected_categories = [];
    
    #[Url(history: true)]
    public $selected_brands = [];

    #[Url(history: true)]
    public $cityFrom ;
    

    #[Url(history: true)]
    public $cityTo ;

    #[Url(history: true)]
    public $nameTo ;

    #[Url(history: true)]
    public $tab = false ;

    #[Url(history: true)]
    public $days ;

    #[Url(history: true)]
    public $nameFrom ;

    #[Url(history: true)]
    public $date ;

 

    #[Url(history: true)]
    public $dateto ;

    #[Url(history: true)]
    public $plan ;

    #[Url(history: true)]
    public $cars ;

   

    public $query;

    #[Url(history: true)]
    public $price_range = 30000;

   

    #[Url]
    public $sort = 'price';

    #[Url(history: true)]
    public $km ;

    #[Url(history: true)]
    public $kmValue ;

    #[Url(history: true)]
    public $time ;

    #[Url(history: true)]
    public $endTime ;

    /**
     * Homepage/banner se select ki gayi specific Self Drive vehicle ID.
     */
    #[Url(history: true)]
    public ?int $vehicle_id = null;

    /**
     * Homepage/banner request can ask the page to check the selected car immediately.
     */
    #[Url(history: true)]
    public bool $check_availability = false;

    public bool $selectedVehicleBooked = false;
    public bool $selfDrivePeriodInvalid = false;
    public ?string $selfDriveAvailabilityMessage = null;

    #[Url(history: true)]
    public $timeValue ;

    public $query2 = null;
    public $brandsData = [];

    // Edit Query Popup Properties
    public $showEditModal = false;
    public $edit_ride_type = 'one_way';
    public $isLoading = false;
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
    public $edit_days = '';
    public $edit_km = '';
    public $edit_kmValue = '';
    public $edit_timeValue = '';
    
    // Auto-complete data for popup
    public $edit_cities_from = [];
    public $edit_cities_to = [];
    public $edit_dataFrom = [];
    public $edit_dataTo = [];
    
    // City IDs for edit modal
    public $edit_cityFrom_id = null;
    public $edit_cityTo_id = null;
    
    // Fare unlock / OTP lead gate
    public bool $showOtpModal = false;
    public bool $fareUnlocked = false;
    public string $otpStage = 'mobile';
    public string $mobileNumber = '';
    public string $otpCode = '';
    public ?string $otpError = null;
    public int $otpResendSeconds = 0;
    public ?string $pendingBookingAction = null;
    public mixed $pendingBookingPayload = null;

    public string $apiKey = '';

    public function mount(): void
    {
        $this->apiKey = (string) config('services.google_maps.key', env('GOOGLE_MAPS_API_KEY', ''));
        $this->fareUnlocked = (bool) session('rides_fare_unlocked', false) || Auth::check();
        $this->mobileNumber = (string) session('rides_verified_mobile', '');

        if ($this->tab === 'self_drive') {
            $this->refreshSelfDriveAvailabilityState();
        }
    }

    

    

    // Booking draft methods. These keep the existing Blade method names while
    // storing one checkout payload in the session instead of using cart cookies.
    public function addToCart(mixed $payload)
    {
        return $this->storeBookingDraft('product', $payload, __FUNCTION__);
    }

    public function addToCartOneWay(mixed $payload)
    {
        return $this->storeBookingDraft('one_way', $payload, __FUNCTION__);
    }

    public function addToCartLocal(mixed $payload)
    {
        return $this->storeBookingDraft('local', $payload, __FUNCTION__);
    }

    public function addToCartReturn(mixed $payload)
    {
        return $this->storeBookingDraft('return', $payload, __FUNCTION__);
    }

    public function addToCartSelfDrive(mixed $payload)
    {
        return $this->storeBookingDraft('self_drive', $payload, __FUNCTION__);
    }

    private function storeBookingDraft(string $bookingType, mixed $selectionId, string $pendingAction)
    {
        if (! $this->fareUnlocked) {
            $this->openFareGate($pendingAction, $selectionId);

            return null;
        }

        try {
            $quantity = max(1, (int) ($this->cars ?: 1));

            if ($bookingType === 'self_drive') {
                $vehicleId = is_numeric($selectionId) ? (int) $selectionId : 0;

                if ($vehicleId <= 0) {
                    throw new \InvalidArgumentException('A valid self-drive vehicle is required.');
                }

                [$pickupAt, $dropAt] = $this->validatedSelfDrivePeriod();

                $vehicle = DB::transaction(function () use ($vehicleId, $pickupAt, $dropAt) {
                    $lockedVehicle = Vehicle::query()
                        ->with('transporter')
                        ->availableForRental()
                        ->selfDrive()
                        ->lockForUpdate()
                        ->findOrFail($vehicleId);

                    if ($this->selfDriveBookingOverlapQuery($vehicleId, $pickupAt, $dropAt)->exists()) {
                        throw new \RuntimeException('SELF_DRIVE_VEHICLE_ALREADY_BOOKED');
                    }

                    return $lockedVehicle;
                }, 3);

                $unitPrice = max(0, (float) ($vehicle->hourly_price ?? 0));
                $hours = $this->calculateSelfDriveHours();
                $minimumBookingHours = max(1, (int) ($vehicle->minimum_booking_hours ?? 1));
                $billableHours = max($hours, $minimumBookingHours);
                $subtotal = $unitPrice * $billableHours * $quantity;

                if ($unitPrice <= 0) {
                    throw new \RuntimeException('SELF_DRIVE_PRICE_UNAVAILABLE');
                }

                session()->put('booking_draft', [
                    'version' => 1,
                    'type' => 'self_drive',
                    'source' => 'rides_page',
                    'selection_id' => is_numeric($selectionId) ? (int) $selectionId : $selectionId,
                    'product_id' => null,
                    'vehicle_id' => (int) $vehicle->getKey(),
                    'customer' => [
                        'user_id' => Auth::id(),
                        'mobile' => $this->mobileNumber ?: session('rides_verified_mobile'),
                    ],
                    'trip' => [
                        'pickup_city_id' => is_numeric($this->cityFrom) ? (int) $this->cityFrom : null,
                        'pickup_name' => (string) $this->nameTo,
                        'drop_city_id' => null,
                        'drop_name' => null,
                        'date' => (string) $this->date,
                        'time' => (string) $this->time,
                        'end_date' => (string) $this->dateto,
                        'end_time' => (string) $this->endTime,
                        'hours' => $hours,
                        'days' => $this->days,
                        'plan' => null,
                        'quantity' => $quantity,
                        'pickup_at' => $pickupAt->toDateTimeString(),
                        'drop_at' => $dropAt->toDateTimeString(),
                    ],
                    'fare' => [
                        'unit_price' => $unitPrice,
                        'price_unit' => 'hour',
                        'hours' => $hours,
                        'billable_hours' => $billableHours,
                        'minimum_booking_hours' => $minimumBookingHours,
                        'quantity' => $quantity,
                        'subtotal' => $subtotal,
                        'total' => $subtotal,
                    ],
                    'vehicle' => [
                        'id' => (int) $vehicle->getKey(),
                        'name' => (string) ($vehicle->name ?? $vehicle->vehicle_name ?? $vehicle->model ?? 'Self Drive Car'),
                        'number' => $vehicle->vehicle_number ?? null,
                        'transporter_id' => $vehicle->transporter_id ?? null,
                    ],
                    'created_at' => now()->toIso8601String(),
                ]);
            } else {
                $product = Product::query()
                    ->with(['brand', 'prices.category'])
                    ->whereKey($selectionId)
                    ->where('is_active', 1)
                    ->firstOrFail();

                $dropCity = ! empty($product->booking_to)
                    ? Brand::query()->find($product->booking_to)
                    : null;
                $unitPrice = (float) ($product->price ?? $product->prices->min('price') ?? 0);
                $subtotal = $unitPrice * $quantity;

                session()->put('booking_draft', [
                    'version' => 1,
                    'type' => $bookingType,
                    'source' => 'rides_page',
                    'selection_id' => is_numeric($selectionId) ? (int) $selectionId : $selectionId,
                    'product_id' => (int) $product->getKey(),
                    'vehicle_id' => null,
                    'customer' => [
                        'user_id' => Auth::id(),
                        'mobile' => $this->mobileNumber ?: session('rides_verified_mobile'),
                    ],
                    'trip' => [
                        'pickup_city_id' => is_numeric($this->cityFrom) ? (int) $this->cityFrom : ($product->brand_id ? (int) $product->brand_id : null),
                        'pickup_name' => (string) ($this->nameTo ?: $product->brand?->name),
                        'drop_city_id' => is_numeric($this->cityTo) ? (int) $this->cityTo : ($product->booking_to ? (int) $product->booking_to : null),
                        'drop_name' => (string) ($this->nameFrom ?: $dropCity?->name),
                        'date' => (string) $this->date,
                        'time' => (string) $this->time,
                        'end_date' => $bookingType === 'return' ? (string) $this->dateto : null,
                        'end_time' => $bookingType === 'return' ? (string) $this->endTime : null,
                        'days' => $bookingType === 'return' ? $this->days : null,
                        'km' => $bookingType === 'return' ? $this->km : null,
                        'km_value' => $bookingType === 'return' ? $this->kmValue : null,
                        'plan' => $bookingType === 'local' ? (string) $this->plan : null,
                        'quantity' => $quantity,
                    ],
                    'fare' => [
                        'unit_price' => $unitPrice,
                        'quantity' => $quantity,
                        'subtotal' => $subtotal,
                        'total' => $subtotal,
                    ],
                    'product' => [
                        'id' => (int) $product->getKey(),
                        'name' => (string) ($product->name ?? ''),
                        'slug' => (string) ($product->slug ?? ''),
                        'ride_type' => (string) ($product->ride_type ?? $bookingType),
                    ],
                    'created_at' => now()->toIso8601String(),
                ]);
            }

            $this->alert('success', 'Booking details saved. Checkout open ho raha hai.', [
                'position' => 'center-end',
                'timer' => 2000,
                'toast' => true,
            ]);

            return redirect()->route('checkout');
        } catch (\Throwable $exception) {
            if ($exception->getMessage() === 'SELF_DRIVE_VEHICLE_ALREADY_BOOKED') {
                $this->selectedVehicleBooked = true;
                $this->selfDriveAvailabilityMessage = 'This car is already booked for the selected date and time.';
                $this->alert('warning', $this->selfDriveAvailabilityMessage, [
                    'position' => 'center-end',
                    'timer' => 5000,
                    'toast' => true,
                ]);

                return null;
            }

            if ($exception->getMessage() === 'SELF_DRIVE_PRICE_UNAVAILABLE') {
                $this->alert('error', 'This vehicle price is currently unavailable.', [
                    'position' => 'center-end',
                    'timer' => 4000,
                    'toast' => true,
                ]);

                return null;
            }

            Log::error('Rides booking draft creation failed.', [
                'booking_type' => $bookingType,
                'selection_id' => $selectionId,
                'message' => $exception->getMessage(),
            ]);

            $this->alert('error', 'Booking start nahi ho saki. Page refresh karke dobara try karein.', [
                'position' => 'center-end',
                'timer' => 4000,
                'toast' => true,
            ]);

            return null;
        }
    }

    public function viewOtherSelfDriveCars(): void
    {
        $this->vehicle_id = null;
        $this->selectedVehicleBooked = false;
        $this->selfDriveAvailabilityMessage = null;
        $this->resetPage();
    }

    private function refreshSelfDriveAvailabilityState(): void
    {
        $this->selfDrivePeriodInvalid = false;
        $this->selectedVehicleBooked = false;
        $this->selfDriveAvailabilityMessage = null;

        if ($this->tab !== 'self_drive') {
            return;
        }

        try {
            [$pickupAt, $dropAt] = $this->validatedSelfDrivePeriod();
        } catch (\Throwable $exception) {
            $this->selfDrivePeriodInvalid = true;
            $this->selfDriveAvailabilityMessage = $exception->getMessage();

            return;
        }

        if ($this->vehicle_id) {
            $this->selectedVehicleBooked = $this->selfDriveBookingOverlapQuery(
                (int) $this->vehicle_id,
                $pickupAt,
                $dropAt
            )->exists();

            if ($this->selectedVehicleBooked) {
                $this->selfDriveAvailabilityMessage = 'This car is already booked for the selected date and time.';
            }
        }
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function validatedSelfDrivePeriod(): array
    {
        if (blank($this->date) || blank($this->time) || blank($this->dateto) || blank($this->endTime)) {
            throw new \InvalidArgumentException('Please select pickup and drop date and time.');
        }

        try {
            $pickupAt = Carbon::createFromFormat(
                '!Y-m-d H:i',
                trim((string) $this->date . ' ' . (string) $this->time)
            );
            $dropAt = Carbon::createFromFormat(
                '!Y-m-d H:i',
                trim((string) $this->dateto . ' ' . (string) $this->endTime)
            );
        } catch (\Throwable $exception) {
            throw new \InvalidArgumentException('Please select a valid pickup and drop date and time.');
        }

        if (! $dropAt->greaterThan($pickupAt)) {
            throw new \InvalidArgumentException('Drop date and time must be later than pickup date and time.');
        }

        return [$pickupAt, $dropAt];
    }

    private function selfDriveBookingOverlapQuery(int $vehicleId, Carbon $pickupAt, Carbon $dropAt): Builder
    {
        return SelfDriveBooking::query()
           ->where('vehicle_id', $vehicleId)
            ->whereIn('status', ['pending', 'confirmed', 'running'])
            ->where('start_datetime', '<', $dropAt->toDateTimeString())
            ->where('end_datetime', '>', $pickupAt->toDateTimeString());
    }

    private function applySelfDriveAvailabilityFilter(Builder $query, Carbon $pickupAt, Carbon $dropAt): void
    {
        $query->whereNotIn('id', SelfDriveBooking::query()
            ->select('vehicle_id')
            ->whereIn('status', ['pending', 'confirmed', 'running'])
            ->where('start_datetime', '<', $dropAt->toDateTimeString())
            ->where('end_datetime', '>', $pickupAt->toDateTimeString()));
    }

    private function calculateSelfDriveHours(): int
    {
        $start = strtotime(trim((string) $this->date . ' ' . (string) $this->time));
        $end = strtotime(trim((string) $this->dateto . ' ' . (string) $this->endTime));

        if ($start !== false && $end !== false && $end > $start) {
            return max(1, (int) ceil(($end - $start) / 3600));
        }

        if (is_numeric($this->timeValue) && (float) $this->timeValue > 0) {
            return max(1, (int) ceil((float) $this->timeValue));
        }

        if (is_numeric($this->days) && (float) $this->days > 0) {
            return max(24, (int) ceil((float) $this->days * 24));
        }

        return 24;
    }


    public function updatedQuery(){
        $this-> query = Product::query()->where('is_active',1);
    }

    public function updatedQuery2($query2): void
    {
        $this->brandsData = $this->searchBrands((string) $query2);
    }


    public function openFareGate(?string $action = null, mixed $payload = null): void
    {
        if ($this->fareUnlocked) {
            return;
        }

        $this->pendingBookingAction = $action;
        $this->pendingBookingPayload = $payload;
        $this->showOtpModal = true;
        $this->otpStage = 'mobile';
        $this->otpCode = '';
        $this->otpError = null;
    }

    public function closeOtpModal(): void
    {
        $this->showOtpModal = false;
        $this->otpError = null;
        $this->otpCode = '';
    }

    public function sendFareOtp(): void
    {
        $this->resetErrorBag();
        $this->otpError = null;

        $result = app(FareOtpService::class)->send(
            (string) $this->mobileNumber,
            'rides',
            request()->ip()
        );

        if (! ($result['success'] ?? false)) {
            $this->otpError = (string) ($result['message'] ?? 'OTP send nahi ho saka.');
            return;
        }

        $this->mobileNumber = (string) $result['mobile'];
        $this->otpStage = 'otp';
        $this->otpCode = '';
        $this->dispatch('rides-otp-sent', seconds: 30);
    }

    public function resendFareOtp(): void
    {
        $this->sendFareOtp();
    }

    public function verifyFareOtp(): void
    {
        $this->resetErrorBag();
        $this->otpError = null;

        $result = app(FareOtpService::class)->verify(
            (string) $this->mobileNumber,
            (string) $this->otpCode,
            'rides'
        );

        if (! ($result['success'] ?? false)) {
            $this->otpError = (string) ($result['message'] ?? 'OTP verify nahi ho saka.');
            return;
        }

        $mobile = (string) $result['mobile'];
        $user = $result['user'] ?? null;

        session([
            'rides_fare_unlocked' => true,
            'rides_verified_mobile' => $mobile,
        ]);

        $this->fareUnlocked = true;
        $this->showOtpModal = false;
        $this->otpStage = 'mobile';
        $this->otpCode = '';

        $this->createOrUpdateRideInquiry($mobile, $user?->getKey());
        $this->alert('success', 'Mobile verified. Exact fares are now unlocked.', [
            'position' => 'center-end',
            'timer' => 3000,
            'toast' => true,
        ]);

        $action = $this->pendingBookingAction;
        $payload = $this->pendingBookingPayload;
        $this->pendingBookingAction = null;
        $this->pendingBookingPayload = null;

        if ($action && method_exists($this, $action)) {
            $this->{$action}($payload);
        }
    }

    private function createOrUpdateRideInquiry(string $mobile, mixed $userId = null): void
    {
        try {
            $landingUrl = request()->fullUrl();
            $source = request()->query('utm_source')
                ? 'utm:' . request()->query('utm_source')
                : (request()->query('gclid') ? 'google_ads' : 'seo_route_page');

            $lookup = RideInquiry::query()
                ->where('mobile', $mobile)
                ->where('pickup_name', (string) $this->nameTo)
                ->where('drop_name', (string) $this->nameFrom)
                ->where('trip_type', (string) ($this->tab ?: 'one_way'))
                ->whereDate('travel_date', $this->date ?: now()->toDateString())
                ->where('created_at', '>=', now()->subHours(24))
                ->latest()
                ->first();

            $payload = [
                'user_id' => $userId,
                'mobile' => $mobile,
                'pickup_city_id' => is_numeric($this->cityFrom) ? $this->cityFrom : null,
                'drop_city_id' => is_numeric($this->cityTo) ? $this->cityTo : null,
                'pickup_name' => (string) $this->nameTo,
                'drop_name' => (string) $this->nameFrom,
                'trip_type' => (string) ($this->tab ?: 'one_way'),
                'travel_date' => $this->date ?: now()->toDateString(),
                'travel_time' => $this->time ?: null,
                'return_date' => $this->dateto ?: null,
                'source' => $source,
                'landing_url' => $landingUrl,
                'utm_source' => request()->query('utm_source'),
                'utm_medium' => request()->query('utm_medium'),
                'utm_campaign' => request()->query('utm_campaign'),
                'gclid' => request()->query('gclid'),
                'fbclid' => request()->query('fbclid'),
                'status' => $lookup?->status ?: 'new',
                'last_activity_at' => now(),
            ];

            if ($lookup) {
                $lookup->update($payload);
            } else {
                $payload['inquiry_no'] = 'RI-' . now()->format('ymd') . '-' . strtoupper(Str::random(6));
                RideInquiry::create($payload);
            }
        } catch (\Throwable $e) {
            Log::error('Ride inquiry creation failed', ['error' => $e->getMessage()]);
        }
    }

    public function priceCalculate(){
       $day =  $this->days;
       $km =  $this->kmValue / 1000;

       return [];
    }
 // Edit Query Popup Methods
    public function showEditQueryModal() {
        $this->showEditModal = true;
        $this->edit_ride_type = $this->tab ?: 'one_way';
        
        // Pre-populate current values
        $this->edit_date = $this->date;
        $this->edit_dateto = $this->dateto;
        $this->edit_time = $this->time;
        $this->edit_endTime = $this->endTime;
        $this->edit_plan = $this->plan ?: '4 Hour / 40 Km';
        $this->edit_cars = $this->cars ?: 1;
        $this->edit_days = $this->days;
        $this->edit_km = $this->km;
        $this->edit_kmValue = $this->kmValue;
        $this->edit_timeValue = $this->timeValue;
        
        if ($this->edit_ride_type == 'one_way') {
            $this->edit_query_search = $this->nameTo;
            $this->edit_query2_search = $this->nameFrom;
            $this->edit_cityFrom_id = $this->cityFrom;
            $this->edit_cityTo_id = $this->cityTo;
        } elseif ($this->edit_ride_type == 'return') {
            $this->edit_queryFrom_search = $this->nameTo;
            $this->edit_queryTo_search = $this->nameFrom;
        } elseif ($this->edit_ride_type == 'local') {
            $this->edit_queryLocal = $this->nameTo;
            $this->edit_cityFrom_id = $this->cityFrom;
        } elseif ($this->edit_ride_type == 'self_drive') {
            $this->edit_querySelfDrive = $this->nameTo;
            $this->edit_cityFrom_id = $this->cityFrom;
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
     * Sync search values when changing tabs in edit modal
     */
    private function syncEditSearchValuesOnTabChange($newTab) {
        $currentTab = $this->edit_ride_type;
        
        // Get current search values based on current tab
        $fromValue = $this->getCurrentEditFromValue($currentTab);
        $toValue = $this->getCurrentEditToValue($currentTab);
        $fromCityId = $this->getCurrentEditFromCityId($currentTab);
        $toCityId = $this->getCurrentEditToCityId($currentTab);
        
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
        if ($fromCityId && $this->isValueCompatibleWithTab($fromValue, $newTab)) {
            $this->setEditFromCityIdForTab($newTab, $fromCityId);
        }
        if ($toCityId) {
            $this->setEditToCityIdForTab($newTab, $toCityId);
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
     * Get current edit "from" city ID based on tab
     */
    private function getCurrentEditFromCityId($tab) {
        switch ($tab) {
            case 'one_way':
                return $this->edit_cityFrom_id;
            case 'local':
                return $this->edit_cityFrom_id;
            case 'self_drive':
                return $this->edit_cityFrom_id;
            default:
                return null;
        }
    }

    /**
     * Get current edit "to" city ID based on tab
     */
    private function getCurrentEditToCityId($tab) {
        switch ($tab) {
            case 'one_way':
                return $this->edit_cityTo_id;
            default:
                return null;
        }
    }

    /**
     * Set edit "from" city ID for specific tab
     */
    private function setEditFromCityIdForTab($tab, $cityId) {
        switch ($tab) {
            case 'one_way':
                $this->edit_cityFrom_id = $cityId;
                break;
            case 'local':
                $this->edit_cityFrom_id = $cityId;
                break;
            case 'self_drive':
                $this->edit_cityFrom_id = $cityId;
                break;
        }
    }

    /**
     * Set edit "to" city ID for specific tab
     */
    private function setEditToCityIdForTab($tab, $cityId) {
        switch ($tab) {
            case 'one_way':
                $this->edit_cityTo_id = $cityId;
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
    public function updatedEditQuerySearch($query): void
    {
        $this->edit_cities_from = $this->searchBrands((string) $query);
    }

    public function updatedEditQuery2Search($query2): void
    {
        $this->edit_cities_to = $this->searchBrands((string) $query2);
    }

    // Local search handler for popup
    public function updatedEditQueryLocal($queryLocal): void
    {
        $this->edit_cities_from = $this->searchBrands((string) $queryLocal, 'is_local');
    }

    // Self Drive search handler for popup
    public function updatedEditQuerySelfDrive($querySelfDrive): void
    {
        $this->edit_cities_from = $this->searchBrands((string) $querySelfDrive, 'is_selfdrive');
    }

    // Return trip Google Places API handlers for popup
    public function updatedEditQueryFromSearch($queryFrom): void
    {
        $this->edit_dataFrom = $this->googlePlaceSuggestions((string) $queryFrom);
    }

    public function updatedEditQueryToSearch($queryTo): void
    {
        $this->edit_dataTo = $this->googlePlaceSuggestions((string) $queryTo);
    }

    /**
     * Small, cached autocomplete payload for Livewire dropdowns.
     */
    private function searchBrands(string $term, ?string $serviceColumn = null): array
    {
        $term = trim($term);

        if (mb_strlen($term) < 3) {
            return [];
        }

        $normalized = mb_strtolower($term);
        $cacheKey = 'rides:brand-search:' . ($serviceColumn ?: 'all') . ':' . md5($normalized);

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($term, $serviceColumn): array {
            $query = Brand::query()
                ->select(['id', 'name', 'slug'])
                ->where('is_active', 1)
                ->where('name', 'like', $term . '%');

            if ($serviceColumn) {
                $query->where($serviceColumn, 1);
            }

            $results = $query->orderBy('name')->limit(10)->get();

            // Keep contains matching as a fallback without slowing the common prefix case.
            if ($results->isEmpty()) {
                $fallback = Brand::query()
                    ->select(['id', 'name', 'slug'])
                    ->where('is_active', 1)
                    ->where('name', 'like', '%' . $term . '%');

                if ($serviceColumn) {
                    $fallback->where($serviceColumn, 1);
                }

                $results = $fallback->orderBy('name')->limit(10)->get();
            }

            return $results->toArray();
        });
    }

    /**
     * Cache Google autocomplete briefly to avoid repeated paid API calls while typing.
     */
    private function googlePlaceSuggestions(string $term): array
    {
        $term = trim($term);

        if (blank($this->apiKey) || mb_strlen($term) < 3) {
            return [];
        }

        $cacheKey = 'rides:google-places:' . md5(mb_strtolower($term));

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($term): array {
            try {
                $client = new Client([
                    'connect_timeout' => 2.5,
                    'timeout' => 5.0,
                    'http_errors' => false,
                ]);

                $response = $client->get(
                    'https://maps.googleapis.com/maps/api/place/queryautocomplete/json',
                    ['query' => ['input' => $term, 'key' => $this->apiKey]]
                );

                if ($response->getStatusCode() !== 200) {
                    Log::warning('Google Places autocomplete returned a non-200 response.', [
                        'status' => $response->getStatusCode(),
                    ]);
                    return [];
                }

                $data = json_decode((string) $response->getBody(), true);

                return array_slice($data['predictions'] ?? [], 0, 8);
            } catch (\Throwable $exception) {
                Log::warning('Google Places autocomplete failed.', [
                    'message' => $exception->getMessage(),
                ]);
                return [];
            }
        });
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
        $this->edit_cityFrom_id = $id;
        $this->edit_cities_from = [];
    }

    public function editUpdate2($name, $id) {
        $this->edit_query2_search = $name;
        $this->edit_cityTo_id = $id;
        $this->edit_cities_to = [];
    }

    public function editUpdate3($name, $id) {
        $this->edit_queryLocal = $name;
        $this->edit_cityFrom_id = $id;
        $this->edit_cities_from = [];
    }

    public function editUpdate4($name, $id) {
        $this->edit_querySelfDrive = $name;
        $this->edit_cityFrom_id = $id;
        $this->edit_cities_from = [];
    }

    public function updateQuery() {
        // Set loading state
        $this->isLoading = true;
        
        try {
            // Validation based on ride type
            $this->validate($this->getEditValidationRules());

            // Build the redirect URL with updated parameters
            $params = $this->buildEditRedirectParams();
            
            $this->showEditModal = false;
            $this->isLoading = false;
            
            return redirect()->to(route('rides') . '?' . http_build_query($params));
        } catch (\Exception $e) {
            $this->isLoading = false;
            throw $e;
        }
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
                $params['cityFrom'] = $this->edit_cityFrom_id;
                $params['cityTo'] = $this->edit_cityTo_id;
                $params['nameTo'] = $this->edit_query_search;
                $params['nameFrom'] = $this->edit_query2_search;
                break;
            case 'return':
                $params['nameTo'] = $this->edit_queryFrom_search;
                $params['cityFrom'] = $this->edit_queryTo_search;
                $params['dateto'] = $this->edit_dateto;
                $params['km'] = $this->edit_km;
                $params['kmValue'] = $this->edit_kmValue;
                $params['timeValue'] = $this->edit_timeValue;
                $params['days'] = $this->edit_days;
                break;
            case 'local':
                $params['cityFrom'] = $this->edit_cityFrom_id;
                $params['nameTo'] = $this->edit_queryLocal;
                $params['plan'] = $this->edit_plan;
                $params['cars'] = $this->edit_cars;
                break;
            case 'self_drive':
                $params['cityFrom'] = $this->edit_cityFrom_id;
                $params['nameTo'] = $this->edit_querySelfDrive;
                $params['dateto'] = $this->edit_dateto;
                $params['endTime'] = $this->edit_endTime;
                $params['days'] = $this->edit_days;

                if ($this->vehicle_id) {
                    $params['vehicle_id'] = $this->vehicle_id;
                    $params['check_availability'] = 1;
                }
                break;
        }

        return $params;
    }


    public function updatedSelectedCategories(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedBrands(): void
    {
        $this->resetPage();
    }

    public function updatedPriceRange(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        if (! in_array($this->sort, ['price', 'latest'], true)) {
            $this->sort = 'price';
        }

        $this->resetPage();
    }

    private function seoData(): array
    {
        $from = trim((string) $this->nameTo);
        $to = trim((string) $this->nameFrom);
        $tripType = (string) ($this->tab ?: 'one_way');

        $fallbackTitle = 'Duracabs: Trusted Online Cab Booking Services in India';
        $fallbackDescription = 'Book reliable one-way, round-trip, local and self-drive cab services with Duracabs. Compare available vehicles, view fare details and book online.';

        if ($tripType === 'local' && $from !== '') {
            $fallbackTitle = "{$from} Local Taxi Service | Duracabs";
            $fallbackDescription = "Book a local taxi in {$from} with Duracabs. Compare available cars, transparent fares and convenient online booking.";
        } elseif ($tripType === 'self_drive' && $from !== '') {
            $fallbackTitle = "Self Drive Cars in {$from} | Duracabs";
            $fallbackDescription = "Book self-drive cars in {$from} with Duracabs. Compare available vehicles, rental prices and trip details online.";
        } elseif ($tripType === 'return' && $from !== '' && $to !== '') {
            $fallbackTitle = "{$from} to {$to} Round Trip Taxi | Duracabs";
            $fallbackDescription = "Book a {$from} to {$to} round-trip taxi with Duracabs. Compare vehicles, check transparent fares and reserve your cab online.";
        } elseif ($from !== '' && $to !== '') {
            $fallbackTitle = "{$from} to {$to} One Way Taxi | Duracabs";
            $fallbackDescription = "Book a {$from} to {$to} one-way taxi with Duracabs. Compare available cars, view fare details and book securely online.";
        }

        $seoPage = Cache::remember('rides:seo-page', now()->addHours(6), fn () => SeoPage::query()
            ->select(['meta_title', 'meta_description', 'image'])
            ->whereIn('slug', ['rides', 'ride', 'cab-booking', 'taxi-booking'])
            ->first());

        $image = 'https://www.duracabs.com/img/logo/duracabs_logo.jpeg';
        if (filled($seoPage?->image)) {
            $image = str_starts_with($seoPage->image, 'http://') || str_starts_with($seoPage->image, 'https://')
                ? $seoPage->image
                : asset('storage/' . ltrim($seoPage->image, '/'));
        }

        return [
            'pageTitle' => filled($seoPage?->meta_title) ? $seoPage->meta_title : $fallbackTitle,
            'pageDescription' => filled($seoPage?->meta_description) ? $seoPage->meta_description : $fallbackDescription,
            'pageImage' => $image,
        ];
    }

    public function render()
    {
        $isSelfDrive = $this->tab === 'self_drive';

        if ($isSelfDrive) {
            $this->refreshSelfDriveAvailabilityState();

            $ridesQuery = Vehicle::query()
                ->with('transporter')
                ->availableForRental()
                ->selfDrive();

            // Homepage/banner se specific vehicle select hui ho to sirf wahi show karein.
            if ($this->vehicle_id) {
                $ridesQuery->whereKey($this->vehicle_id);
            } elseif (! $this->selfDrivePeriodInvalid) {
                try {
                    [$pickupAt, $dropAt] = $this->validatedSelfDrivePeriod();
                    $this->applySelfDriveAvailabilityFilter($ridesQuery, $pickupAt, $dropAt);
                } catch (\Throwable $exception) {
                    // The invalid period state is already exposed to the Blade view.
                }
            }

            if ($this->price_range) {
                $ridesQuery->whereBetween('hourly_price', [0, $this->price_range]);
            }

            if ($this->sort === 'latest') {
                $ridesQuery->latest();
            } else {
                $ridesQuery->orderByRaw('CASE WHEN hourly_price IS NULL THEN 1 ELSE 0 END')
                    ->orderBy('hourly_price');
            }
        } else {
            $ridesQuery = Product::query()->where('is_active', 1);

            if (! empty($this->selected_categories)) {
                $ridesQuery->where('category_id', $this->selected_categories);
            }

            if (! empty($this->selected_brands)) {
                $ridesQuery->where(function ($query) {
                    $query->where('brand_id', $this->selected_brands)
                        ->orWhere('booking_to', $this->selected_brands);
                });
            }

            if (! empty($this->cityFrom)) {
                $ridesQuery->where('brand_id', $this->cityFrom);
            }

            if (! empty($this->cityTo)) {
                $ridesQuery->where('booking_to', $this->cityTo);
            }

            if ($this->price_range) {
                $ridesQuery->whereBetween('price', [0, $this->price_range]);
            }

            if ($this->sort === 'latest') {
                $ridesQuery->latest();
            }

            if ($this->sort === 'price') {
                $ridesQuery->orderBy('price');
            }

            if (! empty($this->tab)) {
                $ridesQuery->where('ride_type', $this->tab);
            }

            if ($this->tab === 'local') {
                $ridesQuery->where('plan', $this->plan);
            }

            $ridesQuery->with(['prices.category']);
        }

        $brandsData = Cache::remember('rides:active-brands', now()->addMinutes(30), fn () => Brand::query()
            ->select(['id', 'name', 'slug'])
            ->where('is_active', 1)
            ->orderBy('name')
            ->get());
        $brandFilter = $this->query2 ? $this->brandsData : $brandsData;
        $seo = $this->seoData();
        $categories = Cache::remember('rides:active-categories', now()->addMinutes(30), fn () => Category::query()
            ->select(['id', 'name', 'slug', 'image', 'new_vehicle', 'pet_friendly', 'roof_career'])
            ->where('is_active', 1)
            ->orderBy('name')
            ->get());
        $returnCategories = Cache::remember('rides:return-categories', now()->addMinutes(30), fn () => Category::query()
            ->select(['id', 'name', 'slug', 'image', 'km_charge', 'driver_charge', 'range', 'new_vehicle', 'pet_friendly', 'roof_career'])
            ->where('in_return', 1)
            ->orderBy('name')
            ->get());

        return view('livewire.rides-page', [
            'rides' => $ridesQuery->paginate(perPage: 9),
            'isSelfDriveListing' => $isSelfDrive,
            'selectedVehicleBooked' => $this->selectedVehicleBooked,
            'selfDrivePeriodInvalid' => $this->selfDrivePeriodInvalid,
            'selfDriveAvailabilityMessage' => $this->selfDriveAvailabilityMessage,
            'brands' => $brandFilter,
            'categories' => $categories,
            'categories2' => $returnCategories,
            'newTime' => $this->time ? DateTime::createFromFormat('H:i', $this->time) : false,
            'timeEnd' => $this->endTime ? DateTime::createFromFormat('H:i', $this->endTime) : false,
            'pageTitle' => $seo['pageTitle'],
            'pageDescription' => $seo['pageDescription'],
            'pageImage' => $seo['pageImage'],
        ]);
    }
}