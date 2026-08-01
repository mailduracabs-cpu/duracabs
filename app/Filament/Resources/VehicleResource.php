<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehicleResource\Pages;
use App\Forms\Components\DuraImageUpload;
use App\Models\FleetManagement\TransporterProfile;
use App\Models\User;
use App\Models\Vehicle;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $activeNavigationIcon = 'heroicon-s-truck';

    protected static ?string $navigationLabel = 'Vehicles';

    protected static ?string $modelLabel = 'Vehicle';

    protected static ?string $pluralModelLabel = 'Vehicles';

    protected static ?string $navigationGroup = 'Fleet Management';
    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'vehicle_number';

    public static function form(Form $form): Form
    {
        return $form->schema([
            /*
            |--------------------------------------------------------------------------
            | Service Classification
            |--------------------------------------------------------------------------
            */

            Section::make('Service and Vehicle Type')
                ->compact()
                ->collapsible()
                ->description(
                    'Choose where this vehicle will be used. Form fields change automatically according to the selected service.'
                )
                ->icon('heroicon-o-squares-2x2')
                ->columns([
                    'default' => 1,
                    'md' => 2,
                    'xl' => 3,
                ])
                ->schema([
                    Select::make('service_type')
                        ->label('Service')
                        ->options(Vehicle::serviceTypeOptions())
                        ->default(Vehicle::SERVICE_SELF_DRIVE)
                        ->required()
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(
                            function (
                                mixed $state,
                                Forms\Set $set
                            ): void {
                                if (
                                    $state === Vehicle::SERVICE_BIKE_RENTAL
                                ) {
                                    $set(
                                        'vehicle_type',
                                        Vehicle::TYPE_BIKE
                                    );

                                    $set('seats', 2);
                                    $set('bags', 0);
                                    $set('security_deposit', 2000);
                                    $set('helmet_charge', 100);
                                    $set('maximum_helmets', 2);
                                    $set('minimum_booking_hours', 1);

                                    return;
                                }

                                $set(
                                    'vehicle_type',
                                    Vehicle::TYPE_CAR
                                );

                                if (
                                    $state === Vehicle::SERVICE_SELF_DRIVE
                                ) {
                                    $set(
                                        'minimum_booking_hours',
                                        24
                                    );
                                }
                            }
                        )
                        ->helperText(
                            'Taxi is with driver. Self Drive and Bike Rental are customer-driven rental services.'
                        ),

                    Select::make('vehicle_type')
                        ->label('Vehicle Type')
                        ->options(
                            fn (Forms\Get $get): array =>
                                $get('service_type')
                                    === Vehicle::SERVICE_BIKE_RENTAL
                                    ? [
                                        Vehicle::TYPE_BIKE => 'Bike',
                                        Vehicle::TYPE_SCOOTER => 'Scooter',
                                    ]
                                    : [
                                        Vehicle::TYPE_CAR => 'Car',
                                    ]
                        )
                        ->default(Vehicle::TYPE_CAR)
                        ->required()
                        ->native(false),

                    Select::make('bike_category')
                        ->label('Bike Category')
                        ->options(Vehicle::bikeCategoryOptions())
                        ->searchable()
                        ->native(false)
                        ->required(
                            fn (Forms\Get $get): bool =>
                                $get('service_type')
                                    === Vehicle::SERVICE_BIKE_RENTAL
                        )
                        ->visible(
                            fn (Forms\Get $get): bool =>
                                $get('service_type')
                                    === Vehicle::SERVICE_BIKE_RENTAL
                        ),
                ]),

            /*
            |--------------------------------------------------------------------------
            | Ownership and Status
            |--------------------------------------------------------------------------
            */

            Section::make('Ownership and Status')
                ->compact()
                ->collapsible()
                ->collapsed()
                ->description(
                    'Vehicle ownership, verification and customer availability settings.'
                )
                ->icon('heroicon-o-user-circle')
                ->columns([
                    'default' => 1,
                    'md' => 2,
                    'xl' => 4,
                ])
                ->schema([
                    Select::make('user_id')
                        ->label('Transporter')
                        ->options(
                            fn (): array =>
                                User::role('Transporter')
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all()
                        )
                        ->searchable()
                        ->preload()
                        ->required(
                            fn (): bool =>
                                ! static::isPartnerPanel()
                        )
                        ->visible(
                            fn (): bool =>
                                ! static::isPartnerPanel()
                        )
                        ->live()
                        ->afterStateUpdated(
                            function (
                                mixed $state,
                                Forms\Set $set
                            ): void {
                                if (blank($state)) {
                                    $set(
                                        'transporter_profile_id',
                                        null
                                    );

                                    return;
                                }

                                $profileId = TransporterProfile::query()
                                    ->where('user_id', $state)
                                    ->value('id');

                                $set(
                                    'transporter_profile_id',
                                    $profileId
                                );
                            }
                        ),

                    Hidden::make('transporter_profile_id'),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->disabled(
                            fn (): bool =>
                                static::isPartnerPanel()
                        )
                        ->dehydrated()
                        ->helperText(
                            'Inactive vehicles are hidden from customers.'
                        ),

                    Toggle::make('is_live')
                        ->label('Live for Booking')
                        ->default(false)
                        ->disabled(
                            fn (): bool =>
                                static::isPartnerPanel()
                        )
                        ->dehydrated()
                        ->helperText(
                            'Enable after pricing and availability are ready.'
                        ),

                    Toggle::make('is_verified')
                        ->label('Documents Verified')
                        ->default(false)
                        ->disabled(
                            fn (): bool =>
                                static::isPartnerPanel()
                        )
                        ->dehydrated()
                        ->helperText(
                            'Admin confirmation that documents are verified.'
                        ),

                    Select::make('verification_status')
                        ->label('Approval Status')
                        ->options(
                            Vehicle::verificationStatusOptions()
                        )
                        ->default(Vehicle::STATUS_PENDING)
                        ->native(false)
                        ->required()
                        ->live()
                        ->disabled(
                            fn (): bool =>
                                static::isPartnerPanel()
                        )
                        ->dehydrated()
                        ->visible(
                            fn (): bool =>
                                ! static::isPartnerPanel()
                        )
                        ->afterStateUpdated(
                            function (
                                mixed $state,
                                Forms\Set $set
                            ): void {
                                if (
                                    $state === Vehicle::STATUS_APPROVED
                                ) {
                                    $set('is_verified', true);

                                    return;
                                }

                                $set('is_live', false);
                            }
                        ),

                    Textarea::make('rejection_reason')
                        ->label('Rejection Reason')
                        ->rows(3)
                        ->maxLength(1000)
                        ->required(
                            fn (Forms\Get $get): bool =>
                                $get('verification_status')
                                    === Vehicle::STATUS_REJECTED
                        )
                        ->visible(
                            fn (Forms\Get $get): bool =>
                                ! static::isPartnerPanel()
                                && $get('verification_status')
                                    === Vehicle::STATUS_REJECTED
                        )
                        ->columnSpanFull(),
                ]),

            /*
            |--------------------------------------------------------------------------
            | Vehicle Identity
            |--------------------------------------------------------------------------
            */

            Section::make('Vehicle Identity')
                ->compact()
                ->collapsible()
                ->description(
                    'Enter vehicle registration and ownership details.'
                )
                ->icon('heroicon-o-identification')
                ->columns([
                    'default' => 1,
                    'md' => 2,
                    'xl' => 4,
                ])
                ->schema([
                    TextInput::make('vehicle_number')
                        ->label('Registration Number')
                        ->placeholder('UP80CT1831')
                        ->required()
                        ->maxLength(30)
                        ->unique(
                            table: 'vehicles',
                            column: 'vehicle_number',
                            ignoreRecord: true
                        )
                        ->helperText(
                            'Spaces and hyphens are removed automatically.'
                        ),

                    TextInput::make('chassis_number')
                        ->label('Chassis Number')
                        ->maxLength(100),

                    TextInput::make('engine_number')
                        ->label('Engine Number')
                        ->maxLength(100),

                    TextInput::make('insurance_number')
                        ->label('Insurance Number')
                        ->maxLength(100),

                    TextInput::make('owner_name')
                        ->label('Registered Owner Name')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('insurance_company_name')
                        ->label('Insurance Company')
                        ->maxLength(255),
                ]),

            /*
            |--------------------------------------------------------------------------
            | General Vehicle Details
            |--------------------------------------------------------------------------
            */

            Section::make('Vehicle Details')
                ->compact()
                ->collapsible()
                ->description(
                    'Information shown to customers while selecting the vehicle.'
                )
                ->icon('heroicon-o-information-circle')
                ->columns([
                    'default' => 1,
                    'md' => 2,
                    'xl' => 4,
                ])
                ->schema([
                    TextInput::make('car_company_name')
                        ->label('Brand')
                        ->placeholder(
                            fn (Forms\Get $get): string =>
                                $get('service_type')
                                    === Vehicle::SERVICE_BIKE_RENTAL
                                    ? 'Honda / TVS / Royal Enfield'
                                    : 'Maruti Suzuki'
                        )
                        ->required()
                        ->maxLength(100),

                    TextInput::make('model_name')
                        ->label('Model')
                        ->placeholder(
                            fn (Forms\Get $get): string =>
                                $get('service_type')
                                    === Vehicle::SERVICE_BIKE_RENTAL
                                    ? 'Activa 6G / Classic 350'
                                    : 'Swift Dzire'
                        )
                        ->required()
                        ->maxLength(100),

                    Select::make('car_classification')
                        ->label(
                            fn (Forms\Get $get): string =>
                                $get('service_type')
                                    === Vehicle::SERVICE_BIKE_RENTAL
                                    ? 'Body Classification'
                                    : 'Car Category'
                        )
                        ->options([
                            'hatchback' => 'Hatchback',
                            'sedan' => 'Sedan',
                            'suv' => 'SUV',
                            'muv' => 'MUV',
                            'luxury' => 'Luxury',
                            'van' => 'Van',
                            'commuter' => 'Commuter Bike',
                            'sports' => 'Sports Bike',
                            'cruiser' => 'Cruiser Bike',
                            'adventure' => 'Adventure Bike',
                            'scooter' => 'Scooter',
                            'electric' => 'Electric Two-Wheeler',
                            'other' => 'Other',
                        ])
                        ->searchable()
                        ->native(false),

                    TextInput::make('car_color')
                        ->label('Color')
                        ->maxLength(50),

                    TextInput::make('manufacture_year')
                        ->label('Manufacture Year')
                        ->numeric()
                        ->minValue(1990)
                        ->maxValue(
                            (int) now()->format('Y') + 1
                        ),

                    Select::make('fuel_type')
                        ->label('Fuel Type')
                        ->options([
                            'petrol' => 'Petrol',
                            'diesel' => 'Diesel',
                            'cng' => 'CNG',
                            'electric' => 'Electric',
                            'hybrid' => 'Hybrid',
                        ])
                        ->native(false)
                        ->searchable(),

                    Select::make('transmission')
                        ->label('Transmission')
                        ->options([
                            'manual' => 'Manual',
                            'automatic' => 'Automatic',
                            'amt' => 'AMT',
                            'cvt' => 'CVT',
                        ])
                        ->native(false)
                        ->visible(
                            fn (Forms\Get $get): bool =>
                                $get('service_type')
                                    !== Vehicle::SERVICE_BIKE_RENTAL
                        ),

                    Select::make('gear_type')
                        ->label('Gear Type')
                        ->options(Vehicle::gearTypeOptions())
                        ->native(false)
                        ->required(
                            fn (Forms\Get $get): bool =>
                                $get('service_type')
                                    === Vehicle::SERVICE_BIKE_RENTAL
                        )
                        ->visible(
                            fn (Forms\Get $get): bool =>
                                $get('service_type')
                                    === Vehicle::SERVICE_BIKE_RENTAL
                        ),

                    TextInput::make('seats')
                        ->label('Seats')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(20)
                        ->default(5),

                    TextInput::make('bags')
                        ->label('Luggage Bags')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(20)
                        ->default(2)
                        ->visible(
                            fn (Forms\Get $get): bool =>
                                $get('service_type')
                                    !== Vehicle::SERVICE_BIKE_RENTAL
                        ),
                ]),

            /*
            |--------------------------------------------------------------------------
            | Bike Specifications
            |--------------------------------------------------------------------------
            */

            Section::make('Bike Specifications')
                ->compact()
                ->collapsible()
                ->collapsed()
                ->description(
                    'Technical specifications and helmet configuration for Bike Rental.'
                )
                ->icon('heroicon-o-wrench-screwdriver')
                ->visible(
                    fn (Forms\Get $get): bool =>
                        $get('service_type')
                            === Vehicle::SERVICE_BIKE_RENTAL
                )
                ->columns([
                    'default' => 1,
                    'md' => 2,
                    'xl' => 4,
                ])
                ->schema([
                    TextInput::make('engine_cc')
                        ->label('Engine Capacity')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(3000)
                        ->suffix('CC')
                        ->required(
                            fn (Forms\Get $get): bool =>
                                $get('fuel_type') !== 'electric'
                        ),

                    TextInput::make('fuel_capacity')
                        ->label('Fuel Tank Capacity')
                        ->numeric()
                        ->minValue(0)
                        ->suffix('Litres')
                        ->visible(
                            fn (Forms\Get $get): bool =>
                                $get('fuel_type') !== 'electric'
                        ),

                    TextInput::make('mileage')
                        ->label('Expected Mileage')
                        ->numeric()
                        ->minValue(0)
                        ->suffix('km/l'),

                    Toggle::make('helmet_available')
                        ->label('Helmet Available')
                        ->default(false)
                        ->live(),

                    TextInput::make('included_helmets')
                        ->label('Free Helmets Included')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(2)
                        ->default(0)
                        ->visible(
                            fn (Forms\Get $get): bool =>
                                (bool) $get('helmet_available')
                        ),

                    TextInput::make('maximum_helmets')
                        ->label('Maximum Helmets')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(2)
                        ->default(2)
                        ->visible(
                            fn (Forms\Get $get): bool =>
                                (bool) $get('helmet_available')
                        ),

                    TextInput::make('helmet_charge')
                        ->label('Extra Helmet Charge')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('₹')
                        ->default(100)
                        ->visible(
                            fn (Forms\Get $get): bool =>
                                (bool) $get('helmet_available')
                        )
                        ->helperText(
                            'Charge for each helmet above the included quantity.'
                        ),
                ]),

            /*
            |--------------------------------------------------------------------------
            | Rental Pricing
            |--------------------------------------------------------------------------
            */

            Section::make('Pricing')
                ->compact()
                ->collapsible()
                ->icon('heroicon-o-banknotes')
                ->visible(
                    fn (Forms\Get $get): bool =>
                        in_array(
                            $get('service_type'),
                            [
                                Vehicle::SERVICE_SELF_DRIVE,
                                Vehicle::SERVICE_BIKE_RENTAL,
                            ],
                            true
                        )
                )
                ->columns([
                    'default' => 1,
                    'md' => 2,
                    'xl' => 4,
                ])
                ->schema([
                    TextInput::make('hourly_price')
                        ->label('Hourly Price')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('₹')
                        ->required()
                        ->live(debounce: 350)
                        ->afterStateUpdated(
                            function (
                                mixed $state,
                                Forms\Set $set
                            ): void {
                                $hourlyPrice = max(0, (float) $state);
                                $dailyPrice = round($hourlyPrice * 24, 2);

                                $set('daily_price', $dailyPrice);
                                $set(
                                    'weekly_price',
                                    round(($dailyPrice * 7) * 0.80, 2)
                                );
                                $set(
                                    'monthly_price',
                                    round(($dailyPrice * 30) * 0.70, 2)
                                );
                            }
                        ),

                    TextInput::make('daily_price')
                        ->label('24-Hour Price')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('₹')
                        ->required()
                        ->live(debounce: 350)
                        ->afterStateUpdated(
                            function (
                                mixed $state,
                                Forms\Set $set
                            ): void {
                                $dailyPrice = max(0, (float) $state);

                                $set(
                                    'weekly_price',
                                    round(($dailyPrice * 7) * 0.80, 2)
                                );
                                $set(
                                    'monthly_price',
                                    round(($dailyPrice * 30) * 0.70, 2)
                                );
                            }
                        )
                        ->helperText('Customer price for a complete 24-hour rental.'),

                    TextInput::make('commission_percentage')
                        ->label('Commission')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%')
                        ->default(30)
                        ->required()
                        ->live(debounce: 300),

                    Placeholder::make('vendor_hourly_preview')
                        ->label('Vendor')
                        ->content(
                            function (Forms\Get $get): string {
                                $customer = max(0, (float) $get('hourly_price'));
                                $commission = min(
                                    100,
                                    max(0, (float) ($get('commission_percentage') ?? 30))
                                );

                                return '₹' . number_format(
                                    $customer * (1 - ($commission / 100)),
                                    2
                                );
                            }
                        ),

                    Placeholder::make('admin_hourly_preview')
                        ->label('Admin')
                        ->content(
                            function (Forms\Get $get): string {
                                $customer = max(0, (float) $get('hourly_price'));
                                $commission = min(
                                    100,
                                    max(0, (float) ($get('commission_percentage') ?? 30))
                                );

                                return '₹' . number_format(
                                    $customer * ($commission / 100),
                                    2
                                );
                            }
                        ),

                    Placeholder::make('compact_price_summary')
                        ->label('')
                        ->columnSpanFull()
                        ->content(
                            function (Forms\Get $get): HtmlString {
                                $hourly = max(0, (float) $get('hourly_price'));
                                $commission = min(
                                    100,
                                    max(0, (float) ($get('commission_percentage') ?? 30))
                                );
                                $vendorFactor = 1 - ($commission / 100);

                                $daily = max(0, (float) $get('daily_price'));
                                $weekly = max(0, (float) $get('weekly_price'));
                                $monthly = max(0, (float) $get('monthly_price'));

                                $money = static fn (float $amount): string =>
                                    '₹' . number_format($amount, 0);

                                return new HtmlString(
                                    '<div class="grid grid-cols-3 gap-2 text-sm">'
                                    . '<div class="rounded-lg border border-gray-200 p-2 dark:border-gray-700">'
                                    . '<span class="font-medium">24H</span> '
                                    . '<span class="text-gray-500">C ' . $money($daily) . '</span> / '
                                    . '<span class="font-medium">V ' . $money($daily * $vendorFactor) . '</span>'
                                    . '</div>'
                                    . '<div class="rounded-lg border border-gray-200 p-2 dark:border-gray-700">'
                                    . '<span class="font-medium">7D</span> '
                                    . '<span class="text-gray-500">C ' . $money($weekly) . '</span> / '
                                    . '<span class="font-medium">V ' . $money($weekly * $vendorFactor) . '</span>'
                                    . '</div>'
                                    . '<div class="rounded-lg border border-gray-200 p-2 dark:border-gray-700">'
                                    . '<span class="font-medium">30D</span> '
                                    . '<span class="text-gray-500">C ' . $money($monthly) . '</span> / '
                                    . '<span class="font-medium">V ' . $money($monthly * $vendorFactor) . '</span>'
                                    . '</div>'
                                    . '</div>'
                                );
                            }
                        ),

                    TextInput::make('weekly_price')
                        ->label('7-Day Price')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('₹'),

                    TextInput::make('monthly_price')
                        ->label('30-Day Price')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('₹'),

                    TextInput::make('security_deposit')
                        ->label('Security Deposit')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('₹')
                        ->default(
                            fn (Forms\Get $get): int =>
                                $get('service_type')
                                    === Vehicle::SERVICE_BIKE_RENTAL
                                    ? 2000
                                    : 5000
                        )
                        ->required(),

                    TextInput::make('minimum_booking_hours')
                        ->label('Minimum Hours')
                        ->numeric()
                        ->minValue(1)
                        ->default(24)
                        ->required(),

                    TextInput::make('free_km')
                        ->label('Included KM (24H)')
                        ->numeric()
                        ->minValue(0)
                        ->suffix('km')
                        ->default(300),

                    TextInput::make('extra_km_rate')
                        ->label('Extra KM')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('₹')
                        ->suffix('/km')
                        ->default(7),

                    TextInput::make('maximum_booking_hours')
                        ->label('Unlimited KM Add-on')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('₹')
                        ->default(1500)
                        ->helperText('0 = unlimited KM option unavailable.'),

                    TextInput::make('extra_hour_rate')
                        ->label('Late Return')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('₹')
                        ->suffix('/hour')
                        ->default(300),
                ]),

            /*
            |--------------------------------------------------------------------------
            | Taxi Pricing Notice
            |--------------------------------------------------------------------------
            */

            Section::make('Taxi Pricing')
                ->compact()
                ->collapsible()
                ->collapsed()
                ->description(
                    'Taxi with driver fares are calculated by the backend route-pricing engine. Rental prices are not required here.'
                )
                ->icon('heroicon-o-information-circle')
                ->visible(
                    fn (Forms\Get $get): bool =>
                        $get('service_type')
                            === Vehicle::SERVICE_TAXI
                )
                ->schema([
                    Placeholder::make('taxi_pricing_information')
                        ->label('')
                        ->content(
                            new HtmlString(
                                '<div class="text-sm text-gray-600 dark:text-gray-300">
                                    Taxi fare will continue to use city, route,
                                    vehicle category, per-kilometre and package
                                    pricing configured in the existing taxi
                                    pricing system.
                                </div>'
                            )
                        ),
                ]),

            /*
            |--------------------------------------------------------------------------
            | Vehicle Photos
            |--------------------------------------------------------------------------
            */

            Section::make('Vehicle Photos')
                ->compact()
                ->collapsible()
                ->collapsed()
                ->description(
                    'Existing photos appear while editing. Upload a new file only to replace the current photo.'
                )
                ->icon('heroicon-o-photo')
                ->columns([
                    'default' => 1,
                    'lg' => 3,
                ])
                ->schema([
                    Placeholder::make('front_photo_preview')
                        ->label('Current Front Photo')
                        ->content(
                            fn (?Vehicle $record): HtmlString =>
                                static::vehicleImagePreview(
                                    url: $record?->front_image_url,
                                    alt: 'Front vehicle photo',
                                    emptyText: 'No front photo available',
                                )
                        )
                        ->visible(
                            fn (?Vehicle $record): bool =>
                                $record instanceof Vehicle
                        ),

                    Placeholder::make('back_photo_preview')
                        ->label('Current Back Photo')
                        ->content(
                            fn (?Vehicle $record): HtmlString =>
                                static::vehicleImagePreview(
                                    url: $record?->back_image_url,
                                    alt: 'Back vehicle photo',
                                    emptyText: 'No back photo available',
                                )
                        )
                        ->visible(
                            fn (?Vehicle $record): bool =>
                                $record instanceof Vehicle
                        ),

                    Placeholder::make('interior_photo_preview')
                        ->label(
                            fn (Forms\Get $get): string =>
                                $get('service_type')
                                    === Vehicle::SERVICE_BIKE_RENTAL
                                    ? 'Current Side / Dashboard Photo'
                                    : 'Current Interior Photo'
                        )
                        ->content(
                            fn (?Vehicle $record): HtmlString =>
                                static::vehicleImagePreview(
                                    url: $record?->interior_image_url,
                                    alt: 'Additional vehicle photo',
                                    emptyText: 'No additional photo available',
                                )
                        )
                        ->visible(
                            fn (?Vehicle $record): bool =>
                                $record instanceof Vehicle
                        ),

                    DuraImageUpload::vehicle(
                        name: 'front_upload',
                        module: 'vehicle-front',
                    )
                        ->panelLayout('compact')
                        ->imagePreviewHeight('56')
                        ->panelAspectRatio('5:1')
                        ->columnSpan(1)
                        ->extraAttributes([
                            'class' => 'max-w-xs',
                        ])
                        ->label(
                            fn (string $operation): string =>
                                $operation === 'edit'
                                    ? 'Replace Front Photo'
                                    : 'Front Photo'
                        )
                        ->required(
                            fn (string $operation): bool =>
                                $operation === 'create'
                        )
                        ->helperText(
                            fn (string $operation): string =>
                                $operation === 'edit'
                                    ? 'Leave empty to keep the existing front photo.'
                                    : 'Upload a clear front photo.'
                        ),

                    DuraImageUpload::vehicle(
                        name: 'back_upload',
                        module: 'vehicle-back',
                    )
                        ->panelLayout('compact')
                        ->imagePreviewHeight('56')
                        ->panelAspectRatio('5:1')
                        ->columnSpan(1)
                        ->extraAttributes([
                            'class' => 'max-w-xs',
                        ])
                        ->label(
                            fn (string $operation): string =>
                                $operation === 'edit'
                                    ? 'Replace Back Photo'
                                    : 'Back Photo'
                        )
                        ->helperText(
                            fn (string $operation): string =>
                                $operation === 'edit'
                                    ? 'Leave empty to keep the existing back photo.'
                                    : 'Upload a clear back photo.'
                        ),

                    DuraImageUpload::vehicle(
                        name: 'interior_upload',
                        module: 'vehicle-interior',
                    )
                        ->panelLayout('compact')
                        ->imagePreviewHeight('56')
                        ->panelAspectRatio('5:1')
                        ->columnSpan(1)
                        ->extraAttributes([
                            'class' => 'max-w-xs',
                        ])
                        ->label(
                            function (
                                string $operation,
                                Forms\Get $get
                            ): string {
                                $photoName = $get('service_type')
                                    === Vehicle::SERVICE_BIKE_RENTAL
                                    ? 'Side / Dashboard Photo'
                                    : 'Interior Photo';

                                return $operation === 'edit'
                                    ? "Replace {$photoName}"
                                    : $photoName;
                            }
                        )
                        ->helperText(
                            fn (string $operation): string =>
                                $operation === 'edit'
                                    ? 'Leave empty to keep the existing photo.'
                                    : 'Upload an additional vehicle photo.'
                        ),

                    Hidden::make('front_media_id'),

                    Hidden::make('back_media_id'),

                    Hidden::make('interior_media_id'),
                ]),

            /*
            |--------------------------------------------------------------------------
            | Documents
            |--------------------------------------------------------------------------
            */

            Section::make('Vehicle Documents')
                ->compact()
                ->collapsible()
                ->collapsed()
                ->description(
                    'Upload clear vehicle documents. Files are processed and stored by the existing media system.'
                )
                ->icon('heroicon-o-document-text')
                ->columns([
                    'default' => 1,
                    'lg' => 3,
                ])
                ->schema([
                    DuraImageUpload::document(
                        name: 'rc_upload',
                        module: 'vehicle-rc',
                    )
                        ->panelLayout('compact')
                        ->imagePreviewHeight('56')
                        ->panelAspectRatio('5:1')
                        ->columnSpan(1)
                        ->label('RC Document')
                        ->required(
                            fn (string $operation): bool =>
                                $operation === 'create'
                        ),

                    Hidden::make('rc_media_id'),

                    DuraImageUpload::document(
                        name: 'insurance_upload',
                        module: 'vehicle-insurance',
                    )
                        ->panelLayout('compact')
                        ->imagePreviewHeight('56')
                        ->panelAspectRatio('5:1')
                        ->columnSpan(1)
                        ->label('Insurance Document')
                        ->required(
                            fn (string $operation): bool =>
                                $operation === 'create'
                        ),

                    Hidden::make('insurance_media_id'),

                    DuraImageUpload::document(
                        name: 'pollution_upload',
                        module: 'vehicle-puc',
                    )
                        ->panelLayout('compact')
                        ->imagePreviewHeight('56')
                        ->panelAspectRatio('5:1')
                        ->columnSpan(1)
                        ->label('PUC / Pollution Document'),

                    Hidden::make('pollution_media_id'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make(
                    'front_image_url'
                )
                    ->label('Vehicle')
                    ->square()
                    ->size(70)
                    ->defaultImageUrl(
                        asset('images/no-image.png')
                    ),

                Tables\Columns\TextColumn::make(
                    'vehicle_number'
                )
                    ->label('Registration')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make(
                    'display_name'
                )
                    ->label('Vehicle')
                    ->searchable([
                        'car_company_name',
                        'model_name',
                    ]),

                Tables\Columns\TextColumn::make(
                    'service_type'
                )
                    ->label('Service')
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string =>
                            Vehicle::serviceTypeOptions()[$state]
                            ?? ucfirst(
                                str_replace(
                                    '_',
                                    ' ',
                                    (string) $state
                                )
                            )
                    )
                    ->color(
                        fn (?string $state): string =>
                            match ($state) {
                                Vehicle::SERVICE_TAXI => 'info',
                                Vehicle::SERVICE_BIKE_RENTAL => 'warning',
                                default => 'success',
                            }
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make(
                    'vehicle_type'
                )
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string =>
                            Vehicle::vehicleTypeOptions()[$state]
                            ?? ucfirst((string) $state)
                    )
                    ->toggleable(),

                Tables\Columns\TextColumn::make(
                    'transporter.user.name'
                )
                    ->label('Transporter')
                    ->placeholder('Not assigned')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make(
                    'car_classification'
                )
                    ->label('Category')
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string =>
                            filled($state)
                                ? ucfirst(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $state
                                    )
                                )
                                : 'Not set'
                    )
                    ->toggleable(),

                Tables\Columns\TextColumn::make(
                    'daily_price'
                )
                    ->label('24-Hour Price')
                    ->money('INR')
                    ->sortable(),

                Tables\Columns\TextColumn::make(
                    'commission_percentage'
                )
                    ->label('Commission')
                    ->suffix('%')
                    ->placeholder('30%')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make(
                    'security_deposit'
                )
                    ->label('Security')
                    ->money('INR')
                    ->sortable()
                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),

                Tables\Columns\TextColumn::make(
                    'verification_status'
                )
                    ->label('Approval')
                    ->badge()
                    ->color(
                        fn (?string $state): string =>
                            match ($state) {
                                Vehicle::STATUS_APPROVED => 'success',
                                Vehicle::STATUS_REJECTED => 'danger',
                                default => 'warning',
                            }
                    )
                    ->formatStateUsing(
                        fn (?string $state): string =>
                            ucfirst((string) $state)
                    ),

                Tables\Columns\IconColumn::make('is_verified')
                    ->label('Verified')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_live')
                    ->label('Live')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make(
                    'service_type'
                )
                    ->label('Service')
                    ->options(
                        Vehicle::serviceTypeOptions()
                    ),

                Tables\Filters\SelectFilter::make(
                    'vehicle_type'
                )
                    ->label('Vehicle Type')
                    ->options(
                        Vehicle::vehicleTypeOptions()
                    ),

                Tables\Filters\SelectFilter::make(
                    'verification_status'
                )
                    ->label('Approval Status')
                    ->options(
                        Vehicle::verificationStatusOptions()
                    ),

                Tables\Filters\SelectFilter::make(
                    'fuel_type'
                )
                    ->options([
                        'petrol' => 'Petrol',
                        'diesel' => 'Diesel',
                        'cng' => 'CNG',
                        'electric' => 'Electric',
                        'hybrid' => 'Hybrid',
                    ]),

                Tables\Filters\TernaryFilter::make('is_live')
                    ->label('Live for Booking'),

                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label('Documents Verified'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make(),

                Tables\Actions\DeleteAction::make()
                    ->visible(
                        fn (Vehicle $record): bool =>
                            ! static::isPartnerPanel()
                            || $record->verification_status
                                !== Vehicle::STATUS_APPROVED
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(
                            fn (): bool =>
                                ! static::isPartnerPanel()
                        ),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehicles::route('/'),

            'create' => Pages\CreateVehicle::route(
                '/create'
            ),

            'edit' => Pages\EditVehicle::route(
                '/{record}/edit'
            ),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'user',
                'transporter',
                'frontMedia',
                'backMedia',
                'interiorMedia',
            ]);

        if (! static::isPartnerPanel()) {
            return $query;
        }

        $profileId = static::getCurrentPartnerProfileId();

        return $query->where(
            function (Builder $builder) use (
                $profileId
            ): void {
                $builder->where(
                    'user_id',
                    auth()->id()
                );

                if ($profileId !== null) {
                    $builder->orWhere(
                        'transporter_profile_id',
                        $profileId
                    );
                }
            }
        );
    }

    public static function isPartnerPanel(): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        if (
            method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole([
                'Admin',
                'admin',
                'Super Admin',
                'super_admin',
                'super-admin',
            ])
        ) {
            return false;
        }

        if (method_exists($user, 'hasAnyRole')) {
            return $user->hasAnyRole([
                'Transporter',
                'transporter',
                'Vendor',
                'vendor',
                'Partner',
                'partner',
            ]);
        }

        return true;
    }

    public static function getCurrentPartnerProfileId(): ?int
    {
        $userId = auth()->id();

        if ($userId === null) {
            return null;
        }

        $profileId = TransporterProfile::query()
            ->where('user_id', $userId)
            ->value('id');

        return $profileId !== null
            ? (int) $profileId
            : null;
    }

    private static function vehicleImagePreview(
        ?string $url,
        string $alt,
        string $emptyText,
    ): HtmlString {
        if (blank($url)) {
            $safeEmptyText = e($emptyText);

            return new HtmlString(
                <<<HTML
                <div style="
                    width: 100%;
                    height: 220px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                    border: 1px dashed #d1d5db;
                    border-radius: 12px;
                    background: #f9fafb;
                    color: #6b7280;
                    text-align: center;
                ">
                    {$safeEmptyText}
                </div>
                HTML
            );
        }

        $safeUrl = e($url);
        $safeAlt = e($alt);

        return new HtmlString(
            <<<HTML
            <div style="
                width: 100%;
                height: 220px;
                overflow: hidden;
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                background: #f9fafb;
            ">
                <img
                    src="{$safeUrl}"
                    alt="{$safeAlt}"
                    style="
                        display: block;
                        width: 100%;
                        height: 100%;
                        object-fit: contain;
                    "
                >
            </div>
            HTML
        );
    }
}