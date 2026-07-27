<?php

namespace App\Models;

use App\Models\FleetManagement\TransporterProfile;
use App\Services\WhatsAppService;
use App\Support\DuraImage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class Vehicle extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | Service Types
    |--------------------------------------------------------------------------
    */

    public const SERVICE_TAXI = 'taxi';

    public const SERVICE_SELF_DRIVE = 'self_drive';

    public const SERVICE_BIKE_RENTAL = 'bike_rental';

    /*
    |--------------------------------------------------------------------------
    | Vehicle Types
    |--------------------------------------------------------------------------
    */

    public const TYPE_CAR = 'car';

    public const TYPE_BIKE = 'bike';

    public const TYPE_SCOOTER = 'scooter';

    /*
    |--------------------------------------------------------------------------
    | Bike Categories
    |--------------------------------------------------------------------------
    */

    public const BIKE_CATEGORY_COMMUTER = 'commuter';

    public const BIKE_CATEGORY_SPORTS = 'sports';

    public const BIKE_CATEGORY_CRUISER = 'cruiser';

    public const BIKE_CATEGORY_ADVENTURE = 'adventure';

    public const BIKE_CATEGORY_SCOOTER = 'scooter';

    public const BIKE_CATEGORY_ELECTRIC = 'electric';

    /*
    |--------------------------------------------------------------------------
    | Gear Types
    |--------------------------------------------------------------------------
    */

    public const GEAR_MANUAL = 'manual';

    public const GEAR_AUTOMATIC = 'automatic';

    /*
    |--------------------------------------------------------------------------
    | Verification Status
    |--------------------------------------------------------------------------
    */

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        // Ownership
        'user_id',
        'transporter_profile_id',

        // Legacy optional vehicle catalogue relation
        'product_id',

        // Service classification
        'service_type',
        'vehicle_type',

        // Vehicle identity
        'vehicle_number',
        'chassis_number',
        'engine_number',
        'insurance_number',

        // Owner information
        'owner_name',

        // Vehicle details
        'car_company_name',
        'model_name',
        'car_classification',
        'car_color',
        'manufacture_year',
        'fuel_type',
        'transmission',
        'seats',
        'bags',
        'insurance_company_name',

        // Rental pricing
        'hourly_price',
        'daily_price',
        'weekly_price',
        'monthly_price',
        'security_deposit',
        'minimum_booking_hours',
        'maximum_booking_hours',

        // Rental usage pricing
        'free_km',
        'extra_km_rate',
        'extra_hour_rate',

        // Bike Rental details
        'bike_category',
        'engine_cc',
        'gear_type',
        'helmet_available',
        'included_helmets',
        'maximum_helmets',
        'helmet_charge',
        'fuel_capacity',
        'mileage',

        // Documents
        'rc_image',
        'insurance_image',
        'polution_image',

        // Media IDs
        'front_media_id',
        'back_media_id',
        'interior_media_id',
        'rc_media_id',
        'insurance_media_id',
        'pollution_media_id',

        // Vehicle photos
        'front_image',
        'back_image',
        'interior_image',

        // Approval and status
        'verification_status',
        'rejection_reason',
        'is_active',
        'is_live',
        'is_verified',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'transporter_profile_id' => 'integer',
        'product_id' => 'integer',

        'manufacture_year' => 'integer',
        'seats' => 'integer',
        'bags' => 'integer',
        'engine_cc' => 'integer',

        'front_media_id' => 'integer',
        'back_media_id' => 'integer',
        'interior_media_id' => 'integer',
        'rc_media_id' => 'integer',
        'insurance_media_id' => 'integer',
        'pollution_media_id' => 'integer',

        'hourly_price' => 'decimal:2',
        'daily_price' => 'decimal:2',
        'weekly_price' => 'decimal:2',
        'monthly_price' => 'decimal:2',
        'security_deposit' => 'decimal:2',

        'free_km' => 'decimal:2',
        'extra_km_rate' => 'decimal:2',
        'extra_hour_rate' => 'decimal:2',
        'helmet_charge' => 'decimal:2',
        'fuel_capacity' => 'decimal:2',
        'mileage' => 'decimal:2',

        'minimum_booking_hours' => 'integer',
        'maximum_booking_hours' => 'integer',
        'included_helmets' => 'integer',
        'maximum_helmets' => 'integer',

        'helmet_available' => 'boolean',
        'is_active' => 'boolean',
        'is_live' => 'boolean',
        'is_verified' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Static Options
    |--------------------------------------------------------------------------
    */

    public static function serviceTypeOptions(): array
    {
        return [
            self::SERVICE_TAXI => 'Taxi With Driver',
            self::SERVICE_SELF_DRIVE => 'Self Drive Car',
            self::SERVICE_BIKE_RENTAL => 'Bike Rental',
        ];
    }

    public static function vehicleTypeOptions(): array
    {
        return [
            self::TYPE_CAR => 'Car',
            self::TYPE_BIKE => 'Bike',
            self::TYPE_SCOOTER => 'Scooter',
        ];
    }

    public static function bikeCategoryOptions(): array
    {
        return [
            self::BIKE_CATEGORY_COMMUTER => 'Commuter',
            self::BIKE_CATEGORY_SPORTS => 'Sports',
            self::BIKE_CATEGORY_CRUISER => 'Cruiser',
            self::BIKE_CATEGORY_ADVENTURE => 'Adventure',
            self::BIKE_CATEGORY_SCOOTER => 'Scooter',
            self::BIKE_CATEGORY_ELECTRIC => 'Electric',
        ];
    }

    public static function gearTypeOptions(): array
    {
        return [
            self::GEAR_MANUAL => 'Manual',
            self::GEAR_AUTOMATIC => 'Automatic',
        ];
    }

    public static function verificationStatusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Vehicle Number Normalization
    |--------------------------------------------------------------------------
    |
    | UP80 CT 1831
    | UP80-CT-1831
    | up80ct1831
    |
    | Database me sab UP80CT1831 ke roop me save honge.
    |
    */

    public function setVehicleNumberAttribute(?string $value): void
    {
        if (blank($value)) {
            $this->attributes['vehicle_number'] = null;

            return;
        }

        $this->attributes['vehicle_number'] = strtoupper(
            preg_replace('/[^A-Za-z0-9]/', '', trim($value))
        );
    }

    public function setChassisNumberAttribute(?string $value): void
    {
        $this->attributes['chassis_number'] = filled($value)
            ? strtoupper(preg_replace('/\s+/', '', trim($value)))
            : null;
    }

    public function setEngineNumberAttribute(?string $value): void
    {
        $this->attributes['engine_number'] = filled($value)
            ? strtoupper(preg_replace('/\s+/', '', trim($value)))
            : null;
    }

    public function setServiceTypeAttribute(?string $value): void
    {
        $serviceType = strtolower(trim((string) $value));

        $allowedTypes = array_keys(self::serviceTypeOptions());

        $this->attributes['service_type'] = in_array(
            $serviceType,
            $allowedTypes,
            true
        )
            ? $serviceType
            : self::SERVICE_TAXI;
    }

    public function setVehicleTypeAttribute(?string $value): void
    {
        $vehicleType = strtolower(trim((string) $value));

        $allowedTypes = array_keys(self::vehicleTypeOptions());

        $this->attributes['vehicle_type'] = in_array(
            $vehicleType,
            $allowedTypes,
            true
        )
            ? $vehicleType
            : self::TYPE_CAR;
    }

    public function setBikeCategoryAttribute(?string $value): void
    {
        if (blank($value)) {
            $this->attributes['bike_category'] = null;

            return;
        }

        $category = strtolower(trim($value));

        $allowedCategories = array_keys(self::bikeCategoryOptions());

        $this->attributes['bike_category'] = in_array(
            $category,
            $allowedCategories,
            true
        )
            ? $category
            : null;
    }

    public function setGearTypeAttribute(?string $value): void
    {
        if (blank($value)) {
            $this->attributes['gear_type'] = null;

            return;
        }

        $gearType = strtolower(trim($value));

        $allowedGearTypes = array_keys(self::gearTypeOptions());

        $this->attributes['gear_type'] = in_array(
            $gearType,
            $allowedGearTypes,
            true
        )
            ? $gearType
            : null;
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function frontMedia(): BelongsTo
    {
        return $this->belongsTo(
            AppMedia::class,
            'front_media_id'
        );
    }

    public function backMedia(): BelongsTo
    {
        return $this->belongsTo(
            AppMedia::class,
            'back_media_id'
        );
    }

    public function interiorMedia(): BelongsTo
    {
        return $this->belongsTo(
            AppMedia::class,
            'interior_media_id'
        );
    }

    public function rcMedia(): BelongsTo
    {
        return $this->belongsTo(
            AppMedia::class,
            'rc_media_id'
        );
    }

    public function insuranceMedia(): BelongsTo
    {
        return $this->belongsTo(
            AppMedia::class,
            'insurance_media_id'
        );
    }

    public function pollutionMedia(): BelongsTo
    {
        return $this->belongsTo(
            AppMedia::class,
            'pollution_media_id'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transporter(): BelongsTo
    {
        return $this->belongsTo(
            TransporterProfile::class,
            'transporter_profile_id'
        );
    }

    /**
     * Existing website compatibility ke liye legacy relation.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Self Drive aur Bike Rental booking engine dono vehicle_id use karenge.
     */
    public function selfDriveBookings(): HasMany
    {
        return $this->hasMany(
            SelfDriveBooking::class,
            'vehicle_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Vehicle Image URL Accessors
    |--------------------------------------------------------------------------
    */

    public function getFrontImageUrlAttribute(): ?string
    {
        $media = $this->relationLoaded('frontMedia')
            ? $this->getRelation('frontMedia')
            : $this->frontMedia;

        if ($media instanceof AppMedia) {
            return $media->medium_url
                ?: $media->large_url
                ?: $media->original_url
                ?: $media->url;
        }

        if (blank($this->front_image)) {
            return null;
        }

        return DuraImage::url($this->front_image);
    }

    public function getBackImageUrlAttribute(): ?string
    {
        $media = $this->relationLoaded('backMedia')
            ? $this->getRelation('backMedia')
            : $this->backMedia;

        if ($media instanceof AppMedia) {
            return $media->medium_url
                ?: $media->large_url
                ?: $media->original_url
                ?: $media->url;
        }

        if (blank($this->back_image)) {
            return null;
        }

        return DuraImage::url($this->back_image);
    }

    public function getInteriorImageUrlAttribute(): ?string
    {
        $media = $this->relationLoaded('interiorMedia')
            ? $this->getRelation('interiorMedia')
            : $this->interiorMedia;

        if ($media instanceof AppMedia) {
            return $media->medium_url
                ?: $media->large_url
                ?: $media->original_url
                ?: $media->url;
        }

        if (blank($this->interior_image)) {
            return null;
        }

        return DuraImage::url($this->interior_image);
    }

    public function getDisplayNameAttribute(): string
    {
        return static::getVehicleDisplayName($this);
    }

    public function getServiceTypeLabelAttribute(): string
    {
        return self::serviceTypeOptions()[$this->service_type]
            ?? ucfirst(str_replace('_', ' ', (string) $this->service_type));
    }

    public function getVehicleTypeLabelAttribute(): string
    {
        return self::vehicleTypeOptions()[$this->vehicle_type]
            ?? ucfirst((string) $this->vehicle_type);
    }

    public function getBikeCategoryLabelAttribute(): ?string
    {
        if (blank($this->bike_category)) {
            return null;
        }

        return self::bikeCategoryOptions()[$this->bike_category]
            ?? ucfirst(str_replace('_', ' ', $this->bike_category));
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeLive(Builder $query): Builder
    {
        return $query->where('is_live', true);
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where(
            'verification_status',
            self::STATUS_PENDING
        );
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where(
            'verification_status',
            self::STATUS_APPROVED
        );
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where(
            'verification_status',
            self::STATUS_REJECTED
        );
    }

    public function scopeTaxi(Builder $query): Builder
    {
        return $query->where(
            'service_type',
            self::SERVICE_TAXI
        );
    }

    public function scopeSelfDrive(Builder $query): Builder
    {
        return $query->where(
            'service_type',
            self::SERVICE_SELF_DRIVE
        );
    }

    public function scopeBikeRental(Builder $query): Builder
    {
        return $query->where(
            'service_type',
            self::SERVICE_BIKE_RENTAL
        );
    }

    public function scopeCars(Builder $query): Builder
    {
        return $query->where(
            'vehicle_type',
            self::TYPE_CAR
        );
    }

    public function scopeBikes(Builder $query): Builder
    {
        return $query->whereIn('vehicle_type', [
            self::TYPE_BIKE,
            self::TYPE_SCOOTER,
        ]);
    }

    public function scopeByServiceType(
        Builder $query,
        string $serviceType
    ): Builder {
        return $query->where('service_type', $serviceType);
    }

    public function scopeByVehicleType(
        Builder $query,
        string $vehicleType
    ): Builder {
        return $query->where('vehicle_type', $vehicleType);
    }

    public function scopeOwnedByPartner(
        Builder $query,
        int $transporterProfileId
    ): Builder {
        return $query->where(
            'transporter_profile_id',
            $transporterProfileId
        );
    }

    /**
     * Customer ko sirf approved aur active vehicles dikhengi.
     */
    public function scopeAvailableForCustomer(Builder $query): Builder
    {
        return $query
            ->where(
                'verification_status',
                self::STATUS_APPROVED
            )
            ->where('is_active', true);
    }

    /**
     * Rental customer listing ke liye approved, active, live aur verified.
     */
    public function scopeAvailableForRental(Builder $query): Builder
    {
        return $query
            ->whereIn('service_type', [
                self::SERVICE_SELF_DRIVE,
                self::SERVICE_BIKE_RENTAL,
            ])
            ->where(
                'verification_status',
                self::STATUS_APPROVED
            )
            ->where('is_active', true)
            ->where('is_live', true)
            ->where('is_verified', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Service Helpers
    |--------------------------------------------------------------------------
    */

    public function isTaxi(): bool
    {
        return $this->service_type === self::SERVICE_TAXI;
    }

    public function isSelfDrive(): bool
    {
        return $this->service_type === self::SERVICE_SELF_DRIVE;
    }

    public function isBikeRental(): bool
    {
        return $this->service_type === self::SERVICE_BIKE_RENTAL;
    }

    public function isRentalVehicle(): bool
    {
        return $this->isSelfDrive() || $this->isBikeRental();
    }

    public function isCar(): bool
    {
        return $this->vehicle_type === self::TYPE_CAR;
    }

    public function isBike(): bool
    {
        return in_array(
            $this->vehicle_type,
            [
                self::TYPE_BIKE,
                self::TYPE_SCOOTER,
            ],
            true
        );
    }

    public function isScooter(): bool
    {
        return $this->vehicle_type === self::TYPE_SCOOTER;
    }

    public function isElectricBike(): bool
    {
        return $this->isBikeRental()
            && $this->bike_category === self::BIKE_CATEGORY_ELECTRIC;
    }

    public function hasHelmet(): bool
    {
        return $this->isBikeRental()
            && (bool) $this->helmet_available;
    }

    public function hasUnlimitedKm(): bool
    {
        return (float) $this->free_km <= 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Status Helpers
    |--------------------------------------------------------------------------
    */

    public function isPending(): bool
    {
        return $this->verification_status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->verification_status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->verification_status === self::STATUS_REJECTED;
    }

    public function canBeShownToCustomer(): bool
    {
        return $this->isApproved()
            && (bool) $this->is_active;
    }

    public function canBeBookedForRental(): bool
    {
        return $this->isRentalVehicle()
            && $this->isApproved()
            && (bool) $this->is_active
            && (bool) $this->is_live
            && (bool) $this->is_verified;
    }

    /*
    |--------------------------------------------------------------------------
    | Rental Pricing Helpers
    |--------------------------------------------------------------------------
    */

    public function getHourlyRate(): float
    {
        return max(0, (float) $this->hourly_price);
    }

    public function getDailyRate(): float
    {
        return max(0, (float) $this->daily_price);
    }

    public function getWeeklyRate(): float
    {
        $weeklyPrice = (float) $this->weekly_price;

        if ($weeklyPrice > 0) {
            return $weeklyPrice;
        }

        $dailyPrice = $this->getDailyRate();

        if ($dailyPrice <= 0) {
            return 0;
        }

        // Default weekly discount: 20%.
        return round(($dailyPrice * 7) * 0.80, 2);
    }

    public function getMonthlyRate(): float
    {
        $monthlyPrice = (float) $this->monthly_price;

        if ($monthlyPrice > 0) {
            return $monthlyPrice;
        }

        $dailyPrice = $this->getDailyRate();

        if ($dailyPrice <= 0) {
            return 0;
        }

        // Default monthly discount: 30%.
        return round(($dailyPrice * 30) * 0.70, 2);
    }

    public function getSecurityDepositAmount(): float
    {
        return max(0, (float) $this->security_deposit);
    }

    public function getExtraKmRate(): float
    {
        return max(0, (float) $this->extra_km_rate);
    }

    public function getExtraHourRate(): float
    {
        return max(0, (float) $this->extra_hour_rate);
    }

    public function getHelmetChargeAmount(): float
    {
        return max(0, (float) $this->helmet_charge);
    }

    public function calculateHelmetCharge(int $requestedHelmets): float
    {
        if (! $this->isBikeRental() || ! $this->helmet_available) {
            return 0;
        }

        $requestedHelmets = max(0, $requestedHelmets);

        $maximumHelmets = max(
            0,
            (int) $this->maximum_helmets
        );

        $includedHelmets = max(
            0,
            (int) $this->included_helmets
        );

        if ($maximumHelmets > 0) {
            $requestedHelmets = min(
                $requestedHelmets,
                $maximumHelmets
            );
        }

        $chargeableHelmets = max(
            0,
            $requestedHelmets - $includedHelmets
        );

        return round(
            $chargeableHelmets * $this->getHelmetChargeAmount(),
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Model Events
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        static::creating(function (Vehicle $vehicle): void {
            if (blank($vehicle->verification_status)) {
                $vehicle->verification_status = self::STATUS_PENDING;
            }

            if (blank($vehicle->service_type)) {
                $vehicle->service_type = self::SERVICE_TAXI;
            }

            if (blank($vehicle->vehicle_type)) {
                $vehicle->vehicle_type = $vehicle->isBikeRental()
                    ? self::TYPE_BIKE
                    : self::TYPE_CAR;
            }

            if (is_null($vehicle->is_active)) {
                $vehicle->is_active = true;
            }

            if (is_null($vehicle->is_live)) {
                $vehicle->is_live = false;
            }

            if (is_null($vehicle->is_verified)) {
                $vehicle->is_verified = false;
            }

            if (blank($vehicle->minimum_booking_hours)) {
                $vehicle->minimum_booking_hours = 1;
            }

            if ($vehicle->isBikeRental()) {
                static::applyBikeRentalDefaults($vehicle);
            }

            /*
             * Transporter profile se legacy user_id automatically fill karo.
             */
            if (
                blank($vehicle->user_id)
                && filled($vehicle->transporter_profile_id)
            ) {
                $vehicle->user_id = TransporterProfile::query()
                    ->whereKey($vehicle->transporter_profile_id)
                    ->value('user_id');
            }
        });

        static::updating(function (Vehicle $vehicle): void {
            if (
                $vehicle->isDirty('verification_status')
                && $vehicle->verification_status !== self::STATUS_REJECTED
            ) {
                $vehicle->rejection_reason = null;
            }

            if (
                $vehicle->isDirty('service_type')
                || $vehicle->isDirty('vehicle_type')
            ) {
                if ($vehicle->isBikeRental()) {
                    static::applyBikeRentalDefaults($vehicle);
                } else {
                    $vehicle->bike_category = null;
                    $vehicle->engine_cc = null;
                    $vehicle->gear_type = null;
                    $vehicle->helmet_available = false;
                    $vehicle->included_helmets = 0;
                    $vehicle->maximum_helmets = 0;
                    $vehicle->helmet_charge = 0;
                }
            }

            if (
                $vehicle->isDirty('verification_status')
                && $vehicle->isApproved()
            ) {
                $vehicle->is_verified = true;
            }

            if (
                $vehicle->isDirty('verification_status')
                && ! $vehicle->isApproved()
            ) {
                $vehicle->is_live = false;
            }
        });

        static::created(function (Vehicle $vehicle): void {
            $vehicle->loadMissing([
                'user',
                'transporter',
                'product',
            ]);

            static::sendPartnerNotification($vehicle);
            static::sendAdminNotification($vehicle);
        });
    }

    protected static function applyBikeRentalDefaults(
        Vehicle $vehicle
    ): void {
        if (! in_array(
            $vehicle->vehicle_type,
            [
                self::TYPE_BIKE,
                self::TYPE_SCOOTER,
            ],
            true
        )) {
            $vehicle->vehicle_type = self::TYPE_BIKE;
        }

        if ((float) $vehicle->security_deposit <= 0) {
            $vehicle->security_deposit = 2000;
        }

        if (is_null($vehicle->helmet_available)) {
            $vehicle->helmet_available = false;
        }

        if (is_null($vehicle->included_helmets)) {
            $vehicle->included_helmets = 0;
        }

        if (
            is_null($vehicle->maximum_helmets)
            || (int) $vehicle->maximum_helmets <= 0
        ) {
            $vehicle->maximum_helmets = 2;
        }

        if ((float) $vehicle->helmet_charge <= 0) {
            $vehicle->helmet_charge = 100;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Notifications
    |--------------------------------------------------------------------------
    */

    protected static function sendPartnerNotification(
        Vehicle $vehicle
    ): void {
        $mobile = $vehicle->transporter?->mobile
            ?: $vehicle->user?->mobile;

        if (blank($mobile)) {
            return;
        }

        $vehicleName = static::getVehicleDisplayName($vehicle);
        $vehicleNumber = $vehicle->vehicle_number ?: 'Not provided';
        $serviceName = $vehicle->service_type_label;

        $message = "🚗 *Dura Cabs Vehicle Submission Received*\n\n";
        $message .= "Your {$serviceName} vehicle has been submitted successfully.\n\n";
        $message .= "*Service:* {$serviceName}\n";
        $message .= "*Vehicle:* {$vehicleName}\n";
        $message .= "*Registration:* {$vehicleNumber}\n";
        $message .= "*Approval Status:* Pending\n\n";
        $message .= "Our Admin team will verify your vehicle and documents. ";
        $message .= "The vehicle will be visible to customers only after approval.\n\n";
        $message .= "Thank you for partnering with *Dura Cabs*.";

        try {
            WhatsAppService::send($mobile, $message);
        } catch (\Throwable $exception) {
            Log::error(
                'Partner vehicle WhatsApp notification failed.',
                [
                    'vehicle_id' => $vehicle->id,
                    'service_type' => $vehicle->service_type,
                    'mobile' => $mobile,
                    'error' => $exception->getMessage(),
                ]
            );
        }
    }

    protected static function sendAdminNotification(
        Vehicle $vehicle
    ): void {
        $adminMobile = env('ADMIN_MOBILE');

        if (blank($adminMobile)) {
            return;
        }

        $partnerName = $vehicle->transporter?->company_name
            ?: $vehicle->user?->name
            ?: 'Partner';

        $partnerMobile = $vehicle->transporter?->mobile
            ?: $vehicle->user?->mobile
            ?: 'N/A';

        $partnerEmail = $vehicle->transporter?->email
            ?: $vehicle->user?->email
            ?: 'N/A';

        $vehicleName = static::getVehicleDisplayName($vehicle);
        $vehicleNumber = $vehicle->vehicle_number ?: 'N/A';
        $serviceName = $vehicle->service_type_label;

        $message = "🚘 *New Dura Cabs Vehicle Submitted*\n\n";
        $message .= "*Service:* {$serviceName}\n";
        $message .= "*Partner:* {$partnerName}\n";
        $message .= "*Mobile:* {$partnerMobile}\n";
        $message .= "*Email:* {$partnerEmail}\n";
        $message .= "*Vehicle:* {$vehicleName}\n";
        $message .= "*Registration:* {$vehicleNumber}\n";
        $message .= "*Status:* Pending Approval\n\n";
        $message .= "Please review the vehicle and documents in the Admin Panel.";

        try {
            WhatsAppService::send($adminMobile, $message);
        } catch (\Throwable $exception) {
            Log::error(
                'Admin vehicle WhatsApp notification failed.',
                [
                    'vehicle_id' => $vehicle->id,
                    'service_type' => $vehicle->service_type,
                    'admin_mobile' => $adminMobile,
                    'error' => $exception->getMessage(),
                ]
            );
        }
    }

    protected static function getVehicleDisplayName(
        Vehicle $vehicle
    ): string {
        $brand = trim((string) $vehicle->car_company_name);
        $model = trim((string) $vehicle->model_name);

        $vehicleName = trim("{$brand} {$model}");

        if (filled($vehicleName)) {
            return $vehicleName;
        }

        return $vehicle->product?->name ?: 'Vehicle';
    }
}