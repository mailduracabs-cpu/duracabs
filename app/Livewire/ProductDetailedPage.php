<?php

namespace App\Livewire;

use App\Services\OtpService;
use App\Models\Brand;
use App\Models\Product;
use App\Models\RideInquiry;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\Title;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Number;

#[Title('Ride Detail - Duracabs')]
class ProductDetailedPage extends Component
{

    use LivewireAlert;
   
    public $slug;
    public $hours = false;
    public $tab = false;
    public $date = false;
    public $time = false;

    public $endDate = false;
    public $endTime = false;
    public $plan = '4 Hour / 40 Km';
    public $cars = false;

    public $price = false;
    public $name = false;
    public $categoryName = false;
     public $toll = false;
    public $newVehical = false;
    public $petFrindly = false;
    public $roof_career = false;
    public $security = false;

    public $quantity = 1;

    // Same-page trip edit
    public bool $showEditTripModal = false;
    public ?int $editPickupId = null;
    public ?int $editDropId = null;
    public string $editRideType = 'one_way';
    public string $editDate = '';
    public string $editTime = '';
    public string $editEndDate = '';
    public string $editEndTime = '';

    // 4-digit OTP fare gate
    public bool $showOtpModal = false;
    public bool $fareUnlocked = false;
    public string $otpStage = 'mobile';
    public string $mobileNumber = '';
    public string $otpCode = '';
    public ?string $otpError = null;
    public ?array $pendingTabValue = null;
    public ?string $pendingBookingAction = null;
    public mixed $pendingBookingPayload = null;

    public function mount($slug): void
    {
        $this->slug = $slug;
        $this->fareUnlocked = Auth::check() || (bool) session('route_fare_unlocked', false);
        $this->mobileNumber = (string) session('route_verified_mobile', '');
        $this->date = request()->query('date', '');
        $this->time = request()->query('time', '');
        $this->endDate = request()->query('end_date', '');
        $this->endTime = request()->query('end_time', '');
    }

