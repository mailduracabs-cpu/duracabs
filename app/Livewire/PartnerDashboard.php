<?php

namespace App\Livewire;

use App\Models\FleetManagement\TransporterProfile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class PartnerDashboard extends Component
{
    use WithFileUploads;

    public $user;
    public $profile;

    public string $activeTab = 'dashboard';

    public bool $partnerTypeLocked = false;

    public $partnerType = '';
    public $companyName = '';
    public $contactPerson = '';
    public $mobile = '';
    public $whatsappNumber = '';
    public $email = '';
    public $aadhaarNumber = '';
    public $panNumber = '';
    public $gstNumber = '';
    public $officeAddress = '';
    public $city = '';
    public $state = '';
    public $pincode = '';

    public $aadhaarImage;
    public $panImage;
    public $gstImage;
    public $companyDocument;
    public $officePhoto;

    public int $profileCompletion = 0;
    public int $bookingsCount = 0;
    public int $vehiclesCount = 0;
    public int $driversCount = 0;
    public int $reviewsCount = 0;
    public float $incomeToday = 0;

    public array $fleets = [];
    public array $categories = [];
    public array $brands = [];

    public bool $showFleetForm = false;
    public bool $editingFleet = false;
    public $editingFleetId = null;

    public $fleetServiceType = 'self_drive';
    public $vehicleType = 'car';
    public $categoryId = '';
    public $brandId = '';

    public $titleName = '';
    public $slug = '';
    public $description = '';
    public $brandName = '';
    public $modelName = '';
    public $modelYear = '';
    public $fuel = '';
    public $transmission = '';
    public $passengerCapacity = '';
    public $luggageCapacity = '';
    public $rangeKm = '';
    public $vehicleNumber = '';
    public $chassisNumber = '';
    public $engineNumber = '';
    public $ownerName = '';
    public $vehicleColor = '';

    public $kmCharge = '';
    public $hourCharge = '';
    public $dailyCharge = '';
    public $weeklyCharge = '';
    public $monthlyCharge = '';
    public $securityDeposit = '';

    public bool $isActive = true;

    public $vehicleImage;
    public $rcImage;
    public $insuranceImage;
    public $pollutionImage;
    public $fitnessImage;
    public $authorityImage;

    public $driverName = '';
    public $driverMobile = '';
    public $driverLicenseNumber = '';

    public function mount()
    {
        if (! Auth::check()) {
            return redirect()->route('partner.login');
        }

        $this->user = Auth::user();

        $this->profile = TransporterProfile::firstOrCreate(
            ['user_id' => $this->user->id],
            [
                'partner_type' => '',
                'company_name' => $this->user->company_name ?? '',
                'contact_person' => $this->user->name ?? '',
                'mobile' => $this->user->mobile ?? '',
                'email' => $this->user->email ?? '',
                'status' => true,
                'verification_status' => 'pending',
            ]
        );

        $this->fillProfileForm();
        $this->loadCategories();
        $this->loadBrands();
        $this->loadFleets();
        $this->loadStats();
        $this->calculateProfileCompletion();
    }

    public function fillProfileForm(): void
    {
        $this->partnerType = $this->profile->partner_type ?? '';
        $this->partnerTypeLocked = ! empty($this->profile->partner_type);

        $this->companyName = $this->profile->company_name ?? '';
        $this->contactPerson = $this->profile->contact_person ?? $this->user->name ?? '';
        $this->mobile = $this->profile->mobile ?? $this->user->mobile ?? '';
        $this->whatsappNumber = $this->profile->whatsapp_number ?? '';
        $this->email = $this->profile->email ?? $this->user->email ?? '';

        $this->aadhaarNumber = $this->profile->aadhaar_number ?? '';
        $this->panNumber = $this->profile->pan_number ?? '';
        $this->gstNumber = $this->profile->gst_number ?? '';

        $this->officeAddress = $this->profile->office_address ?? '';
        $this->city = $this->profile->city ?? '';
        $this->state = $this->profile->state ?? '';
        $this->pincode = $this->profile->pincode ?? '';
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;

        if ($tab === 'vehicles') {
            $this->loadFleets();
        }
    }

    public function updateProfile()
    {
        $this->validate([
            'partnerType' => 'required|in:host,vendor,both',
            'companyName' => 'required|max:255',
            'contactPerson' => 'required|max:255',
            'mobile' => 'required|max:20',
            'whatsappNumber' => 'nullable|max:20',
            'email' => 'nullable|email|max:255',
            'aadhaarNumber' => 'nullable|max:20',
            'panNumber' => 'nullable|max:20',
            'gstNumber' => 'nullable|max:30',
            'officeAddress' => 'nullable|max:500',
            'city' => 'required|max:255',
            'state' => 'required|max:255',
            'pincode' => 'nullable|max:20',
            'aadhaarImage' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'panImage' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'gstImage' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'companyDocument' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'officePhoto' => 'nullable|file|mimes:jpg,jpeg,png|max:4096',
        ]);

        $finalPartnerType = $this->profile->partner_type ?: $this->partnerType;

        $data = [
            'partner_type' => $finalPartnerType,
            'company_name' => $this->companyName,
            'contact_person' => $this->contactPerson,
            'mobile' => $this->mobile,
            'whatsapp_number' => $this->whatsappNumber,
            'email' => $this->email,
            'aadhaar_number' => $this->aadhaarNumber,
            'pan_number' => $this->panNumber,
            'gst_number' => $this->gstNumber,
            'office_address' => $this->officeAddress,
            'city' => $this->city,
            'state' => $this->state,
            'pincode' => $this->pincode,
            'status' => true,
            'verification_status' => $this->profile->verification_status ?? 'pending',
        ];

        if ($this->aadhaarImage) {
            $data['aadhaar_image'] = $this->aadhaarImage->store('partner-documents/aadhaar', 'public');
        }

        if ($this->panImage) {
            $data['pan_image'] = $this->panImage->store('partner-documents/pan', 'public');
        }

        if ($this->gstImage) {
            $data['gst_image'] = $this->gstImage->store('partner-documents/gst', 'public');
        }

        if ($this->companyDocument) {
            $data['company_document'] = $this->companyDocument->store('partner-documents/company', 'public');
        }

        if ($this->officePhoto) {
            $data['office_photo'] = $this->officePhoto->store('partner-documents/office', 'public');
        }

        $this->profile->update($data);

        $this->user->update([
            'name' => $this->contactPerson,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'company_name' => $this->companyName,
        ]);

        $this->user = Auth::user()->fresh();
        $this->profile = TransporterProfile::where('user_id', $this->user->id)->first();

        $this->fillProfileForm();
        $this->calculateProfileCompletion();

        session()->flash('success', 'Partner profile saved successfully.');
    }

    public function openAddFleetForm(string $serviceType = 'self_drive'): void
    {
        $this->activeTab = 'vehicles';
        $this->resetFleetForm();

        $this->fleetServiceType = $serviceType;
        $this->showFleetForm = true;
        $this->editingFleet = false;
    }

    public function resetFleetForm(): void
    {
        $this->editingFleetId = null;
        $this->fleetServiceType = 'self_drive';
        $this->vehicleType = 'car';
        $this->categoryId = '';
        $this->brandId = '';
        $this->titleName = '';
        $this->slug = '';
        $this->description = '';
        $this->brandName = '';
        $this->modelName = '';
        $this->modelYear = '';
        $this->fuel = '';
        $this->transmission = '';
        $this->passengerCapacity = '';
        $this->luggageCapacity = '';
        $this->rangeKm = '';
        $this->vehicleNumber = '';
        $this->chassisNumber = '';
        $this->engineNumber = '';
        $this->ownerName = '';
        $this->vehicleColor = '';
        $this->kmCharge = '';
        $this->hourCharge = '';
        $this->dailyCharge = '';
        $this->weeklyCharge = '';
        $this->monthlyCharge = '';
        $this->securityDeposit = '';
        $this->isActive = true;
        $this->driverName = '';
        $this->driverMobile = '';
        $this->driverLicenseNumber = '';
        $this->vehicleImage = null;
        $this->rcImage = null;
        $this->insuranceImage = null;
        $this->pollutionImage = null;
        $this->fitnessImage = null;
        $this->authorityImage = null;
    }

    public function saveFleet()
    {
        if (! Schema::hasTable('fleets')) {
            session()->flash('error', 'Fleets table not found.');
            return;
        }

        $this->validate([
            'fleetServiceType' => 'required|in:self_drive,with_driver',
            'vehicleType' => 'required|max:50',
            'titleName' => 'required|max:255',
            'modelName' => 'nullable|max:255',
            'modelYear' => 'nullable|max:20',
            'fuel' => 'nullable|max:100',
            'transmission' => 'nullable|max:100',
            'vehicleNumber' => 'nullable|max:100',
            'vehicleImage' => 'nullable|image|max:4096',
            'rcImage' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'insuranceImage' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'pollutionImage' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'fitnessImage' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'authorityImage' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ]);

        $data = [];

        $this->putFleet($data, ['transporter_profile_id'], $this->profile->id);
        $this->putFleet($data, ['user_id'], $this->user->id);
        $this->putFleet($data, ['service_type'], $this->fleetServiceType);
        $this->putFleet($data, ['service_group'], $this->fleetServiceType === 'self_drive' ? 'without_driver' : 'with_driver');
        $this->putFleet($data, ['vehicle_type', 'type'], $this->vehicleType);
        $this->putFleet($data, ['category_id'], $this->categoryId ?: null);
        $this->putFleet($data, ['brand_id'], $this->brandId ?: null);
        $this->putFleet($data, ['name', 'title'], $this->titleName);
        $this->putFleet($data, ['slug'], $this->slug ?: Str::slug($this->titleName));
        $this->putFleet($data, ['description', 'short_description'], $this->description);
        $this->putFleet($data, ['brand_name', 'company_name'], $this->brandName);
        $this->putFleet($data, ['model_name', 'model', 'model_similar'], $this->modelName);
        $this->putFleet($data, ['model_year', 'manufacture_year', 'manufacturing_year'], $this->modelYear);
        $this->putFleet($data, ['fuel', 'fuel_type'], $this->fuel);
        $this->putFleet($data, ['transmission'], $this->transmission);
        $this->putFleet($data, ['passenger_capacity', 'seats'], $this->passengerCapacity);
        $this->putFleet($data, ['luggage_capacity', 'bag_capacity', 'bags'], $this->luggageCapacity);
        $this->putFleet($data, ['range', 'range_km'], $this->numOrNull($this->rangeKm));
        $this->putFleet($data, ['vehicle_number', 'registration_number'], $this->vehicleNumber);
        $this->putFleet($data, ['chassis_number'], $this->chassisNumber);
        $this->putFleet($data, ['engine_number'], $this->engineNumber);
        $this->putFleet($data, ['owner_name'], $this->ownerName);
        $this->putFleet($data, ['color', 'car_color'], $this->vehicleColor);
        $this->putFleet($data, ['km_charge', 'price_per_km'], $this->kmCharge);
        $this->putFleet($data, ['hour_charge', 'price_per_hour'], $this->hourCharge);
        $this->putFleet($data, ['daily_charge', 'price_per_day'], $this->dailyCharge);
        $this->putFleet($data, ['weekly_charge'], $this->weeklyCharge);
        $this->putFleet($data, ['monthly_charge'], $this->monthlyCharge);
        $this->putFleet($data, ['security_deposit'], $this->securityDeposit);
        $this->putFleet($data, ['driver_name'], $this->driverName);
        $this->putFleet($data, ['driver_mobile'], $this->driverMobile);
        $this->putFleet($data, ['driver_license_number'], $this->driverLicenseNumber);
        $this->putFleet($data, ['status', 'is_active', 'active'], $this->isActive ? 1 : 0);

        if ($this->vehicleImage) {
            $this->putFleet($data, ['image', 'vehicle_image', 'photo'], $this->vehicleImage->store('fleet/vehicles', 'public'));
        }

        if ($this->rcImage) {
            $this->putFleet($data, ['rc_image'], $this->rcImage->store('fleet/documents/rc', 'public'));
        }

        if ($this->insuranceImage) {
            $this->putFleet($data, ['insurance_image'], $this->insuranceImage->store('fleet/documents/insurance', 'public'));
        }

        if ($this->pollutionImage) {
            $this->putFleet($data, ['pollution_image', 'puc_image'], $this->pollutionImage->store('fleet/documents/puc', 'public'));
        }

        if ($this->fitnessImage) {
            $this->putFleet($data, ['fitness_image'], $this->fitnessImage->store('fleet/documents/fitness', 'public'));
        }

        if ($this->authorityImage) {
            $this->putFleet($data, ['authority_image', 'permit_image'], $this->authorityImage->store('fleet/documents/permit', 'public'));
        }

        $data['updated_at'] = now();

        if ($this->editingFleet && $this->editingFleetId) {
            DB::table('fleets')->where('id', $this->editingFleetId)->update($data);
            session()->flash('success', 'Vehicle updated successfully.');
        } else {
            $data['created_at'] = now();
            DB::table('fleets')->insert($data);
            session()->flash('success', 'Vehicle added successfully.');
        }

        $this->showFleetForm = false;
        $this->resetFleetForm();
        $this->loadFleets();
        $this->loadStats();
    }

    public function editFleet($id): void
    {
        if (! Schema::hasTable('fleets')) {
            return;
        }

        $fleet = DB::table('fleets')->where('id', $id)->first();

        if (! $fleet) {
            return;
        }

        $this->activeTab = 'vehicles';
        $this->editingFleet = true;
        $this->showFleetForm = true;
        $this->editingFleetId = $id;

        $this->fleetServiceType = $fleet->service_type ?? (($fleet->service_group ?? '') === 'with_driver' ? 'with_driver' : 'self_drive');
        $this->vehicleType = $fleet->vehicle_type ?? $fleet->type ?? 'car';
        $this->categoryId = $fleet->category_id ?? '';
        $this->brandId = $fleet->brand_id ?? '';
        $this->titleName = $fleet->name ?? $fleet->title ?? '';
        $this->slug = $fleet->slug ?? '';
        $this->description = $fleet->description ?? $fleet->short_description ?? '';
        $this->brandName = $fleet->brand_name ?? $fleet->company_name ?? '';
        $this->modelName = $fleet->model_name ?? $fleet->model ?? $fleet->model_similar ?? '';
        $this->modelYear = $fleet->model_year ?? $fleet->manufacture_year ?? $fleet->manufacturing_year ?? '';
        $this->fuel = $fleet->fuel ?? $fleet->fuel_type ?? '';
        $this->transmission = $fleet->transmission ?? '';
        $this->passengerCapacity = $fleet->passenger_capacity ?? $fleet->seats ?? '';
        $this->luggageCapacity = $fleet->luggage_capacity ?? $fleet->bag_capacity ?? $fleet->bags ?? '';
        $this->rangeKm = $fleet->range ?? $fleet->range_km ?? '';
        $this->vehicleNumber = $fleet->vehicle_number ?? $fleet->registration_number ?? '';
        $this->chassisNumber = $fleet->chassis_number ?? '';
        $this->engineNumber = $fleet->engine_number ?? '';
        $this->ownerName = $fleet->owner_name ?? '';
        $this->vehicleColor = $fleet->color ?? $fleet->car_color ?? '';
        $this->kmCharge = $fleet->km_charge ?? $fleet->price_per_km ?? '';
        $this->hourCharge = $fleet->hour_charge ?? $fleet->price_per_hour ?? '';
        $this->dailyCharge = $fleet->daily_charge ?? $fleet->price_per_day ?? '';
        $this->weeklyCharge = $fleet->weekly_charge ?? '';
        $this->monthlyCharge = $fleet->monthly_charge ?? '';
        $this->securityDeposit = $fleet->security_deposit ?? '';
        $this->driverName = $fleet->driver_name ?? '';
        $this->driverMobile = $fleet->driver_mobile ?? '';
        $this->driverLicenseNumber = $fleet->driver_license_number ?? '';
        $this->isActive = (bool) ($fleet->status ?? $fleet->is_active ?? $fleet->active ?? true);
    }

    public function toggleFleetStatus($id): void
    {
        if (! Schema::hasTable('fleets')) {
            return;
        }

        $fleet = DB::table('fleets')->where('id', $id)->first();

        if (! $fleet) {
            return;
        }

        $column = $this->fleetColumn(['status', 'is_active', 'active']);

        if (! $column) {
            return;
        }

        DB::table('fleets')->where('id', $id)->update([
            $column => ! (bool) ($fleet->{$column} ?? false),
            'updated_at' => now(),
        ]);

        $this->loadFleets();
        session()->flash('success', 'Vehicle status updated.');
    }

    public function deleteFleet($id): void
    {
        if (! Schema::hasTable('fleets')) {
            return;
        }

        DB::table('fleets')->where('id', $id)->delete();

        $this->loadFleets();
        $this->loadStats();

        session()->flash('success', 'Vehicle deleted successfully.');
    }

    public function loadFleets(): void
    {
        if (! Schema::hasTable('fleets')) {
            $this->fleets = [];
            return;
        }

        $query = DB::table('fleets')->orderByDesc('id');

        if (Schema::hasColumn('fleets', 'transporter_profile_id')) {
            $query->where('transporter_profile_id', $this->profile->id);
        }

        $this->fleets = $query->limit(50)->get()->map(fn ($item) => (array) $item)->toArray();
    }

    public function loadCategories(): void
    {
        $this->categories = Schema::hasTable('categories')
            ? DB::table('categories')->orderBy('id')->get()->map(fn ($item) => (array) $item)->toArray()
            : [];
    }

    public function loadBrands(): void
    {
        $this->brands = Schema::hasTable('brands')
            ? DB::table('brands')->orderBy('id')->get()->map(fn ($item) => (array) $item)->toArray()
            : [];
    }

    public function loadStats(): void
    {
        $this->vehiclesCount = count($this->fleets);
        $this->bookingsCount = 0;
        $this->driversCount = 0;
        $this->reviewsCount = 0;
        $this->incomeToday = 0;
    }

    public function calculateProfileCompletion(): void
    {
        $fields = [
            $this->partnerType,
            $this->companyName,
            $this->contactPerson,
            $this->mobile,
            $this->email,
            $this->aadhaarNumber,
            $this->panNumber,
            $this->officeAddress,
            $this->city,
            $this->state,
            $this->pincode,
            $this->profile->aadhaar_image ?? null,
            $this->profile->pan_image ?? null,
            $this->profile->company_document ?? null,
        ];

        $filled = collect($fields)->filter(fn ($value) => ! empty($value))->count();

        $this->profileCompletion = (int) round(($filled / count($fields)) * 100);
    }

    private function putFleet(array &$data, array $columns, $value): void
    {
        foreach ($columns as $column) {
            if (Schema::hasColumn('fleets', $column)) {
                $data[$column] = $value;
                return;
            }
        }
    }

    private function fleetColumn(array $columns): ?string
    {
        foreach ($columns as $column) {
            if (Schema::hasColumn('fleets', $column)) {
                return $column;
            }
        }

        return null;
    }

    public function logout()
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('partner.login');
    }

    public function render()
    {
        return view('livewire.partner-dashboard');
    }
}