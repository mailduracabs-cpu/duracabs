<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers\AddressRelationManager;
use App\Filament\Resources\OrderResource\RelationManagers\InvoicesRelationManager;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vehicle;
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

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationLabel = 'Bookings';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('Create Booking')
                ->persistTabInQueryString()
                ->tabs([
                    Forms\Components\Tabs\Tab::make('1. Customer')
                        ->icon('heroicon-o-user')
                        ->schema([
                            Forms\Components\Section::make('Customer Details')
                                ->description('Search an existing customer or create a new customer without leaving this page.')
                                ->schema([
                                    Forms\Components\Placeholder::make('booking_reference')
                                        ->label('Booking Number')
                                        ->content(function (?Order $record): string {
                                            return $record
                                                ? static::bookingNumber($record)
                                                : 'Generated automatically after saving';
                                        }),

                                    Forms\Components\Select::make('user_id')
                                        ->label('Customer')
                                        ->relationship('user', 'name')
                                        ->getOptionLabelFromRecordUsing(
                                            fn (User $record): string =>
                                            trim(
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
                                            $mobile = preg_replace(
                                                '/\D+/',
                                                '',
                                                (string) ($data['mobile'] ?? '')
                                            );

                                            return User::query()->firstOrCreate(
                                                ['mobile' => $mobile],
                                                [
                                                    'name' => $data['name'],
                                                    'email' => $data['email'] ?? null,
                                                    'password' => bcrypt(Str::random(32)),
                                                ]
                                            )->id;
                                        })
                                        ->required()
                                        ->columnSpanFull(),
                                ])
                                ->columns(2),
                        ]),

                    Forms\Components\Tabs\Tab::make('2. Journey')
                        ->icon('heroicon-o-map')
                        ->schema([
                            Forms\Components\Section::make('Journey Details')
                                ->description('Only fields required for the selected service will be shown.')
                                ->schema([
                                    Forms\Components\Select::make('ride_type')
                                        ->label('Booking Type')
                                        ->options([
                                            'one_way' => 'One Way',
                                            'local' => 'Local',
                                            'return' => 'Round Trip / Multi-City',
                                            'airport' => 'Airport',
                                            'self_drive' => 'Self Drive',
                                        ])
                                        ->default('one_way')
                                        ->required()
                                        ->native(false)
                                        ->live(),

                                    Forms\Components\TextInput::make('cityFrom')
                                        ->label('Pickup City / Location')
                                        ->required()
                                        ->maxLength(255),

                                    Forms\Components\TextInput::make('cityTo')
                                        ->label(fn (Get $get): string =>
                                            $get('ride_type') === 'return'
                                                ? 'First Destination'
                                                : 'Drop City / Location'
                                        )
                                        ->visible(fn (Get $get): bool =>
                                            in_array(
                                                $get('ride_type'),
                                                ['one_way', 'return', 'airport'],
                                                true
                                            )
                                        )
                                        ->required(fn (Get $get): bool =>
                                            in_array(
                                                $get('ride_type'),
                                                ['one_way', 'return', 'airport'],
                                                true
                                            )
                                        )
                                        ->maxLength(255),

                                    Forms\Components\TagsInput::make('route_stops')
                                        ->label('Additional Cities')
                                        ->placeholder('Type a city and press Enter')
                                        ->helperText('Use for Round Trip / Multi-City bookings. The route returns to the pickup city.')
                                        ->visible(fn (Get $get): bool =>
                                            $get('ride_type') === 'return'
                                        )
                                        ->columnSpanFull(),

                                    Forms\Components\DatePicker::make('date')
                                        ->label('Pickup Date')
                                        ->required()
                                        ->native(false),

                                    Forms\Components\TimePicker::make('time')
                                        ->label('Pickup Time')
                                        ->seconds(false)
                                        ->required()
                                        ->native(false),

                                    Forms\Components\DatePicker::make('dateTo')
                                        ->label('Return / Drop Date')
                                        ->afterOrEqual('date')
                                        ->visible(fn (Get $get): bool =>
                                            in_array(
                                                $get('ride_type'),
                                                ['return', 'self_drive'],
                                                true
                                            )
                                        )
                                        ->required(fn (Get $get): bool =>
                                            in_array(
                                                $get('ride_type'),
                                                ['return', 'self_drive'],
                                                true
                                            )
                                        )
                                        ->native(false),

                                    Forms\Components\TimePicker::make('endTime')
                                        ->label('Return / Drop Time')
                                        ->seconds(false)
                                        ->visible(fn (Get $get): bool =>
                                            in_array(
                                                $get('ride_type'),
                                                ['return', 'self_drive'],
                                                true
                                            )
                                        )
                                        ->native(false),

                                    Forms\Components\Select::make('plan')
                                        ->label('Local Package')
                                        ->options(static::plans())
                                        ->visible(fn (Get $get): bool =>
                                            $get('ride_type') === 'local'
                                        )
                                        ->required(fn (Get $get): bool =>
                                            $get('ride_type') === 'local'
                                        )
                                        ->native(false),

                                    Forms\Components\TextInput::make('total_km')
                                        ->label('Total / Billable KM')
                                        ->numeric()
                                        ->suffix('KM'),

                                    Forms\Components\Textarea::make('notes')
                                        ->label('Booking Notes')
                                        ->rows(3)
                                        ->columnSpanFull(),
                                ])
                                ->columns(3),
                        ]),

                    Forms\Components\Tabs\Tab::make('3. Vehicle & Fare')
                        ->icon('heroicon-o-truck')
                        ->schema([
                            Forms\Components\Section::make('Vehicle Selection')
                                ->schema([
                                    Forms\Components\Select::make('taxi_type')
                                        ->label('Vehicle Category')
                                        ->options(static::taxiTypes())
                                        ->native(false)
                                        ->searchable(),

                                    Forms\Components\TextInput::make('productName')
                                        ->label('Vehicle / Package Name')
                                        ->maxLength(255),

                                    Forms\Components\Select::make('transporter_id')
                                        ->label('Transporter')
                                        ->options(fn (): array => User::role('Transporter')
                                            ->pluck('name', 'id')
                                            ->all())
                                        ->searchable()
                                        ->preload()
                                        ->live(),

                                    Forms\Components\Select::make('driver_id')
                                        ->label('Driver')
                                        ->options(function (Get $get): array {
                                            $transporterId = $get('transporter_id');

                                            if (! $transporterId) {
                                                return [];
                                            }

                                            return User::role('Driver')
                                                ->where('created_by', $transporterId)
                                                ->pluck('name', 'id')
                                                ->all();
                                        })
                                        ->searchable()
                                        ->preload(),

                                    Forms\Components\Select::make('vehicle_id')
                                        ->label('Assigned Vehicle')
                                        ->options(function (Get $get): array {
                                            $transporterId = $get('transporter_id');

                                            $query = Vehicle::query();

                                            if ($transporterId) {
                                                $query->where(function (Builder $builder) use ($transporterId): void {
                                                    $builder
                                                        ->where('user_id', $transporterId)
                                                        ->orWhere('transporter_profile_id', $transporterId);
                                                });
                                            }

                                            return $query
                                                ->limit(200)
                                                ->get()
                                                ->mapWithKeys(function (Vehicle $vehicle): array {
                                                    $name = trim(
                                                        ($vehicle->car_company_name ?? '')
                                                        . ' '
                                                        . ($vehicle->model_name ?? '')
                                                    );

                                                    $number = $vehicle->vehicle_number ?: 'No Number';

                                                    return [
                                                        $vehicle->id => trim(
                                                            "{$name} | {$number}",
                                                            ' |'
                                                        ),
                                                    ];
                                                })
                                                ->all();
                                        })
                                        ->searchable()
                                        ->preload(),
                                ])
                                ->columns(3),

                            Forms\Components\Section::make('Fare')
                                ->description('Automatic fare integration will be connected in the next phase. Admin can safely enter or adjust the final fare here.')
                                ->schema([
                                    Forms\Components\Select::make('fare_type')
                                        ->label('Fare Type')
                                        ->options([
                                            'normal' => 'Normal Fare',
                                            'all_inclusive' => 'All Inclusive',
                                        ])
                                        ->default('normal')
                                        ->native(false),

                                    Forms\Components\TextInput::make('calculated_amount')
                                        ->label('Calculated Fare')
                                        ->numeric()
                                        ->prefix('₹')
                                        ->default(0)
                                        ->live()
                                        ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                                            $calculated = max(0, (float) $state);
                                            $discount = max(0, (float) ($get('coupon_value') ?? 0));
                                            $set('grand_total', max(0, $calculated - $discount));
                                        }),

                                    Forms\Components\TextInput::make('coupon_value')
                                        ->label('Manual Discount')
                                        ->numeric()
                                        ->prefix('₹')
                                        ->default(0)
                                        ->live()
                                        ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                                            $calculated = max(
                                                0,
                                                (float) ($get('calculated_amount') ?? 0)
                                            );
                                            $discount = max(0, (float) $state);
                                            $set('grand_total', max(0, $calculated - $discount));
                                        }),

                                    Forms\Components\TextInput::make('grand_total')
                                        ->label('Final Booking Amount')
                                        ->numeric()
                                        ->prefix('₹')
                                        ->required()
                                        ->default(0),

                                    Forms\Components\Textarea::make('fare_override_reason')
                                        ->label('Discount / Fare Change Reason')
                                        ->placeholder('Required when the calculated fare is changed manually.')
                                        ->rows(2)
                                        ->visible(function (Get $get): bool {
                                            return (float) ($get('coupon_value') ?? 0) > 0;
                                        })
                                        ->required(function (Get $get): bool {
                                            return (float) ($get('coupon_value') ?? 0) > 0;
                                        })
                                        ->columnSpanFull(),
                                ])
                                ->columns(4),

                            Forms\Components\Section::make('Order Item')
                                ->schema([
                                    Forms\Components\Repeater::make('items')
                                        ->relationship()
                                        ->defaultItems(1)
                                        ->minItems(1)
                                        ->maxItems(1)
                                        ->schema([
                                            Forms\Components\Select::make('product_id')
                                                ->relationship('product', 'name')
                                                ->label('Fare Product')
                                                ->searchable()
                                                ->preload()
                                                ->required()
                                                ->live()
                                                ->afterStateUpdated(function ($state, Set $set): void {
                                                    $price = (float) (
                                                        Product::find($state)?->price
                                                        ?? 0
                                                    );

                                                    $set('unit_ammount', $price);
                                                    $set('total_ammount', $price);
                                                })
                                                ->columnSpan(6),

                                            Forms\Components\TextInput::make('quantity')
                                                ->numeric()
                                                ->required()
                                                ->default(1)
                                                ->minValue(1)
                                                ->live()
                                                ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                                                    $quantity = max(1, (int) $state);
                                                    $unitAmount = (float) (
                                                        $get('unit_ammount')
                                                        ?? 0
                                                    );

                                                    $set(
                                                        'total_ammount',
                                                        $quantity * $unitAmount
                                                    );
                                                })
                                                ->columnSpan(2),

                                            Forms\Components\TextInput::make('unit_ammount')
                                                ->label('Unit Amount')
                                                ->numeric()
                                                ->prefix('₹')
                                                ->required()
                                                ->dehydrated()
                                                ->columnSpan(2),

                                            Forms\Components\TextInput::make('total_ammount')
                                                ->label('Total')
                                                ->numeric()
                                                ->prefix('₹')
                                                ->required()
                                                ->dehydrated()
                                                ->columnSpan(2),
                                        ])
                                        ->columns(12),
                                ]),
                        ]),

                    Forms\Components\Tabs\Tab::make('4. Payment & Confirm')
                        ->icon('heroicon-o-credit-card')
                        ->schema([
                            Forms\Components\Section::make('Payment')
                                ->schema([
                                    Forms\Components\Select::make('payment_request_type')
                                        ->label('Payment Request')
                                        ->options([
                                            'token' => 'Token Amount',
                                            'full' => 'Full Amount',
                                            'custom' => 'Custom Amount',
                                        ])
                                        ->default('token')
                                        ->native(false)
                                        ->live(),

                                    Forms\Components\TextInput::make('payment_request_amount')
                                        ->label('Amount to Collect')
                                        ->numeric()
                                        ->prefix('₹')
                                        ->default(500)
                                        ->required()
                                        ->visible(fn (Get $get): bool =>
                                            $get('payment_request_type') !== 'full'
                                        ),

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
                                        ->options([
                                            'INR' => 'INR',
                                            'inr' => 'INR',
                                        ])
                                        ->default('INR')
                                        ->required()
                                        ->native(false),

                                    Forms\Components\TextInput::make('coupon_name')
                                        ->label('Coupon / Reference')
                                        ->maxLength(100),
                                ])
                                ->columns(3),

                            Forms\Components\Section::make('Booking Status')
                                ->schema([
                                    Forms\Components\ToggleButtons::make('status')
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
                                        ->icons([
                                            'new' => 'heroicon-m-sparkles',
                                            'reconfirmation' => 'heroicon-m-phone-arrow-down-left',
                                            'confirm' => 'heroicon-m-check-badge',
                                            'modification' => 'heroicon-m-arrow-path',
                                            'start' => 'heroicon-m-truck',
                                            'cancelled' => 'heroicon-m-x-circle',
                                            'closed' => 'heroicon-m-clipboard-document-check',
                                            'refund' => 'heroicon-m-banknotes',
                                        ]),
                                ]),
                        ]),
                ])
                ->columnSpanFull(),
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
                        ->visible(fn (): bool =>
                            Route::has('orders.invoice')
                        )
                        ->url(fn (Order $record): string => route(
                            'orders.invoice',
                            ['booking' => static::bookingNumber($record)]
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
        $query = parent::getEloquentQuery()->with([
            'user',
            'items',
        ]);

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

        if ($user->hasRole('Driver')) {
            return $query->where('driver_id', $user->id);
        }

        return $query->where('user_id', $user->id);
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
            'self_drive' => 'Self Drive',
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