    public function test()
{
    $this->quantity++;
    
}

public function submitOneWay($productId)
{
    $this->validate([
        'date' => ['required', 'date'],
        'time' => ['required'],
    ]);

    return $this->storeBookingDraft('one_way', $productId, __FUNCTION__);
}

public function submitLocal($productId)
{
    $this->validate([
        'date' => ['required', 'date'],
        'time' => ['required'],
        'plan' => ['required', 'string'],
    ]);

    return $this->storeBookingDraft('local', $productId, __FUNCTION__);
}

public function submitSelfDrive($productId)
{
    $this->validate([
        'date' => ['required', 'date'],
        'time' => ['required'],
        'endDate' => ['required', 'date', 'after_or_equal:date'],
        'endTime' => ['required'],
    ]);

    $this->recalculateSelfDriveHours();

    if ($this->hours === false) {
        $this->addError('endTime', 'Drop date and time pickup date and time ke baad honi chahiye.');
        return null;
    }

    return $this->storeBookingDraft('self_drive', $productId, __FUNCTION__);
}

private function storeBookingDraft(string $bookingType, mixed $selectionId, string $pendingAction)
{
    if (! $this->fareUnlocked) {
        $this->openFareGate($pendingAction, $selectionId);
        return null;
    }

    try {
        $ride = Product::query()
            ->with(['brand', 'prices.category'])
            ->where('slug', $this->slug)
            ->where('is_active', 1)
            ->firstOrFail();

        $selectedPrice = $ride->prices->firstWhere('id', (int) $selectionId);
        $dropCity = ! empty($ride->booking_to)
            ? Brand::query()->find($ride->booking_to)
            : null;

        $unitPrice = (float) ($selectedPrice?->price ?? $this->price ?? 0);
        $quantity = max(1, (int) $this->quantity);
        $hours = $bookingType === 'self_drive'
            ? max(24, (int) ($this->hours ?: 24))
            : null;

        session()->put('booking_draft', [
            'version' => 1,
            'type' => $bookingType,
            'source' => 'product_detailed_page',
            'selection_id' => is_numeric($selectionId) ? (int) $selectionId : $selectionId,
            'product_id' => (int) $ride->getKey(),
            'vehicle_id' => $selectedPrice?->category_id
                ? (int) $selectedPrice->category_id
                : null,
            'customer' => [
                'user_id' => Auth::id(),
                'mobile' => $this->mobileNumber ?: session('route_verified_mobile'),
            ],
            'trip' => [
                'pickup_city_id' => $ride->brand_id ? (int) $ride->brand_id : null,
                'pickup_name' => $ride->brand?->name,
                'drop_city_id' => $ride->booking_to ? (int) $ride->booking_to : null,
                'drop_name' => $dropCity?->name,
                'date' => (string) $this->date,
                'time' => (string) $this->time,
                'end_date' => $bookingType === 'self_drive' ? (string) $this->endDate : null,
                'end_time' => $bookingType === 'self_drive' ? (string) $this->endTime : null,
                'hours' => $hours,
                'plan' => $bookingType === 'local' ? (string) $this->plan : null,
                'quantity' => $quantity,
            ],
            'fare' => [
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'subtotal' => $unitPrice * $quantity,
                'display_price' => $this->price,
                'toll' => $this->toll,
                'security' => $bookingType === 'self_drive' ? $this->security : null,
            ],
            'product' => [
                'slug' => (string) $ride->slug,
                'name' => (string) ($this->name ?: $ride->name),
                'category_name' => (string) ($this->categoryName ?: $selectedPrice?->category?->name ?: ''),
                'ride_type' => (string) ($ride->ride_type ?: $bookingType),
                'new_vehicle' => (bool) $this->newVehical,
                'pet_friendly' => (bool) $this->petFrindly,
                'roof_carrier' => (bool) $this->roof_career,
            ],
            'created_at' => now()->toIso8601String(),
        ]);

        $this->pendingBookingAction = null;
        $this->pendingBookingPayload = null;

        return redirect()->route('checkout');
    } catch (\Throwable $e) {
        Log::error('Booking draft creation failed', [
            'slug' => $this->slug,
            'booking_type' => $bookingType,
            'selection_id' => $selectionId,
            'error' => $e->getMessage(),
        ]);

        $this->alert('error', 'Booking start nahi ho saki. Page refresh karke dobara try karein.', [
            'position' => 'center-end',
            'timer' => 4000,
            'toast' => true,
        ]);

        return null;
    }
}

public function updatedEndDate(): void
{
    $this->recalculateSelfDriveHours();
}

public function updatedEndTime(): void
{
    $this->recalculateSelfDriveHours();
}

public function updatedTime(): void
{
    $this->recalculateSelfDriveHours();
}

public function updatedDate(): void
{
    $this->recalculateSelfDriveHours();
}

private function recalculateSelfDriveHours(): void
{
    if (! $this->date || ! $this->time || ! $this->endDate || ! $this->endTime) {
        $this->hours = false;
        return;
    }

    $startTimestamp = strtotime(sprintf('%s %s', $this->date, $this->time));
    $endTimestamp = strtotime(sprintf('%s %s', $this->endDate, $this->endTime));

    if ($startTimestamp === false || $endTimestamp === false || $endTimestamp <= $startTimestamp) {
        $this->hours = false;
        return;
    }

    $calculatedHours = (int) ceil(($endTimestamp - $startTimestamp) / 3600);
    $this->hours = max(24, $calculatedHours);
}


public function tabValue($val){

    if (! $this->fareUnlocked) {
        $this->pendingTabValue = is_array($val) ? $val : null;
        $this->openFareGate();
        return;
    }

    $this->tab = $val[0];
    $this->price = $val[1];
    $this->name = $val[2];
    $this->categoryName = $val[3];

    if($this->tab == 'one_way' || $this->tab == 'local'){
        $this->toll = $val[4];
        $this->newVehical = $val[5];
        $this->petFrindly = $val[6];
        $this->roof_career = $val[7];
    }

    if($this->tab == 'self_drive'){
        $this->security = $val[4];
       
    }
   
}
    

    

    public function increaseQty(){
        $this->quantity++;
    }

    public function decreaseQty(){
       if($this->quantity > 1){
        $this->quantity--;
       }
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
        $this->resetErrorBag();
    }

    public function closeOtpModal(): void
    {
        $this->showOtpModal = false;
        $this->otpCode = '';
        $this->otpError = null;
    }

    public function changeOtpMobile(): void
    {
        $this->otpStage = 'mobile';
        $this->otpCode = '';
        $this->otpError = null;
    }

    public function sendFareOtp(): void
    {
        $this->resetErrorBag();
        $this->otpError = null;

        $result = app(OtpService::class)->sendFareOtp(
            (string) $this->mobileNumber,
            'route-details',
            request()->ip()
        );

        if (! ($result['success'] ?? false)) {
            $this->otpError = (string) ($result['message'] ?? 'OTP send nahi ho saka.');
            return;
        }

        $this->mobileNumber = (string) $result['mobile'];
        $this->otpStage = 'otp';
        $this->otpCode = '';
        $this->dispatch('route-otp-sent');
    }

