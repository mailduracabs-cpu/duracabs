<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers\AddressRelationManager;
use App\Filament\Resources\OrderResource\RelationManagers\InvoicesRelationManager;
use App\Models\Order;
use App\Models\Brand;
use App\Models\Price;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use App\Services\WhatsAppService;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationLabel = 'With Driver Bookings';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Create Booking')
                ->description('Customer, journey, package, fare and payment — all in one screen. City, package and fare are loaded from the Laravel database.')
                ->schema([
                    Forms\Components\Placeholder::make('booking_reference')
                        ->label('Booking Number')
                        ->content(function (?Order $record): string {
                            return $record
                                ? static::bookingNumber($record)
                                : 'Generated automatically after saving';
                        })
                        ->columnSpan(1),

                    Forms\Components\Select::make('user_id')
                        ->label('Customer')
                        ->relationship('user', 'name')
                        ->getOptionLabelFromRecordUsing(
                            fn (User $record): string => trim(
                                ($record->name ?: 'No Name')
                                . ' | '
                                . ($record->mobile ?: 'No Mobile')
                            )
                        )
                        ->searchable(['name', 'mobile', 'email'])
                        ->preload()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')
                                ->label('Customer Name')
                                ->required()
                                ->maxLength(150),
                            Forms\Components\TextInput::make('mobile')
                                ->label('Mobile Number')
                                ->tel()
                                ->required()
                                ->maxLength(15),
                            Forms\Components\TextInput::make('email')
                                ->label('Email')
                                ->email()
                                ->maxLength(150),
                        ])
                        ->createOptionUsing(function (array $data): int {
                            $mobile = preg_replace('/\\D+/', '', (string) ($data['mobile'] ?? ''));

                            return User::query()->firstOrCreate(
    ['mobile' => $mobile],
    [
        'name' => $data['name'],
        'email' => filled($data['email'] ?? null)
            ? trim((string) $data['email'])
            : $mobile . '@guest.duracabs.com',
        'password' => bcrypt(Str::random(32)),
    ]
)->id;
                        })
                        ->required()
                        ->columnSpan(2),
                ])
                ->columns(3),

            Forms\Components\Section::make('Journey')
                ->description('Select service and cities. Matching Laravel products/packages will load automatically.')
                ->schema([
                    Forms\Components\Select::make('ride_type')
                        ->label('Booking Type')
                        ->options([
                            'one_way' => 'One Way',
                            'return' => 'Round Trip / Multi-City',
                            'local' => 'Local',
                            'airport' => 'Airport',
                        ])
                        ->default('one_way')
                        ->required()
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set): void {
                            $set('pickup_brand_id', null);
                            $set('drop_brand_id', null);
                            $set('selected_product_id', null);
                            $set('selected_price_id', null);
                            $set('cityFrom', null);
                            $set('cityTo', null);
                            $set('productName', null);
                            $set('taxi_type', null);
                            $set('calculated_amount', 0);
                            $set('coupon_value', 0);
                            $set('grand_total', 0);
                            $set('items', []);
                        }),

                    Forms\Components\Select::make('pickup_brand_id')
                        ->label('Pickup City')
                        ->options(fn (): array => Brand::query()
                            ->where('is_active', 1)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->live()
                        ->dehydrated(false)
                        ->required()
                        ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                            $brand = $state ? Brand::query()->find($state) : null;
                            $set('cityFrom', $brand?->name);
                            $set('selected_product_id', null);
                            $set('selected_price_id', null);
                            $set('productName', null);
                            $set('taxi_type', null);
                            $set('calculated_amount', 0);
                            $set('coupon_value', 0);
                            $set('grand_total', 0);
                            $set('items', []);
                        }),

                    Forms\Components\Select::make('drop_brand_id')
                        ->label(fn (Get $get): string => $get('ride_type') === 'return'
                            ? 'First Destination'
                            : 'Drop City')
                        ->options(function (Get $get): array {
                            $pickupId = (int) ($get('pickup_brand_id') ?: 0);

                            return Brand::query()
                                ->where('is_active', 1)
                                ->when($pickupId > 0, fn (Builder $query) => $query->whereKeyNot($pickupId))
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all();
                        })
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->live()
                        ->dehydrated(false)
                        ->visible(fn (Get $get): bool => in_array(
                            $get('ride_type'),
                            ['one_way', 'return', 'airport'],
                            true
                        ))
                        ->required(fn (Get $get): bool => in_array(
                            $get('ride_type'),
                            ['one_way', 'return', 'airport'],
                            true
                        ))
                        ->afterStateUpdated(function ($state, Set $set): void {
                            $brand = $state ? Brand::query()->find($state) : null;
                            $set('cityTo', $brand?->name);
                            $set('selected_product_id', null);
                            $set('selected_price_id', null);
                            $set('productName', null);
                            $set('taxi_type', null);
                            $set('calculated_amount', 0);
                            $set('coupon_value', 0);
                            $set('grand_total', 0);
                            $set('items', []);
                        }),

                    Forms\Components\DatePicker::make('date')
                        ->label('Start Date')
                        ->required()
                        ->native(false)
                        ->live(),

                    Forms\Components\TimePicker::make('time')
                        ->label('Start Time')
                        ->seconds(false)
                        ->required()
                        ->native(false)
                        ->live(),

                    Forms\Components\TextInput::make('booking_from')
                        ->label('Pickup Location')
                        ->placeholder('e.g. Agra Cantt Railway Station')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('booking_to')
                        ->label('Drop Location')
                        ->placeholder('e.g. Hotel / Airport / Railway Station')
                        ->maxLength(255),

                    Forms\Components\DatePicker::make('dateTo')
                        ->label('End Date')
                        ->afterOrEqual('date')
                        ->visible(fn (Get $get): bool => $get('ride_type') === 'return')
                        ->required(fn (Get $get): bool => $get('ride_type') === 'return')
                        ->native(false)
                        ->live(),

                    Forms\Components\TimePicker::make('endTime')
                        ->label('End Time')
                        ->seconds(false)
                        ->visible(fn (Get $get): bool => $get('ride_type') === 'return')
                        ->required(fn (Get $get): bool => $get('ride_type') === 'return')
                        ->native(false)
                        ->live(),

                    Forms\Components\TagsInput::make('route_stops')
                        ->label('Additional Cities')
                        ->placeholder('Type city and press Enter')
                        ->helperText('Optional for Round Trip / Multi-City.')
                        ->visible(fn (Get $get): bool => $get('ride_type') === 'return')
                        ->columnSpanFull(),

                    Forms\Components\Hidden::make('cityFrom'),
                    Forms\Components\Hidden::make('cityTo'),
                ])
                ->columns(3),

            Forms\Components\Section::make('Package / Vehicle Category & Fare')
                ->description('Packages and fares come from products, prices and categories in the Laravel database.')
                ->schema([
                    Forms\Components\Select::make('selected_product_id')
                        ->label(fn (Get $get): string => $get('ride_type') === 'local'
                            ? 'Local Package'
                            : 'Route / Package')
                        ->options(function (Get $get): array {
                            $rideType = (string) ($get('ride_type') ?: 'one_way');
                            $pickupId = (int) ($get('pickup_brand_id') ?: 0);
                            $dropId = (int) ($get('drop_brand_id') ?: 0);

                            if ($pickupId <= 0) {
                                return [];
                            }

                            $rideTypes = $rideType === 'return'
                                ? ['return', 'round_trip']
                                : [$rideType];

                            return Product::query()
                                ->where('is_active', 1)
                                ->where('brand_id', $pickupId)
                                ->whereIn('ride_type', $rideTypes)
                                ->when(
                                    in_array($rideType, ['one_way', 'return', 'airport'], true) && $dropId > 0,
                                    fn (Builder $query) => $query->where('booking_to', $dropId)
                                )
                                ->orderBy('name')
                                ->get(['id', 'name', 'slug'])
                                ->mapWithKeys(fn (Product $product): array => [
                                    $product->id => trim((string) ($product->name ?: $product->slug)),
                                ])
                                ->all();
                        })
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->live()
                        ->dehydrated(false)
                        ->required()
                        ->helperText('Only matching active products are shown.')
                        ->afterStateUpdated(function ($state, Set $set): void {
                            $product = $state ? Product::query()->find($state) : null;
                            $set('productName', $product?->name);
                            $set('selected_price_id', null);
                            $set('taxi_type', null);
                            $set('calculated_amount', 0);
                            $set('coupon_value', 0);
                            $set('grand_total', 0);
                            $set('items', []);
                        }),

                    Forms\Components\Select::make('selected_price_id')
                        ->label('Vehicle Category / Database Fare')
                        ->options(function (Get $get): array {
                            $productId = (int) ($get('selected_product_id') ?: 0);

                            if ($productId <= 0) {
                                return [];
                            }

                            return Price::query()
                                ->with('category:id,name,slug')
                                ->where('product_id', $productId)
                                ->orderBy('price')
                                ->get()
                                ->mapWithKeys(function (Price $price): array {
                                    $category = $price->category?->name ?: 'Vehicle';
                                    $amount = number_format((float) $price->price, 2);
                                    $max = (float) ($price->max_price ?? 0);
                                    $label = $category . ' — ₹' . $amount;

                                    if ($max > 0 && $max !== (float) $price->price) {
                                        $label .= ' to ₹' . number_format($max, 2);
                                    }

                                    return [$price->id => $label];
                                })
                                ->all();
                        })
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->live()
                        ->dehydrated(false)
                        ->required()
                        ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                            $price = $state
                                ? Price::query()->with('category')->find($state)
                                : null;

                            if (! $price) {
                                $set('taxi_type', null);
                                $set('calculated_amount', 0);
                                $set('grand_total', 0);
                                $set('items', []);
                                return;
                            }

                            $fare = max(0, (float) $price->price);
                            $discount = max(0, (float) ($get('coupon_value') ?? 0));
                            $productId = (int) ($get('selected_product_id') ?: 0);

                            $set('taxi_type', (string) ($price->category?->slug ?: Str::slug((string) ($price->category?->name ?: 'vehicle'))));
                            $set('calculated_amount', $fare);
                            $set('grand_total', max(0, $fare - $discount));

                            if ($productId > 0) {
                                $set('items', [[
                                    'product_id' => $productId,
                                    'quantity' => 1,
                                    'unit_ammount' => $fare,
                                    'total_ammount' => $fare,
                                ]]);
                            }
                        }),

                    Forms\Components\TextInput::make('calculated_amount')
                        ->label('Database Fare')
                        ->numeric()
                        ->prefix('₹')
                        ->default(0)
                        ->readOnly()
                        ->dehydrated(false),

                    Forms\Components\TextInput::make('coupon_value')
                        ->label('Discount / Adjustment')
                        ->numeric()
                        ->prefix('₹')
                        ->default(0)
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                            $databaseFare = max(0, (float) ($get('calculated_amount') ?? 0));
                            $discount = max(0, (float) $state);
                            $set('grand_total', max(0, $databaseFare - $discount));
                        }),

                    Forms\Components\TextInput::make('grand_total')
                        ->label('Final Booking Amount')
                        ->numeric()
                        ->prefix('₹')
                        ->required()
                        ->default(0)
                        ->live(),

                    Forms\Components\Textarea::make('fare_override_reason')
                        ->label('Fare Change / Discount Reason')
                        ->placeholder('Required when final fare differs from database fare.')
                        ->rows(2)
                        ->visible(function (Get $get): bool {
                            return abs(
                                (float) ($get('grand_total') ?? 0)
                                - (float) ($get('calculated_amount') ?? 0)
                            ) > 0.009;
                        })
                        ->required(function (Get $get): bool {
                            return abs(
                                (float) ($get('grand_total') ?? 0)
                                - (float) ($get('calculated_amount') ?? 0)
                            ) > 0.009;
                        })
                        ->columnSpanFull(),

                    Forms\Components\Hidden::make('productName'),
                    Forms\Components\Hidden::make('taxi_type'),

                    Forms\Components\Repeater::make('items')
                        ->relationship()
                        ->label('Order Item')
                        ->defaultItems(0)
                        ->minItems(1)
                        ->maxItems(1)
                        ->schema([
                            Forms\Components\Select::make('product_id')
                                ->relationship('product', 'name')
                                ->label('Product')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->columnSpan(6),
                            Forms\Components\TextInput::make('quantity')
                                ->numeric()
                                ->required()
                                ->default(1)
                                ->minValue(1)
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('unit_ammount')
                                ->label('Unit Amount')
                                ->numeric()
                                ->prefix('₹')
                                ->required()
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('total_ammount')
                                ->label('Total')
                                ->numeric()
                                ->prefix('₹')
                                ->required()
                                ->columnSpan(2),
                        ])
                        ->columns(12)
                        ->collapsed()
                        ->columnSpanFull(),
                ])
                ->columns(3),

            Forms\Components\Section::make('Payment & Status')
                ->schema([
                    Forms\Components\Select::make('payment_request_type')
                        ->label('Amount to Request')
                        ->options([
                            'token' => 'Token Amount',
                            'full' => 'Full Booking Amount',
                            'custom' => 'Custom Amount',
                        ])
                        ->default('token')
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                            $bookingAmount = max(0, (float) ($get('grand_total') ?? 0));

                            if ($state === 'full') {
                                $set('payment_request_amount', $bookingAmount);
                            } elseif ($state === 'token') {
                                $set('payment_request_amount', min(500, $bookingAmount));
                            } elseif ((float) ($get('payment_request_amount') ?? 0) > $bookingAmount) {
                                $set('payment_request_amount', $bookingAmount);
                            }
                        }),

                    Forms\Components\TextInput::make('payment_request_amount')
                        ->label('Payment Link Amount')
                        ->numeric()
                        ->prefix('₹')
                        ->default(500)
                        ->required()
                        ->maxValue(fn (Get $get) => max(0, (float) ($get('grand_total') ?? 0)))
                        ->helperText('After the booking is created, this amount can be used to generate the Razorpay payment link.'),

                    Forms\Components\Select::make('payment_method')
                        ->label('Payment Method')
                        ->options(static::paymentMethods())
                        ->default('cash')
                        ->required()
                        ->native(false),

                    Forms\Components\Select::make('payment_status')
                        ->label('Payment Status')
                        ->options(static::paymentStatuses())
                        ->default('pending')
                        ->required()
                        ->native(false),

                    Forms\Components\Select::make('currency')
                        ->options(['INR' => 'INR'])
                        ->default('INR')
                        ->required()
                        ->native(false),

                    Forms\Components\ToggleButtons::make('status')
                        ->label('Booking Status')
                        ->inline()
                        ->default('new')
                        ->required()
                        ->options(static::statusOptions())
                        ->colors([
                            'new' => 'info',
                            'reconfirmation' => 'warning',
                            'confirm' => 'success',
                            'modification' => 'warning',
                            'start' => 'success',
                            'cancelled' => 'danger',
                            'closed' => 'success',
                            'refund' => 'info',
                        ])
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('coupon_name')
                        ->label('Coupon / Reference')
                        ->maxLength(100),

                    Forms\Components\Textarea::make('notes')
                        ->label('Booking Notes')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('booking_number')
                    ->label('Booking')
                    ->state(fn (Order $record): string =>
                        static::bookingNumber($record)
                    )
                    ->description(fn (Order $record): string =>
                        Str::headline((string) $record->ride_type)
                    )
                    ->copyable()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $builder) use ($search): void {
                            $builder
                                ->where('id', 'like', "%{$search}%")
                                ->orWhere('booking_no', 'like', "%{$search}%");
                        });
                    })
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Order $record): string =>
                        $record->user?->mobile ?: 'No mobile'
                    ),

                Tables\Columns\TextColumn::make('cityFrom')
                    ->label('Route')
                    ->formatStateUsing(fn ($state, Order $record): string =>
                        trim(
                            ($record->cityFrom ?: '--')
                            . (
                                filled($record->cityTo)
                                    ? ' → ' . $record->cityTo
                                    : ''
                            )
                        )
                    )
                    ->wrap()
                    ->searchable(['cityFrom', 'cityTo']),

                Tables\Columns\TextColumn::make('date')
                    ->label('Travel Date')
                    ->date('d M Y')
                    ->description(fn (Order $record): string =>
                        filled($record->time)
                            ? Carbon::parse($record->time)->format('h:i A')
                            : ''
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Amount')
                    ->money('INR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        'paid' => 'success',
                        'failed' => 'danger',
                        'refunded' => 'info',
                        default => 'warning',
                    }),

                Tables\Columns\SelectColumn::make('status')
    ->label('Status')
    ->options(static::statusOptions())
    ->selectablePlaceholder(false)
    ->afterStateUpdated(function (Order $record, $state): void {
        try {
            $record->loadMissing(['user', 'address']);

            $mobile = trim((string) (
                $record->address?->phone
                ?: $record->user?->mobile
                ?: ''
            ));

            if ($mobile === '') {
                \Log::warning('Status WhatsApp skipped: mobile missing.', [
                    'order_id' => $record->id,
                    'status' => $state,
                ]);

                return;
            }

            $bookingId = static::bookingNumber($record);

            $customerName = trim((string) (
                $record->address?->full_name
                ?: $record->user?->name
                ?: 'Customer'
            ));

            $route = trim(
                (string) ($record->cityFrom ?: 'Pickup')
                . ' to '
                . (string) ($record->cityTo ?: $record->cityFrom ?: 'Destination')
            );

            $serviceType = match ($record->ride_type) {
                'return', 'round_trip' => 'Round Trip Taxi',
                'local' => 'Local Taxi',
                'airport' => 'Airport Taxi',
                default => 'One Way Taxi',
            };

            $vehicleName = trim((string) (
                $record->productName
                ?: 'Dura Cabs Vehicle'
            ));

            $travelDate = $record->date
                ? Carbon::parse($record->date)->format('d F Y')
                : 'N/A';

            $travelTime = $record->time
                ? Carbon::parse($record->time)->format('h:i A')
                : 'N/A';

            $commonPayload = [
                'mobile' => $mobile,
                'customer_mobile' => $mobile,
                'customer_name' => $customerName,
                'booking_id' => $bookingId,
                'service_type' => $serviceType,
                'vehicle_name' => $vehicleName,
                'pickup' => $record->cityFrom ?: 'N/A',
                'drop' => $record->cityTo ?: 'N/A',
                'route' => $route,
                'travel_date' => $travelDate,
                'travel_time' => $travelTime,
                'total_amount' => number_format(
                    (float) ($record->grand_total ?? 0),
                    2,
                    '.',
                    ''
                ),
                'payment_status' => ucfirst(
                    (string) ($record->payment_status ?: 'pending')
                ),
            ];

            $result = match ($state) {
                'confirm' => WhatsAppService::dispatchEvent(
                    'booking.confirmed',
                    $commonPayload
                ),

                'start' => WhatsAppService::dispatchEvent(
                    'trip.started',
                    array_merge($commonPayload, [
                        'driver_name' => 'Assigned Driver',
                    ])
                ),

                'closed' => WhatsAppService::dispatchEvent(
                    'trip.completed',
                    $commonPayload
                ),

                'cancelled' => WhatsAppService::dispatchEvent(
                    'booking.cancelled',
                    $commonPayload
                ),

                'refund' => WhatsAppService::dispatchEvent(
                    'refund.processed',
                    array_merge($commonPayload, [
                        'refund_amount' => number_format(
                            (float) (
                                $record->refund_amount
                                ?? $record->grand_total
                                ?? 0
                            ),
                            2,
                            '.',
                            ''
                        ),
                        'refund_status' => 'Processed',
                    ])
                ),

                default => null,
            };

            if (is_array($result) && ! (bool) (
                $result['status']
                ?? $result['success']
                ?? false
            )) {
                \Log::warning('Booking status WhatsApp was not accepted.', [
                    'order_id' => $record->id,
                    'status' => $state,
                    'result' => $result,
                ]);
            }

            \Log::info('Booking status WhatsApp processed.', [
                'order_id' => $record->id,
                'status' => $state,
                'mobile' => $mobile,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Booking status WhatsApp failed.', [
                'order_id' => $record->id,
                'status' => $state,
                'error' => $e->getMessage(),
            ]);
        }
    })
    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('ride_type')
                    ->label('Booking Type')
                    ->options(static::rideTypes()),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->options(static::paymentStatuses()),

                Tables\Filters\SelectFilter::make('status')
                    ->options(static::statusOptions()),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('secure_booking_view')
                        ->label('Secure View')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->visible(fn (): bool =>
                            Route::has('booking.secure.show')
                        )
                        ->url(fn (Order $record): string => route(
                            'booking.secure.show',
                            ['booking' => static::bookingNumber($record)]
                        ))
                        ->openUrlInNewTab(),

                    Tables\Actions\Action::make('invoice_pdf')
    ->label('Invoice PDF')
    ->icon('heroicon-o-document-arrow-down')
    ->color('success')
    ->visible(fn (): bool => Route::has('invoice.shared'))
    ->url(fn (Order $record): string => URL::temporarySignedRoute(
        'invoice.shared',
        now()->addMinutes(30),
        [
            'booking' => static::bookingNumber($record),
        ]
    ))
    ->openUrlInNewTab(),

                    Tables\Actions\DeleteAction::make(),
                ])
                    ->label('Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No bookings found')
            ->emptyStateDescription(
                'Create your first booking from the button above.'
            )
            ->emptyStateIcon('heroicon-o-calendar-days');
    }

    public static function getRelations(): array
    {
        return [
            AddressRelationManager::class,
            InvoicesRelationManager::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::getModel()::count() > 10 ? 'danger' : 'success';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

   public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery()
        ->with([
            'user',
            'items',
        ])
        ->where(function (Builder $builder): void {
            $builder
                ->whereNull('ride_type')
                ->orWhere('ride_type', '!=', 'self_drive');
        });

    $user = auth()->user();

    if (! $user) {
        return $query->whereRaw('1 = 0');
    }

    if ($user->hasRole('Admin')) {
        return $query;
    }

    if ($user->hasRole('Transporter')) {
        return $query->where('transporter_id', $user->id);
    }

    return $query->whereRaw('1 = 0');
}

    public static function bookingNumber(Order $record): string
    {
        if (
            isset($record->booking_no) &&
            filled($record->booking_no)
        ) {
            return (string) $record->booking_no;
        }

        return 'DURA' . str_pad(
            (string) $record->id,
            6,
            '0',
            STR_PAD_LEFT
        );
    }

    public static function rideTypes(): array
    {
        return [
            'one_way' => 'One Way',
            'round_trip' => 'Round Trip',
            'return' => 'Round Trip',
            'local' => 'Local',
            'airport' => 'Airport',
        ];
    }

    public static function taxiTypes(): array
    {
        return [
            'hatchback' => 'Hatchback',
            'sedan' => 'Sedan',
            'suv' => 'SUV',
            'muv' => 'MUV',
            'fedx' => 'SUV',
            'ups' => 'Sedan',
            'dhl' => 'Hatchback',
            'usps' => 'MUV',
        ];
    }

    public static function plans(): array
    {
        return [
            'none' => 'None',
            '4 Hour / 40 Km' => '4 Hour / 40 Km',
            '8 Hour / 80 Km' => '8 Hour / 80 Km',
            '12 Hour / 120 Km' => '12 Hour / 120 Km',
        ];
    }

    public static function paymentMethods(): array
    {
        return [
            'cash' => 'Cash',
            'cod' => 'Pay by Cash',
            'razorpay' => 'Razorpay',
            'RazorPay' => 'Razorpay',
            'online' => 'Online',
            'upi' => 'UPI',
            'card' => 'Card',
            'netbanking' => 'Net Banking',
        ];
    }

    public static function paymentStatuses(): array
    {
        return [
            'pending' => 'Pending',
            'partial' => 'Partial',
            'paid' => 'Paid',
            'failed' => 'Failed',
            'refunded' => 'Refunded',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            'new' => 'Waiting Response',
            'reconfirmation' => 'Reconfirmation / Follow Up',
            'confirm' => 'Confirmed',
            'modification' => 'Modification',
            'start' => 'Trip Started',
            'cancelled' => 'Cancelled / Not Interested',
            'closed' => 'Closed / Completed',
            'refund' => 'Refund',
        ];
    }
}