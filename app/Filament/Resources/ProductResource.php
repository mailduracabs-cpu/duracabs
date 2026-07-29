<?php

namespace App\Filament\Resources;

use App\Filament\Imports\ProductImporter;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers\LinksRelationManager;
use App\Forms\Components\DuraImageUpload;
use App\Forms\Components\DuraSeo;
use App\Forms\Components\DuraSeoAiWriter;
use App\Models\Category;
use App\Models\Product;
use App\Models\Vehicle;
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
                                                'one_way' => 'One Way',
                                                'return' => 'Round Trip',
                                                'local' => 'Local Package',
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
                                            ->unique(
                                                Product::class,
                                                'slug',
                                                ignoreRecord: true,
                                            ),
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
                                            ->required(),

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
                                        'Type vehicle name, model or registration number',
                                    )
                                    ->searchable()
                                    ->native(false)
                                    ->required(
                                        fn (Get $get): bool =>
                                            $get('ride_type') === 'self_drive',
                                    )
                                    ->getSearchResultsUsing(
                                        fn (string $search): array =>
                                            static::searchSelfDriveVehicles($search),
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
                                        'Only approved, active, live and verified Self Drive vehicles are shown.',
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

    protected static function searchSelfDriveVehicles(string $search): array
    {
        $search = trim($search);

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
                                                        'pickup_address',
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
            ->limit(50)
            ->get()
            ->mapWithKeys(
                fn (Vehicle $vehicle): array => [
                    $vehicle->id => static::formatVehicleOption($vehicle),
                ],
            )
            ->all();
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

        $location = $vehicle->transporter?->pickup_address;

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
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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