    public function verifyFareOtp()
    {
        $this->resetErrorBag();
        $this->otpError = null;

        $result = app(OtpService::class)->verifyFareOtp(
            (string) $this->mobileNumber,
            (string) $this->otpCode,
            'route-details'
        );

        if (! ($result['success'] ?? false)) {
            $this->otpError = (string) ($result['message'] ?? 'OTP verify nahi ho saka.');
            return;
        }

        $mobile = (string) $result['mobile'];
        $user = $result['user'] ?? null;

        session([
            'route_fare_unlocked' => true,
            'route_verified_mobile' => $mobile,
        ]);

        $this->fareUnlocked = true;
        $this->showOtpModal = false;
        $this->otpStage = 'mobile';
        $this->otpCode = '';
        $this->createOrUpdateInquiry($mobile, $user?->getKey());

        $pending = $this->pendingTabValue;
        $this->pendingTabValue = null;
        if ($pending) {
            $this->tabValue($pending);
        }

        $pendingAction = $this->pendingBookingAction;
        $pendingPayload = $this->pendingBookingPayload;

        if (
            $pendingAction
            && in_array($pendingAction, ['submitOneWay', 'submitLocal', 'submitSelfDrive'], true)
            && method_exists($this, $pendingAction)
        ) {
            $this->pendingBookingAction = null;
            $this->pendingBookingPayload = null;

            return $this->{$pendingAction}($pendingPayload);
        }

        return null;
    }

    private function createOrUpdateInquiry(string $mobile, mixed $userId = null): void
    {
        try {
            $ride = Product::query()->with(['brand', 'prices'])->where('slug', $this->slug)->firstOrFail();
            $drop = Brand::query()->find($ride->booking_to);
            $travelDate = $this->date ?: now()->toDateString();
            $lowestFare = $ride->prices->min('price');

            $inquiry = RideInquiry::query()
                ->where('mobile', $mobile)
                ->where('pickup_city_id', $ride->brand_id)
                ->where('drop_city_id', $ride->booking_to)
                ->where('trip_type', $ride->ride_type ?: 'one_way')
                ->whereDate('travel_date', $travelDate)
                ->where('created_at', '>=', now()->subHours(24))
                ->latest()
                ->first();

            $payload = [
                'user_id' => $userId,
                'mobile' => $mobile,
                'pickup_city_id' => $ride->brand_id,
                'drop_city_id' => $ride->booking_to,
                'pickup_name' => $ride->brand?->name,
                'drop_name' => $drop?->name,
                'trip_type' => $ride->ride_type ?: 'one_way',
                'travel_date' => $travelDate,
                'travel_time' => $this->time ?: null,
                'return_date' => $this->endDate ?: null,
                'estimated_fare_from' => $lowestFare,
                'source' => request()->query('gclid') ? 'google_ads' : (request()->query('utm_source') ? 'utm:' . request()->query('utm_source') : 'seo_route_page'),
                'landing_url' => request()->fullUrl(),
                'utm_source' => request()->query('utm_source'),
                'utm_medium' => request()->query('utm_medium'),
                'utm_campaign' => request()->query('utm_campaign'),
                'gclid' => request()->query('gclid'),
                'fbclid' => request()->query('fbclid'),
                'status' => $inquiry?->status ?: 'new',
                'last_activity_at' => now(),
            ];

            if ($inquiry) {
                $inquiry->update($payload);
            } else {
                $payload['inquiry_no'] = 'RI-' . now()->format('ymd') . '-' . strtoupper(Str::random(6));
                RideInquiry::create($payload);
            }
        } catch (\Throwable $e) {
            Log::error('Route inquiry create/update failed', ['error' => $e->getMessage()]);
        }
    }

    public function openEditTripModal(): void
    {
        $ride = Product::query()->where('slug', $this->slug)->firstOrFail();
        $this->editPickupId = $ride->brand_id ? (int) $ride->brand_id : null;
        $this->editDropId = $ride->booking_to ? (int) $ride->booking_to : null;
        $this->editRideType = (string) ($ride->ride_type ?: 'one_way');
        $this->editDate = (string) ($this->date ?: now()->toDateString());
        $this->editTime = (string) ($this->time ?: '10:00');
        $this->editEndDate = (string) ($this->endDate ?: '');
        $this->editEndTime = (string) ($this->endTime ?: '');
        $this->showEditTripModal = true;
    }

