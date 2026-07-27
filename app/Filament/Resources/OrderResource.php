<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers\AddressRelationManager;
use App\Filament\Resources\OrderResource\RelationManagers\InvoicesRelationManager;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vehicle;
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
            Forms\Components\Section::make('Booking Information')
                ->schema([
                    Forms\Components\Placeholder::make('booking_reference')
                        ->label('Booking Number')
                        ->content(function (?Order $record): string {
                            if (! $record) {
                                return 'Generated after booking is saved';
                            }

                            return static::bookingNumber($record);
                        }),

                    Forms\Components\Select::make('user_id')
                        ->label('Customer')
                        ->relationship('user', 'name')
                        ->getOptionLabelFromRecordUsing(
                            fn (User $record): string =>
                                trim(($record->name ?: 'No Name') . ' | ' . ($record->mobile ?: 'No Mobile'))
                        )
                        ->searchable(['name', 'mobile'])
                        ->preload()
                        ->required(),

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
                        ->label('Vehicle')
                        ->options(function (Get $get): array {
                            $transporterId = $get('transporter_id');

                            if (! $transporterId) {
                                return [];
                            }

                            return Vehicle::query()
                                ->where(function (Builder $query) use ($transporterId): void {
                                    $query
                                        ->where('user_id', $transporterId)
                                        ->orWhere('transporter_profile_id', $transporterId);
                                })
                                ->get()
                                ->mapWithKeys(function (Vehicle $vehicle): array {
                                    $name = trim(
                                        ($vehicle->car_company_name ?? '') . ' ' .
                                        ($vehicle->model_name ?? '')
                                    );
                                    $number = $vehicle->vehicle_number ?: 'No Number';

                                    return [
                                        $vehicle->id => trim("{$name} | {$number}", ' |'),
                                    ];
                                })
                                ->all();
                        })
                        ->searchable()
                        ->preload(),
                ])
                ->columns(3),

            Forms\Components\Section::make('Trip Details')
                ->schema([
                    Forms\Components\Select::make('ride_type')
                        ->label('Service Type')
                        ->options(static::rideTypes())
                        ->default('one_way')
                        ->required()
                        ->native(false),

                    Forms\Components\TextInput::make('cityFrom')
                        ->label('Pickup City')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('cityTo')
                        ->label('Drop City')
                        ->maxLength(255),

                    Forms\Components\DatePicker::make('date')
                        ->label('Pickup Date')
                        ->required(),

                    Forms\Components\TimePicker::make('time')
                        ->label('Pickup Time')
                        ->seconds(false)
                        ->required(),

                    Forms\Components\DatePicker::make('dateTo')
                        ->label('Return Date')
                        ->afterOrEqual('date'),

                    Forms\Components\TimePicker::make('endTime')
                        ->label('Return Time')
                        ->seconds(false),

                    Forms\Components\Select::make('taxi_type')
                        ->label('Taxi Type')
                        ->options(static::taxiTypes())
                        ->native(false),

                    Forms\Components\Select::make('plan')
                        ->label('Local Plan')
                        ->options(static::plans())
                        ->native(false),

                    Forms\Components\TextInput::make('productName')
                        ->label('Product / Vehicle Name')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('total_km')
                        ->label('Total KM'),

                    Forms\Components\Textarea::make('notes')
                        ->columnSpanFull(),
                ])
                ->columns(3),

            Forms\Components\Section::make('Payment')
                ->schema([
                    Forms\Components\TextInput::make('coupon_name')
                        ->label('Coupon Code')
                        ->maxLength(100),

                    Forms\Components\TextInput::make('coupon_value')
                        ->label('Coupon Discount')
                        ->numeric()
                        ->prefix('₹')
                        ->default(0),

                    Forms\Components\TextInput::make('tax')
                        ->label('GST / Tax')
                        ->numeric()
                        ->prefix('₹')
                        ->default(0),

                    Forms\Components\TextInput::make('grand_total')
                        ->label('Grand Total')
                        ->numeric()
                        ->prefix('₹')
                        ->required()
                        ->default(0),

                    Forms\Components\Select::make('payment_method')
                        ->options(static::paymentMethods())
                        ->default('cash')
                        ->required()
                        ->native(false),

                    Forms\Components\Select::make('payment_status')
                        ->options(static::paymentStatuses())
                        ->default('pending')
                        ->required()
                        ->native(false),

                    Forms\Components\Select::make('currency')
                        ->options([
                            'INR' => 'INR',
                            'inr' => 'INR',
                            'USD' => 'USD',
                            'usd' => 'USD',
                            'EUR' => 'EUR',
                            'eur' => 'EUR',
                            'GBP' => 'GBP',
                            'gbp' => 'GBP',
                        ])
                        ->default('INR')
                        ->required()
                        ->native(false),
                ])
                ->columns(4),

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

            Forms\Components\Section::make('Order Items')
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship()
                        ->schema([
                            Forms\Components\Select::make('product_id')
                                ->relationship('product', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->distinct()
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                ->live()
                                ->afterStateUpdated(function ($state, Set $set): void {
                                    $price = (float) (Product::find($state)?->price ?? 0);
                                    $set('unit_ammount', $price);
                                    $set('total_ammount', $price);
                                })
                                ->columnSpan(4),

                            Forms\Components\TextInput::make('quantity')
                                ->numeric()
                                ->required()
                                ->default(1)
                                ->minValue(1)
                                ->live()
                                ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                                    $quantity = max(1, (int) $state);
                                    $unitAmount = (float) ($get('unit_ammount') ?? 0);
                                    $set('total_ammount', $quantity * $unitAmount);
                                })
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('unit_ammount')
                                ->label('Unit Amount')
                                ->numeric()
                                ->prefix('₹')
                                ->required()
                                ->disabled()
                                ->dehydrated()
                                ->columnSpan(3),

                            Forms\Components\TextInput::make('total_ammount')
                                ->label('Total Amount')
                                ->numeric()
                                ->prefix('₹')
                                ->required()
                                ->dehydrated()
                                ->columnSpan(3),
                        ])
                        ->columns(12),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Order ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('booking_number')
                    ->label('Booking No')
                    ->state(fn (Order $record): string => static::bookingNumber($record))
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Order $record): string =>
                        $record->user?->mobile ?: 'No mobile'),

                Tables\Columns\TextColumn::make('ride_type')
                    ->label('Service')
                    ->badge()
                    ->formatStateUsing(fn ($state): string =>
                        static::rideTypes()[$state] ?? Str::headline((string) $state)),

                Tables\Columns\TextColumn::make('cityFrom')
                    ->label('Route')
                    ->formatStateUsing(fn ($state, Order $record): string =>
                        trim(($record->cityFrom ?: '--') . ' → ' . ($record->cityTo ?: '--'))),

                Tables\Columns\TextColumn::make('transporter_id')
                    ->label('Transporter')
                    ->formatStateUsing(fn ($state): string =>
                        $state ? (User::find($state)?->name ?: '--') : '--'),

                Tables\Columns\TextColumn::make('driver_id')
                    ->label('Driver')
                    ->formatStateUsing(fn ($state): string =>
                        $state ? (User::find($state)?->name ?: '--') : '--'),

                Tables\Columns\TextColumn::make('vehicle_id')
                    ->label('Vehicle')
                    ->formatStateUsing(fn ($state): string =>
                        $state ? strtoupper((string) (Vehicle::find($state)?->vehicle_number ?: '--')) : '--'),

                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Grand Total')
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

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Method')
                    ->badge(),

                Tables\Columns\SelectColumn::make('status')
                    ->options(static::statusOptions())
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('ride_type')
                    ->label('Service Type')
                    ->options(static::rideTypes()),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->options(static::paymentStatuses()),

                Tables\Filters\SelectFilter::make('status')
                    ->options(static::statusOptions()),

                Tables\Filters\SelectFilter::make('transporter_id')
                    ->label('Transporter')
                    ->options(fn (): array => User::role('Transporter')
                        ->pluck('name', 'id')
                        ->all()),

                Tables\Filters\SelectFilter::make('driver_id')
                    ->label('Driver')
                    ->options(fn (): array => User::role('Driver')
                        ->pluck('name', 'id')
                        ->all()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('secure_booking_view')
                    ->label('Secure View')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->visible(fn (): bool => Route::has('booking.secure.show'))
                    ->url(fn (Order $record): string => route(
                        'booking.secure.show',
                        ['booking' => static::bookingNumber($record)]
                    ))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('invoice_pdf')
    ->label('Invoice PDF')
    ->icon('heroicon-o-document-arrow-down')
    ->color('success')
    ->visible(fn (): bool => Route::has('orders.invoice'))
    ->url(function (Order $record): string {
        return route('orders.invoice', [
            'booking' => static::bookingNumber($record),
        ]);
    })
    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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