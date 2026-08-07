<?php

namespace App\Filament\Resources;

use App\Filament\Imports\ProductImporter;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers\LinksRelationManager;
use App\Forms\Components\DuraImageUpload;
use App\Forms\Components\DuraSeo;
use App\Forms\Components\DuraSeoAiWriter;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Vehicle;
use App\SEO\Services\SeoAnalysisService;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ImportAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationLabel = 'Content Writer';

    protected static ?string $modelLabel = 'Content';

    protected static ?string $pluralModelLabel = 'Content Writer';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()
                    ->schema([
                        Section::make('SEO Page')
                            ->compact()
                            ->schema([
                                Grid::make([
                                    'default' => 1,
                                    'md' => 3,
                                ])
                                    ->schema([
                                        Select::make('ride_type')
                                            ->label('Page Type')
                                            ->options([
                                                'one_way' => 'Route — One Way',
                                                'return' => 'Route — Round Trip',
                                                'local' => 'Route — Local Package',
                                                'bike_rental' => 'Bike Rental',
                                                'self_drive' => 'Self Drive',
                                            ])
                                            ->default('one_way')
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(
                                                function (?string $state, Set $set): void {
                                                    if ($state !== 'self_drive') {
                                                        $set('vehicle_id', null);
                                                    }
                                                },
                                            ),

                                        TextInput::make('name')
                                            ->label('Page Name')
                                            ->placeholder('Agra to Delhi Cab')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(
                                                function (
                                                    string $operation,
                                                    ?string $state,
                                                    Set $set,
                                                ): void {
                                                    if ($operation !== 'create') {
                                                        return;
                                                    }

                                                    $set(
                                                        'slug',
                                                        Str::slug((string) $state),
                                                    );
                                                },
                                            ),

                                        TextInput::make('slug')
                                            ->label('Slug')
                                            ->helperText(
                                                'Existing slug is locked to protect old URLs and SEO.',
                                            )
                                            ->required()
                                            ->maxLength(255)
                                            ->disabled(
                                                fn (string $operation): bool =>
                                                    $operation === 'edit',
                                            )
                                            ->dehydrated()
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(
                                                function (
                                                    ?string $state,
                                                    Get $get,
                                                    Set $set,
                                                ): void {
                                                    $set(
                                                        'seo_public_url',
                                                        static::previewProductUrl(
                                                            (string) $get('ride_type'),
                                                            (string) $state,
                                                        ),
                                                    );
                                                },
                                            )
                                            ->unique(
                                                Product::class,
                                                'slug',
                                                ignoreRecord: true,
                                            ),

                                        Placeholder::make('public_url_preview')
                                            ->label('Public URL')
                                            ->content(
                                                fn (Get $get): string =>
                                                    static::previewProductUrl(
                                                        (string) $get('ride_type'),
                                                        (string) $get('slug'),
                                                    ),
                                            )
                                            ->helperText(
                                                'New pages use the selected page type. Existing ranked slugs are never changed automatically.',
                                            )
                                            ->columnSpanFull(),

                                        Hidden::make('seo_public_url')
                                            ->default(
                                                fn (?Product $record): string =>
                                                    $record?->public_url
                                                    ?? static::previewProductUrl(
                                                        'one_way',
                                                        '',
                                                    ),
                                            )
                                            ->dehydrated(false),
                                    ]),

                                Grid::make([
                                    'default' => 1,
                                    'md' => 3,
                                ])
                                    ->schema([
                                        Select::make('brand_id')
                                            ->label(
                                                fn (Get $get): string => match (
                                                    $get('ride_type')
                                                ) {
                                                    'one_way',
                                                    'return' => 'Start City',
                                                    default => 'City',
                                                },
                                            )
                                            ->relationship('brand', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(
                                                function (
                                                    mixed $state,
                                                    Get $get,
                                                    Set $set,
                                                ): void {
                                                    if ($get('ride_type') === 'self_drive') {
                                                        $set('vehicle_id', null);
                                                    }
                                                },
                                            ),

                                        Select::make('booking_to')
                                            ->label('Destination City')
                                            ->relationship('brand', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->visible(
                                                fn (Get $get): bool =>
                                                    $get('ride_type') === 'one_way',
                                            )
                                            ->required(
                                                fn (Get $get): bool =>
                                                    $get('ride_type') === 'one_way',
                                            ),

                                        Select::make('plan')
                                            ->label('Local Package')
                                            ->options([
                                                '4 Hours / 40 KM' => '4 Hours / 40 KM',
                                                '4 Hours / 80 KM' => '4 Hours / 80 KM',
                                                '8 Hours / 80 KM' => '8 Hours / 80 KM',
                                                '12 Hours / 120 KM' => '12 Hours / 120 KM',
                                            ])
                                            ->visible(
                                                fn (Get $get): bool =>
                                                    $get('ride_type') === 'local',
                                            )
                                            ->required(
                                                fn (Get $get): bool =>
                                                    $get('ride_type') === 'local',
                                            ),
                                    ]),
                            ]),

                        Section::make('Self Drive Vehicle')
                            ->description(
                                'Select the exact approved Self Drive vehicle. Search by brand, model, registration number, owner or transporter.',
                            )
                            ->compact()
                            ->visible(
                                fn (Get $get): bool =>
                                    $get('ride_type') === 'self_drive',
                            )
                            ->schema([
                                Select::make('vehicle_id')
                                    ->label('Self Drive Vehicle')
                                    ->placeholder(
                                        fn (Get $get): string => filled($get('brand_id'))
                                            ? 'Select or search a vehicle for this city'
                                            : 'Select City first',
                                    )
                                    ->options(
                                        fn (Get $get): array =>
                                            static::selfDriveVehicleOptions(
                                                $get('brand_id'),
                                            ),
                                    )
                                    ->searchable()
                                    ->native(false)
                                    ->disabled(
                                        fn (Get $get): bool => blank($get('brand_id')),
                                    )
                                    ->required(
                                        fn (Get $get): bool =>
                                            $get('ride_type') === 'self_drive',
                                    )
                                    ->getSearchResultsUsing(
                                        fn (string $search, Get $get): array =>
                                            static::searchSelfDriveVehicles(
                                                $search,
                                                $get('brand_id'),
                                            ),
                                    )
                                    ->getOptionLabelUsing(
                                        fn (mixed $value): ?string =>
                                            static::getVehicleOptionLabel($value),
                                    )
                                    ->live()
                                    ->afterStateUpdated(
                                        function (mixed $state, Set $set): void {
                                            static::fillProductFromVehicle(
                                                $state,
                                                $set,
                                            );
                                        },
                                    )
                                    ->helperText(
                                        fn (Get $get): string => static::vehicleSelectHelperText(
                                            $get('brand_id'),
                                        ),
                                    ),

                                Placeholder::make('self_drive_vehicle_city_status')
                                    ->label('City Vehicle Status')
                                    ->content(
                                        fn (Get $get): string => static::vehicleCityStatus(
                                            $get('brand_id'),
                                        ),
                                    )
                                    ->visible(
                                        fn (Get $get): bool => filled($get('brand_id')),
                                    ),

                                Grid::make([
                                    'default' => 1,
                                    'md' => 2,
                                ])
                                    ->schema([
                                        Placeholder::make('selected_vehicle_price')
                                            ->label('Hourly Price')
                                            ->content(
                                                function (Get $get): string {
                                                    $vehicle = static::findVehicle(
                                                        $get('vehicle_id'),
                                                    );

                                                    return $vehicle
                                                        ? '₹' . number_format(
                                                            (float) $vehicle->hourly_price,
                                                            2,
                                                        ) . ' / hour'
                                                        : 'Select a vehicle';
                                                },
                                            ),

                                        Placeholder::make('selected_vehicle_details')
                                            ->label('Selected Vehicle')
                                            ->content(
                                                function (Get $get): string {
                                                    $vehicle = static::findVehicle(
                                                        $get('vehicle_id'),
                                                    );

                                                    if (! $vehicle) {
                                                        return 'No vehicle selected';
                                                    }

                                                    $parts = array_filter([
                                                        static::vehicleName($vehicle),
                                                        $vehicle->vehicle_number,
                                                        $vehicle->fuel_type
                                                            ? Str::headline(
                                                                (string) $vehicle->fuel_type,
                                                            )
                                                            : null,
                                                        $vehicle->transmission
                                                            ? Str::headline(
                                                                (string) $vehicle->transmission,
                                                            )
                                                            : null,
                                                    ]);

                                                    return implode(' | ', $parts);
                                                },
                                            ),
                                    ]),
                            ]),

                        Section::make('Content')
                            ->compact()
                            ->schema([
                                RichEditor::make('description')
                                    ->label('Page Content')
                                    ->columnSpanFull()
                                    ->fileAttachmentsDirectory('products')
                                    ->live(debounce: 1000)
                                    ->toolbarButtons([
                                        'bold',
                                        'italic',
                                        'underline',
                                        'link',
                                        'h2',
                                        'h3',
                                        'bulletList',
                                        'orderedList',
                                        'blockquote',
                                        'undo',
                                        'redo',
                                    ]),
                            ]),

                        DuraSeoAiWriter::make()
                            ->collapsed(),

                        DuraSeo::make()
                            ->collapsed(),
                    ])
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 2,
                    ]),

                Group::make()
                    ->schema([
                        Section::make('Image')
                            ->compact()
                            ->schema([
                                DuraImageUpload::make(
                                    'images',
                                    'products',
                                )
                                    ->multiple()
                                    ->maxFiles(3)
                                    ->reorderable()
                                    ->imagePreviewHeight('90')
                                    ->panelLayout('compact')
                                    ->helperText(
                                        'Small banner/gallery preview. First image is primary.',
                                    ),
                            ]),

                        Section::make('Publishing')
                            ->compact()
                            ->columns(2)
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),

                                Toggle::make('is_featured')
                                    ->label('Featured')
                                    ->default(false),

                                Toggle::make('in_stock')
                                    ->label('Bookable')
                                    ->default(true),

                                Toggle::make('on_sale')
                                    ->label('SEO Highlight')
                                    ->default(true),
                            ]),

                        Section::make('Legacy Fallback')
                            ->description(
                                'Old frontend compatibility. For Self Drive, selecting a vehicle automatically fills the nearest matching category and hourly fallback price.',
                            )
                            ->collapsed()
                            ->compact()
                            ->schema([
                                Select::make('category_id')
                                    ->label('Fallback Cab Category')
                                    ->options(
                                        fn (): array => Category::query()
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                            ->all(),
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('price')
                                            ->label(
                                                fn (Get $get): string =>
                                                    $get('ride_type') === 'self_drive'
                                                        ? 'Fallback Hourly Price'
                                                        : 'Fallback Price',
                                            )
                                            ->numeric()
                                            ->prefix('₹')
                                            ->default(1)
                                            ->required(),

                                        TextInput::make('max_price')
                                            ->label('Fallback Max')
                                            ->numeric()
                                            ->prefix('₹')
                                            ->default(1)
                                            ->required(),
                                    ]),
                            ]),

                        Hidden::make('km_limit')
                            ->default(0),

                        Hidden::make('hr_limit')
                            ->default(0),

                        Hidden::make('extra_km_charge')
                            ->default(0),

                        Hidden::make('extra_hr_charge')
                            ->default(0),

                        Hidden::make('toll_tax')
                            ->default(0),

                        Hidden::make('border_tax')
                            ->default(0),

                        Hidden::make('driver_allowances')
                            ->default(0),
                    ])
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 1,
                    ]),
            ])
            ->columns([
                'default' => 1,
                'lg' => 3,
            ]);
    }

    /**
     * @return array<int, string>
     */
    protected static function selfDriveVehicleOptions(mixed $brandId): array
    {
        return static::searchSelfDriveVehicles('', $brandId);
    }

    /**
     * @return array<int, string>
     */
    protected static function searchSelfDriveVehicles(
        string $search,
        mixed $brandId,
    ): array {
        $search = trim($search);
        $cityTerms = static::citySearchTerms($brandId);

        if ($cityTerms === []) {
            return [];
        }

        return Vehicle::query()
            ->with([
                'user:id,name',
                'transporter',
            ])
            ->where('service_type', Vehicle::SERVICE_SELF_DRIVE)
            ->where('verification_status', Vehicle::STATUS_APPROVED)
            ->where('is_active', true)
            ->where('is_live', true)
            ->where('is_verified', true)
            ->whereHas(
                'transporter',
                function (Builder $transporterQuery) use ($cityTerms): void {
                    $transporterQuery->where(
                        function (Builder $locationQuery) use ($cityTerms): void {
                            foreach ($cityTerms as $term) {
                                $locationQuery
                                    ->orWhereRaw(
                                        'LOWER(TRIM(city)) = ?',
                                        [Str::lower($term)],
                                    )
                                    ->orWhere(
                                        'city',
                                        'like',
                                        "%{$term}%",
                                    )
                                    ->orWhere(
                                        'office_address',
                                        'like',
                                        "%{$term}%",
                                    );
                            }
                        },
                    );
                },
            )
            ->when(
                filled($search),
                function (Builder $query) use ($search): void {
                    $normalisedVehicleNumber = strtoupper(
                        preg_replace('/[^A-Za-z0-9]/', '', $search),
                    );

                    $query->where(
                        function (Builder $vehicleQuery) use (
                            $search,
                            $normalisedVehicleNumber,
                        ): void {
                            $vehicleQuery
                                ->where(
                                    'car_company_name',
                                    'like',
                                    "%{$search}%",
                                )
                                ->orWhere(
                                    'model_name',
                                    'like',
                                    "%{$search}%",
                                )
                                ->orWhere(
                                    'vehicle_number',
                                    'like',
                                    "%{$search}%",
                                )
                                ->orWhere(
                                    'owner_name',
                                    'like',
                                    "%{$search}%",
                                )
                                ->orWhereHas(
                                    'user',
                                    fn (Builder $userQuery): Builder =>
                                        $userQuery->where(
                                            'name',
                                            'like',
                                            "%{$search}%",
                                        ),
                                )
                                ->orWhereHas(
                                    'transporter',
                                    function (Builder $transporterQuery) use (
                                        $search,
                                    ): void {
                                        $transporterQuery->where(
                                            function (Builder $detailQuery) use (
                                                $search,
                                            ): void {
                                                $detailQuery
                                                    ->where(
                                                        'company_name',
                                                        'like',
                                                        "%{$search}%",
                                                    )
                                                    ->orWhere(
                                                        'city',
                                                        'like',
                                                        "%{$search}%",
                                                    )
                                                    ->orWhere(
                                                        'office_address',
                                                        'like',
                                                        "%{$search}%",
                                                    );
                                            },
                                        );
                                    });

                            if (filled($normalisedVehicleNumber)) {
                                $vehicleQuery->orWhere(
                                    'vehicle_number',
                                    'like',
                                    "%{$normalisedVehicleNumber}%",
                                );
                            }
                        },
                    );
                },
            )
            ->orderBy('car_company_name')
            ->orderBy('model_name')
            ->limit(100)
            ->get()
            ->mapWithKeys(
                fn (Vehicle $vehicle): array => [
                    $vehicle->id => static::formatVehicleOption($vehicle),
                ],
            )
            ->all();
    }

    /**
     * @return array<int, string>
     */
    protected static function citySearchTerms(mixed $brandId): array
    {
        if (blank($brandId) || ! is_numeric($brandId)) {
            return [];
        }

        $brandName = trim(
            (string) Brand::query()
                ->whereKey((int) $brandId)
                ->value('name'),
        );

        if ($brandName === '') {
            return [];
        }

        $shortCity = trim((string) Str::before($brandName, ','));

        return collect([
            $brandName,
            $shortCity,
        ])
            ->filter(fn (string $value): bool => $value !== '')
            ->unique(fn (string $value): string => Str::lower($value))
            ->values()
            ->all();
    }

    protected static function selectedCityName(mixed $brandId): ?string
    {
        if (blank($brandId) || ! is_numeric($brandId)) {
            return null;
        }

        $name = Brand::query()
            ->whereKey((int) $brandId)
            ->value('name');

        return filled($name)
            ? trim((string) $name)
            : null;
    }

    protected static function vehicleSelectHelperText(mixed $brandId): string
    {
        $cityName = static::selectedCityName($brandId);

        if (! $cityName) {
            return 'Pehle City select karein. Us city ke approved, active, live aur verified Self Drive vehicles hi dikhaye jayenge.';
        }

        return "Only approved, active, live and verified Self Drive vehicles for {$cityName} are shown.";
    }

    protected static function vehicleCityStatus(mixed $brandId): string
    {
        $cityName = static::selectedCityName($brandId);

        if (! $cityName) {
            return 'Select City first';
        }

        $count = count(static::selfDriveVehicleOptions($brandId));

        if ($count === 0) {
            return "No Self Drive Vehicle available in {$cityName}";
        }

        return $count === 1
            ? "1 Self Drive Vehicle available in {$cityName}"
            : "{$count} Self Drive Vehicles available in {$cityName}";
    }

    protected static function getVehicleOptionLabel(mixed $value): ?string
    {
        $vehicle = static::findVehicle($value);

        return $vehicle
            ? static::formatVehicleOption($vehicle)
            : null;
    }

    protected static function findVehicle(mixed $value): ?Vehicle
    {
        if (blank($value) || ! is_numeric($value)) {
            return null;
        }

        return Vehicle::query()
            ->with([
                'user:id,name',
                'transporter',
            ])
            ->find((int) $value);
    }

    protected static function formatVehicleOption(Vehicle $vehicle): string
    {
        $vehicleName = static::vehicleName($vehicle);

        $registration = filled($vehicle->vehicle_number)
            ? $vehicle->vehicle_number
            : 'No registration';

        $transporter = $vehicle->transporter?->company_name
            ?: $vehicle->user?->name
            ?: $vehicle->owner_name
            ?: 'Unknown partner';

        $location = $vehicle->transporter?->office_address
            ?: $vehicle->transporter?->city;

        $price = '₹' . number_format(
            max(0, (float) $vehicle->hourly_price),
            2,
        ) . '/hour';

        return implode(
            ' | ',
            array_filter([
                $vehicleName,
                $registration,
                $transporter,
                $location,
                $price,
            ]),
        );
    }

    protected static function vehicleName(Vehicle $vehicle): string
    {
        $name = trim(
            (string) $vehicle->car_company_name
            . ' '
            . (string) $vehicle->model_name,
        );

        return filled($name)
            ? $name
            : 'Vehicle #' . $vehicle->id;
    }

    protected static function fillProductFromVehicle(
        mixed $vehicleId,
        Set $set,
    ): void {
        $vehicle = static::findVehicle($vehicleId);

        if (! $vehicle) {
            return;
        }

        $hourlyPrice = max(0, (float) $vehicle->hourly_price);

        $set('price', $hourlyPrice > 0 ? $hourlyPrice : 1);
        $set('max_price', $hourlyPrice > 0 ? $hourlyPrice : 1);

        $categoryId = static::findMatchingCategoryId(
            $vehicle->car_classification,
        );

        if ($categoryId !== null) {
            $set('category_id', $categoryId);
        }
    }

    protected static function findMatchingCategoryId(
        ?string $classification,
    ): ?int {
        if (blank($classification)) {
            return null;
        }

        $classification = Str::headline($classification);

        $categoryId = Category::query()
            ->whereRaw(
                'LOWER(name) = ?',
                [Str::lower($classification)],
            )
            ->value('id');

        return $categoryId !== null
            ? (int) $categoryId
            : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                ImageColumn::make('primary_image')
                    ->label('')
                    ->height(38)
                    ->width(56),

                TextColumn::make('name')
                    ->label('SEO Page')
                    ->description(
                        fn (Product $record): string => '/'
                            . ltrim(
                                (string) $record->slug,
                                '/',
                            ),
                    )
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('ride_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string => match ($state) {
                            'one_way' => 'One Way',
                            'return' => 'Round Trip',
                            'local' => 'Local',
                            'bike_rental' => 'Bike Rental',
                            'self_drive' => 'Self Drive',
                            default => Str::headline((string) $state),
                        },
                    )
                    ->color(
                        fn (?string $state): string => match ($state) {
                            'one_way' => 'primary',
                            'return' => 'warning',
                            'local' => 'success',
                            'bike_rental' => 'info',
                            'self_drive' => 'gray',
                            default => 'gray',
                        },
                    )
                    ->sortable(),

                TextColumn::make('seo_score')
                    ->label('SEO')
                    ->badge()
                    ->suffix('/100')
                    ->getStateUsing(
                        fn (Product $record): int => (int) ($record->seo_score ?? 0),
                    )
                    ->color(
                        fn (int $state): string => match (true) {
                            $state >= 80 => 'success',
                            $state >= 60 => 'info',
                            $state >= 40 => 'warning',
                            default => 'danger',
                        },
                    )
                    ->sortable(),

                TextColumn::make('index_readiness')
                    ->label('Index Status')
                    ->badge()
                    ->getStateUsing(
                        fn (Product $record): string =>
                            static::seoIndexData($record)['status_label'],
                    )
                    ->color(
                        fn (Product $record): string =>
                            static::seoIndexData($record)['status_color'],
                    )
                    ->description(
                        fn (Product $record): string =>
                            static::seoIndexDescription($record),
                    ),

                TextColumn::make('sitemap_status')
                    ->label('Sitemap')
                    ->badge()
                    ->getStateUsing(
                        fn (Product $record): string =>
                            static::seoIndexData($record)['sitemap_eligible']
                                ? 'Eligible'
                                : 'Excluded',
                    )
                    ->color(
                        fn (Product $record): string =>
                            static::seoIndexData($record)['sitemap_eligible']
                                ? 'success'
                                : 'gray',
                    )
                    ->toggleable(),

                TextColumn::make('canonical_status')
                    ->label('Canonical')
                    ->badge()
                    ->getStateUsing(
                        function (Product $record): string {
                            $status = static::seoIndexData($record)['canonical_status'];

                            return match ($status) {
                                'self' => 'Valid',
                                'different' => 'Different',
                                default => 'Missing',
                            };
                        },
                    )
                    ->color(
                        function (Product $record): string {
                            $status = static::seoIndexData($record)['canonical_status'];

                            return match ($status) {
                                'self' => 'success',
                                'different' => 'warning',
                                default => 'danger',
                            };
                        },
                    )
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('robots_status')
                    ->label('Robots')
                    ->badge()
                    ->getStateUsing(
                        fn (Product $record): string =>
                            static::seoIndexData($record)['robots'],
                    )
                    ->color(
                        fn (Product $record): string =>
                            static::seoIndexData($record)['robots_index']
                                ? 'success'
                                : 'danger',
                    )
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('vehicle.display_name')
                    ->label('Self Drive Vehicle')
                    ->placeholder('—')
                    ->toggleable()
                    ->visible(
                        fn (): bool => true,
                    ),

                TextColumn::make('brand.name')
                    ->label('City / From')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('bookingTo.name')
                    ->label('To')
                    ->placeholder('—')
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Live')
                    ->boolean(),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('ride_type')
                    ->label('Page Type')
                    ->options([
                        'one_way' => 'One Way',
                        'return' => 'Round Trip',
                        'local' => 'Local Package',
                        'bike_rental' => 'Bike Rental',
                        'self_drive' => 'Self Drive',
                    ]),

                Tables\Filters\SelectFilter::make('indexing_status')
                    ->label('Index Readiness')
                    ->options([
                        'ready' => 'Index Ready',
                        'noindex' => 'Noindex',
                        'inactive' => 'Inactive',
                        'needs_attention' => 'Needs Attention',
                    ])
                    ->query(
                        function (Builder $query, array $data): Builder {
                            return match ($data['value'] ?? null) {
                                'ready' => $query
                                    ->where('is_active', true)
                                    ->where('robots_index', true)
                                    ->whereNotNull('slug')
                                    ->where('slug', '!=', '')
                                    ->whereNotNull('meta_title')
                                    ->where('meta_title', '!=', '')
                                    ->whereNotNull('description')
                                    ->where('description', '!=', ''),

                                'noindex' => $query->where('robots_index', false),

                                'inactive' => $query->where('is_active', false),

                                'needs_attention' => $query
                                    ->where(function (Builder $builder): void {
                                        $builder
                                            ->whereNull('slug')
                                            ->orWhere('slug', '')
                                            ->orWhereNull('meta_title')
                                            ->orWhere('meta_title', '')
                                            ->orWhereNull('description')
                                            ->orWhere('description', '');
                                    }),

                                default => $query,
                            };
                        },
                    ),

                Tables\Filters\SelectFilter::make('seo_score_range')
                    ->label('SEO Score')
                    ->options([
                        'excellent' => '80–100',
                        'good' => '60–79',
                        'needs_work' => '40–59',
                        'poor' => 'Below 40',
                    ])
                    ->query(
                        function (Builder $query, array $data): Builder {
                            return match ($data['value'] ?? null) {
                                'excellent' => $query->whereBetween('seo_score', [80, 100]),
                                'good' => $query->whereBetween('seo_score', [60, 79]),
                                'needs_work' => $query->whereBetween('seo_score', [40, 59]),
                                'poor' => $query->where('seo_score', '<', 40),
                                default => $query,
                            };
                        },
                    ),

                Tables\Filters\TernaryFilter::make('canonical_url')
                    ->label('Canonical URL')
                    ->placeholder('All')
                    ->trueLabel('Canonical Set')
                    ->falseLabel('Canonical Missing')
                    ->queries(
                        true: fn (Builder $query): Builder =>
                            $query->whereNotNull('canonical_url')
                                ->where('canonical_url', '!=', ''),
                        false: fn (Builder $query): Builder =>
                            $query->whereNull('canonical_url')
                                ->orWhere('canonical_url', ''),
                    ),

                SelectFilter::make('vehicle_id')
                    ->label('Self Drive Vehicle')
                    ->options(
                        fn (): array => Vehicle::query()
                            ->where(
                                'service_type',
                                Vehicle::SERVICE_SELF_DRIVE,
                            )
                            ->orderBy('car_company_name')
                            ->orderBy('model_name')
                            ->limit(500)
                            ->get()
                            ->mapWithKeys(
                                fn (Vehicle $vehicle): array => [
                                    $vehicle->id =>
                                        static::vehicleName($vehicle)
                                        . ' | '
                                        . ($vehicle->vehicle_number ?: 'N/A'),
                                ],
                            )
                            ->all(),
                    )
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('open_live_page')
                        ->label('Open Live Page')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->url(
                            fn (Product $record): string =>
                                static::seoIndexData($record)['page_url'],
                            shouldOpenInNewTab: true,
                        ),

                    Tables\Actions\Action::make('analyze_seo')
                        ->label('Analyze SEO')
                        ->icon('heroicon-o-magnifying-glass-circle')
                        ->color('info')
                        ->action(function (Product $record): void {
                            $analysis = app(SeoAnalysisService::class)
                                ->analyzeToArray(static::seoRecordData($record));

                            $indexing = $analysis['indexing'];

                            $body = sprintf(
                                'SEO Score: %d/100 | Index: %s | Sitemap: %s | Blockers: %d | Warnings: %d',
                                (int) $analysis['score'],
                                (string) $indexing['status_label'],
                                $indexing['sitemap_eligible'] ? 'Eligible' : 'Excluded',
                                count($indexing['blockers']),
                                count($indexing['warnings']),
                            );

                            Notification::make()
                                ->title('SEO analysis completed')
                                ->body($body)
                                ->color((string) $indexing['status_color'])
                                ->send();
                        }),

                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->headerActions([
                ImportAction::make()
                    ->importer(ProductImporter::class)
                    ->options([
                        'updateExisting' => true,
                    ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('analyze_selected_seo')
                        ->label('Analyze Selected SEO')
                        ->icon('heroicon-o-chart-bar-square')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Analyze selected SEO pages')
                        ->modalDescription(
                            'This recalculates SEO and readability scores for the selected pages. It does not submit URLs to Google.'
                        )
                        ->action(function ($records): void {
                            $service = app(SeoAnalysisService::class);

                            $ready = 0;
                            $needsAttention = 0;
                            $updated = 0;

                            foreach ($records as $record) {
                                $analysis = $service->analyzeToArray(
                                    static::seoRecordData($record)
                                );

                                $record->forceFill([
                                    'seo_score' => (int) ($analysis['score'] ?? 0),
                                    'readability_score' => (int) (
                                        $analysis['readability_score']
                                        ?? 0
                                    ),
                                ])->save();

                                if (
                                    (bool) data_get(
                                        $analysis,
                                        'indexing.index_ready',
                                        false
                                    )
                                ) {
                                    $ready++;
                                } else {
                                    $needsAttention++;
                                }

                                $updated++;
                            }

                            Notification::make()
                                ->title('Bulk SEO analysis completed')
                                ->body(
                                    "Updated: {$updated} | Index Ready: {$ready} | Needs Attention: {$needsAttention}"
                                )
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('generate_canonical_urls')
                        ->label('Generate Missing Canonicals')
                        ->icon('heroicon-o-link')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Generate canonical URLs')
                        ->modalDescription(
                            'Only blank canonical fields will be filled. Existing canonical URLs and slugs will not be changed.'
                        )
                        ->action(function ($records): void {
                            $updated = 0;
                            $skipped = 0;

                            foreach ($records as $record) {
                                if (filled($record->canonical_url)) {
                                    $skipped++;
                                    continue;
                                }

                                $record->forceFill([
                                    'canonical_url' => static::productionProductUrl(
                                        $record
                                    ),
                                ])->save();

                                $updated++;
                            }

                            Cache::forget('seo.sitemap.xml.data');

                            Notification::make()
                                ->title('Canonical generation completed')
                                ->body(
                                    "Generated: {$updated} | Existing preserved: {$skipped}"
                                )
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('set_index_follow')
                        ->label('Set Index, Follow')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Allow search engine indexing')
                        ->modalDescription(
                            'This sets robots to index,follow for selected pages. It does not guarantee Google indexing.'
                        )
                        ->action(function ($records): void {
                            $updated = 0;

                            foreach ($records as $record) {
                                $record->forceFill([
                                    'robots_index' => true,
                                    'robots_follow' => true,
                                ])->save();

                                $updated++;
                            }

                            Cache::forget('seo.sitemap.xml.data');

                            Notification::make()
                                ->title('Robots settings updated')
                                ->body("Updated {$updated} selected pages.")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('sync_selected_with_sitemap')
                        ->label('Sync Sitemap')
                        ->icon('heroicon-o-arrow-path')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->modalHeading('Refresh dynamic sitemap')
                        ->modalDescription(
                            'The sitemap cache will be cleared so eligible selected pages appear on the next sitemap request.'
                        )
                        ->action(function ($records): void {
                            Cache::forget('seo.sitemap.xml.data');

                            $eligible = 0;
                            $excluded = 0;

                            foreach ($records as $record) {
                                $analysis = static::seoIndexData($record);

                                if ($analysis['sitemap_eligible']) {
                                    $eligible++;
                                } else {
                                    $excluded++;
                                }
                            }

                            Notification::make()
                                ->title('Sitemap refreshed')
                                ->body(
                                    "Eligible selected pages: {$eligible} | Excluded: {$excluded}"
                                )
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected static function seoIndexData(Product $record): array
    {
        return app(SeoAnalysisService::class)
            ->analyzeIndexReadiness(static::seoRecordData($record));
    }

    /**
     * @return array<string, mixed>
     */
    protected static function seoRecordData(Product $record): array
    {
        return [
            'name' => $record->name,
            'slug' => $record->slug,
            'description' => $record->description,
            'meta_title' => $record->meta_title,
            'meta_description' => $record->meta_description,
            'focus_keyword' => $record->focus_keyword,
            'canonical_url' => $record->canonical_url,
            'robots_index' => $record->robots_index,
            'robots_follow' => $record->robots_follow,
            'is_active' => $record->is_active,
            'page_url' => static::productionProductUrl($record),
            'updated_at' => $record->updated_at,
        ];
    }

    protected static function previewProductUrl(
        string $rideType,
        string $slug
    ): string {
        $baseUrl = rtrim(
            (string) config(
                'services.search_console.property',
                config('app.url')
            ),
            '/'
        );

        $prefix = match ($rideType) {
            'self_drive' => 'self-drive',
            'bike_rental' => 'bike-rental',
            'tour' => 'tour',
            default => 'route',
        };

        $cleanSlug = ltrim(trim($slug), '/');

        return $cleanSlug === ''
            ? $baseUrl . '/' . $prefix . '/{slug}'
            : $baseUrl . '/' . $prefix . '/' . $cleanSlug;
    }

    protected static function productionProductUrl(
        Product $record
    ): string {
        return filled($record->public_url)
            ? (string) $record->public_url
            : static::previewProductUrl(
                (string) $record->ride_type,
                (string) $record->slug,
            );
    }

    protected static function seoIndexDescription(Product $record): string
    {
        $analysis = static::seoIndexData($record);

        if ($analysis['blockers'] !== []) {
            return (string) ($analysis['blockers'][0]['title']
                ?? 'Indexing issue found');
        }

        if ($analysis['warnings'] !== []) {
            return (string) ($analysis['warnings'][0]['title']
                ?? 'SEO warning found');
        }

        return 'Technically ready for indexing';
    }

    public static function getRelations(): array
    {
        return [
            LinksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}