    public function closeEditTripModal(): void
    {
        $this->showEditTripModal = false;
    }

    public function updateTripSearch()
    {
        $rules = [
            'editPickupId' => ['required', 'integer'],
            'editDate' => ['required', 'date', 'after_or_equal:today'],
            'editTime' => ['required'],
        ];

        if ($this->editRideType === 'one_way') {
            $rules['editDropId'] = ['required', 'integer', 'different:editPickupId'];
        }
        if ($this->editRideType === 'self_drive') {
            $rules['editEndDate'] = ['required', 'date', 'after_or_equal:editDate'];
            $rules['editEndTime'] = ['required'];
        }
        $this->validate($rules);

        $query = Product::query()
            ->where('is_active', 1)
            ->where('brand_id', $this->editPickupId)
            ->where('ride_type', $this->editRideType);

        if ($this->editRideType === 'one_way') {
            $query->where('booking_to', $this->editDropId);
        }

        $target = $query->first();
        if (! $target) {
            $this->addError('editPickupId', 'Is route ke liye active package nahi mila.');
            return null;
        }

        $params = array_filter([
            'date' => $this->editDate,
            'time' => $this->editTime,
            'end_date' => $this->editEndDate,
            'end_time' => $this->editEndTime,
        ]);

        return redirect()->route('route.show', ['slug' => $target->slug] + $params);
    }

    public function render()
    {
        $ride = Product::query()
            ->with(['brand', 'prices.category', 'links'])
            ->where('slug', $this->slug)
            ->where('is_active', 1)
            ->firstOrFail();

        $cityTo = ! empty($ride->booking_to)
            ? Brand::query()->find($ride->booking_to)
            : null;

        $prices = $ride->prices;
        $links = $ride->links;

        $ridesQuery = Product::query()
            ->with(['brand'])
            ->where('is_active', 1)
            ->whereKeyNot($ride->getKey());

        if (! empty($ride->brand_id)) {
            $ridesQuery->where('brand_id', $ride->brand_id);
        }

        if (! empty($ride->ride_type)) {
            $ridesQuery->where('ride_type', $ride->ride_type);
        }

        $contentType = (string) ($ride->content_type ?: 'route');
        $urlType = (string) ($ride->url_type ?: 'route');

        $pickupName = $this->cleanCityName(
            $ride->brand?->name ?: $ride->name ?: 'Dura Cabs'
        );

        $dropName = $this->cleanCityName($cityTo?->name);

        $tripLabel = match ($ride->ride_type) {
            'local' => 'Local Cab',
            'self_drive' => 'Self Drive Car',
            'round_trip' => 'Round Trip Taxi',
            'airport' => 'Airport Taxi',
            default => 'One Way Taxi',
        };

        $routeName = match ($contentType) {
            'blog', 'page', 'landing_page', 'product' => trim((string) ($ride->name ?: $pickupName)),
            default => $ride->ride_type === 'one_way' && $dropName !== ''
                ? "{$pickupName} to {$dropName}"
                : $pickupName,
        };

        $seoTitle = filled($ride->meta_title)
            ? trim((string) $ride->meta_title)
            : $this->buildFallbackSeoTitle($ride, $routeName, $tripLabel, $contentType);

        $lowestFare = $prices->min('price');
        $fareText = $lowestFare
            ? ' Fares start from ' . Number::currency((float) $lowestFare, 'INR') . '.'
            : '';

        $seoDescription = filled($ride->meta_description)
            ? trim((string) $ride->meta_description)
            : $this->buildFallbackSeoDescription($routeName, $tripLabel, $contentType, $fareText);

        $canonicalUrl = filled($ride->canonical_url)
            ? trim((string) $ride->canonical_url)
            : $this->resolveCanonicalUrl($ride, $urlType);

        $firstImage = collect($ride->images ?? [])->filter()->first();
        $imageMeta = $this->resolveSeoImage($ride, $firstImage);

        $metaKeywords = $this->normaliseKeywords($ride->meta_keywords ?? null, $ride->focus_keyword ?? null);
        $robots = filled($ride->robots)
            ? trim((string) $ride->robots)
            : 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';

        $ogType = $contentType === 'blog' ? 'article' : 'website';

        $faqs = $this->buildFaqs($ride, $routeName, $tripLabel, $lowestFare);

        $serviceSchema = $this->buildServiceSchema(
            $ride,
            $canonicalUrl,
            $seoTitle,
            $seoDescription,
            $imageMeta,
            $tripLabel,
            $pickupName,
            $dropName,
            $lowestFare,
            $prices->count(),
            $contentType
        );

        $faqSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            '@id' => $canonicalUrl . '#faq',
            'mainEntity' => collect($faqs)->map(fn (array $faq) => [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq['answer'],
                ],
            ])->values()->all(),
        ];

        $breadcrumbSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            '@id' => $canonicalUrl . '#breadcrumb',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Home',
                    'item' => url('/'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => 'Cab Routes',
                    'item' => url('/routes'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => $routeName,
                    'item' => $canonicalUrl,
                ],
            ],
        ];

        return view('livewire.product-detailed-page', [
            'ride' => $ride,
            'rides' => $ridesQuery->paginate(9),
            'cityTo' => $cityTo,
            'links' => $links,
            'prices' => $prices,
            'imageMeta' => $imageMeta,
            'allCities' => Brand::query()->orderBy('name')->get(['id', 'name']),
            'seoTitle' => $seoTitle,
            'seoDescription' => $seoDescription,
            'metaKeywords' => $metaKeywords,
            'robots' => $robots,
            'canonicalUrl' => $canonicalUrl,
            'routeName' => $routeName,
            'tripLabel' => $tripLabel,
            'contentType' => $contentType,
            'ogType' => $ogType,
            'faqs' => $faqs,
            'serviceSchema' => $serviceSchema,
            'faqSchema' => $faqSchema,
            'breadcrumbSchema' => $breadcrumbSchema,
            'contentLinks' => collect($ride->content_links ?? [])->filter()->values(),
            'fareCards' => collect($ride->fare_cards ?? [])->filter()->values(),
            'linkedProducts' => collect($ride->link_products ?? [])->filter()->values(),
        ]);
    }


    private function cleanCityName(mixed $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        return trim(explode(',', $value, 2)[0]);
    }

    private function buildFallbackSeoTitle(Product $ride, string $routeName, string $tripLabel, string $contentType): string
    {
        return match ($contentType) {
            'blog' => "{$routeName} | Dura Cabs Blog",
            'page' => "{$routeName} | Dura Cabs",
            'landing_page' => "{$routeName} | Book with Dura Cabs",
            'product' => "{$routeName} | Dura Cabs",
            default => "{$routeName} {$tripLabel} Booking | Dura Cabs",
        };
    }

    private function buildFallbackSeoDescription(string $routeName, string $tripLabel, string $contentType, string $fareText): string
    {
        return match ($contentType) {
            'blog' => "Read useful travel information, cab booking guidance and local tips about {$routeName} from Dura Cabs.",
            'page' => "Learn more about {$routeName} and book reliable cab services with Dura Cabs.",
            'landing_page' => "Book {$routeName} with verified drivers, clean cars and transparent pricing through Dura Cabs.{$fareText}",
            'product' => "Explore {$routeName}, pricing, availability and booking details with Dura Cabs.",
            default => "Book {$routeName} {$tripLabel} with verified drivers, clean cars and transparent pricing.{$fareText} Check available cabs and reserve online with Dura Cabs.",
        };
    }

    private function resolveCanonicalUrl(Product $ride, string $urlType): string
    {
        $slug = trim((string) $ride->slug, '/');

        return match ($urlType) {
            'page' => url('/pages/' . $slug),
            'blog' => url('/blog/' . $slug),
            'product' => url('/product/' . $slug),
            'root', 'landing_page' => url('/' . $slug),
            default => route('route.show', ['slug' => $slug]),
        };
    }

    private function resolveSeoImage(Product $ride, mixed $firstImage): string
    {
        foreach ([$ride->image ?? null, $firstImage] as $image) {
            if (filled($image)) {
                return $this->normaliseAssetUrl((string) $image);
            }
        }

        return asset('img/logo/favicon_duracabs.ico');
    }

    private function normaliseAssetUrl(string $path): string
    {
        $path = trim($path);

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        if (Str::startsWith($path, ['/storage/', 'storage/'])) {
            return url('/' . ltrim($path, '/'));
        }

        return url('/storage/' . ltrim($path, '/'));
    }

    private function normaliseKeywords(mixed $keywords, mixed $focusKeyword): string
    {
        $items = [];

        if (is_array($keywords)) {
            $items = $keywords;
        } elseif (is_string($keywords)) {
            $items = preg_split('/[,\n]+/', $keywords) ?: [];
        }

        if (filled($focusKeyword)) {
            array_unshift($items, (string) $focusKeyword);
        }

        return collect($items)
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->unique(fn ($item) => Str::lower($item))
            ->implode(', ');
    }

    private function buildFaqs(Product $ride, string $routeName, string $tripLabel, mixed $lowestFare): array
    {
        $customFaqs = collect($ride->faqs ?? [])
            ->map(function ($faq) {
                if (! is_array($faq)) {
                    return null;
                }

                $question = trim((string) Arr::get($faq, 'question', ''));
                $answer = trim((string) Arr::get($faq, 'answer', ''));

                return $question !== '' && $answer !== ''
                    ? ['question' => $question, 'answer' => $answer]
                    : null;
            })
            ->filter()
            ->values()
            ->all();

        if ($customFaqs !== []) {
            return $customFaqs;
        }

        return [
            [
                'question' => "What cab options are available for {$routeName}?",
                'answer' => "Dura Cabs offers multiple vehicle categories for {$routeName}, subject to live availability. Select a suitable cab and verify your mobile number to view the exact fare.",
            ],
            [
                'question' => "What is the minimum cab fare for {$routeName}?",
                'answer' => $lowestFare
                    ? 'The currently listed fare starts from ' . Number::currency((float) $lowestFare, 'INR') . '. The final payable amount depends on the selected vehicle, trip details, taxes and applicable extras.'
                    : 'The fare depends on the selected vehicle and trip details. Verify your mobile number on this page to view the latest available price.',
            ],
            [
                'question' => "How can I book {$tripLabel} for {$routeName}?",
                'answer' => 'Choose your vehicle, enter the pickup date and time, verify your mobile number, review the fare details and continue to checkout.',
            ],
            [
                'question' => 'Are toll, parking and taxes included in the displayed fare?',
                'answer' => 'Inclusions vary by package. Review the fare breakdown shown for the selected vehicle before booking. Toll, parking, state tax or other route-specific charges may be payable separately when marked as excluded.',
            ],
            [
                'question' => 'Can I change my trip details before booking?',
                'answer' => 'Yes. Use the Edit Trip option on this page to change the pickup city, destination, trip type, date or time before proceeding.',
            ],
        ];
    }

    private function buildServiceSchema(
        Product $ride,
        string $canonicalUrl,
        string $seoTitle,
        string $seoDescription,
        string $imageMeta,
        string $tripLabel,
        string $pickupName,
        string $dropName,
        mixed $lowestFare,
        int $offerCount,
        string $contentType
    ): array {
        if ($contentType === 'blog') {
            return [
                '@context' => 'https://schema.org',
                '@type' => 'Article',
                '@id' => $canonicalUrl . '#article',
                'headline' => $seoTitle,
                'description' => $seoDescription,
                'url' => $canonicalUrl,
                'image' => [$imageMeta],
                'datePublished' => optional($ride->created_at)?->toAtomString(),
                'dateModified' => optional($ride->updated_at)?->toAtomString(),
                'author' => [
                    '@type' => 'Organization',
                    'name' => 'Dura Cabs',
                ],
                'publisher' => [
                    '@type' => 'Organization',
                    'name' => 'Dura Cabs',
                    'url' => url('/'),
                ],
            ];
        }

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => $contentType === 'product' ? 'Product' : 'Service',
            '@id' => $canonicalUrl . '#service',
            'name' => $seoTitle,
            'description' => $seoDescription,
            'url' => $canonicalUrl,
            'image' => $imageMeta,
            'serviceType' => $contentType === 'product' ? null : $tripLabel,
            'provider' => [
                '@id' => url('/') . '#business',
            ],
            'areaServed' => $contentType === 'product' ? null : array_values(array_filter([
                ['@type' => 'City', 'name' => $pickupName],
                $ride->ride_type === 'one_way' && $dropName !== '' ? ['@type' => 'City', 'name' => $dropName] : null,
            ])),
            'offers' => $lowestFare ? [
                '@type' => $offerCount > 1 ? 'AggregateOffer' : 'Offer',
                'priceCurrency' => 'INR',
                $offerCount > 1 ? 'lowPrice' : 'price' => (float) $lowestFare,
                'offerCount' => $offerCount > 1 ? $offerCount : null,
                'availability' => 'https://schema.org/InStock',
                'url' => $canonicalUrl,
            ] : null,
        ], static fn ($value) => $value !== null && $value !== '' && $value !== []);
    }
}