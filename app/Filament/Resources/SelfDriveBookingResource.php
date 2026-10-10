<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SelfDriveBookingResource\Pages;
use App\Models\FleetManagement\TransporterProfile;
use App\Models\SelfDriveBooking;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\RazorpayService;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SelfDriveBookingResource extends Resource
{
    protected static ?string $model = SelfDriveBooking::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = null;
    protected static ?string $navigationLabel = 'Self Drive Bookings';
    protected static ?int $navigationSort = 4;

    public static function isTransporterPanel(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'transporter';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with([
            'customer', 'vehicle', 'transporter',
        ]);

        if (! static::isTransporterPanel()) {
            return $query;
        }

        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        $profile = TransporterProfile::query()
            ->where('user_id', $user->id)
            ->when(
                filled($user->mobile),
                fn (Builder $q) => $q->orWhere('mobile', $user->mobile)
            )
            ->first();

        return $profile
            ? $query->where('transporter_profile_id', $profile->id)
            : $query->whereRaw('1 = 0');
    }

    public static function canCreate(): bool
    {
        return ! static::isTransporterPanel();
    }

    public static function canEdit(Model $record): bool
    {
        return ! static::isTransporterPanel();
    }

    public static function canDelete(Model $record): bool
    {
        return ! static::isTransporterPanel();
    }

    public static function canDeleteAny(): bool
    {
        return ! static::isTransporterPanel();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Booking Details')
                ->description('Customer, rental date/time and only cars available for the selected period.')
                ->schema([
                    Forms\Components\TextInput::make('booking_no')
                        ->label('Booking No')
                        ->readOnly()
                        ->dehydrated(false)
                        ->visible(fn (?SelfDriveBooking $record): bool =>
                            (bool) $record?->exists),

                    Forms\Components\Select::make('customer_id')
                        ->label('Customer')
                        ->relationship('customer', 'name')
                        ->getOptionLabelFromRecordUsing(
                            fn (User $record): string =>
                                ($record->name ?: 'No Name') . ' | ' .
                                ($record->mobile ?: 'No Mobile')
                        )
                        ->searchable(['name', 'mobile'])
                        ->preload()
                        ->required()
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
                                ->label('Email (Optional)')
                                ->email()
                                ->maxLength(255)
                                ->helperText('Blank chhodne par system automatic internal email generate karega.'),
                        ])
                        ->createOptionUsing(function (array $data): int {
                            $mobile = preg_replace('/\D+/', '', (string) ($data['mobile'] ?? ''));

                            if ($mobile === '') {
                                throw ValidationException::withMessages([
                                    'mobile' => 'Valid mobile number required hai.',
                                ]);
                            }

                            $existingUser = User::query()
                                ->where('mobile', $mobile)
                                ->first();

                            if ($existingUser) {
                                return (int) $existingUser->id;
                            }

                            $email = filled($data['email'] ?? null)
                                ? strtolower(trim((string) $data['email']))
                                : $mobile . '@customer.duracabs.local';

                            if (User::query()->where('email', $email)->exists()) {
                                $email = $mobile . '+' . Str::lower(Str::random(6))
                                    . '@customer.duracabs.local';
                            }

                            return (int) User::query()->create([
                                'name' => trim((string) ($data['name'] ?? 'Customer')),
                                'mobile' => $mobile,
                                'email' => $email,
                                'password' => bcrypt(Str::random(24)),
                            ])->id;
                        }),

                    Forms\Components\DatePicker::make('start_date')
                        ->label('Start Date')
                        ->required()
                        ->native(false)
                        ->live()
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Set $set, ?SelfDriveBooking $record): void {
                            if ($record?->start_datetime) {
                                $set('start_date', $record->start_datetime->format('Y-m-d'));
                            }
                        })
                        ->afterStateUpdated(fn (Get $get, Set $set) =>
                            static::syncRentalDateTime($get, $set)),

                    Forms\Components\TimePicker::make('start_time')
                        ->label('Start Time')
                        ->seconds(false)
                        ->required()
                        ->native()
                        ->displayFormat('h:i A')
                        ->format('H:i')
                        ->suffixIcon('heroicon-m-clock')
                        ->live()
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Set $set, ?SelfDriveBooking $record): void {
                            if ($record?->start_datetime) {
                                $set('start_time', $record->start_datetime->format('H:i'));
                            }
                        })
                        ->afterStateUpdated(fn (Get $get, Set $set) =>
                            static::syncRentalDateTime($get, $set)),

                    Forms\Components\DatePicker::make('end_date')
                        ->label('End Date')
                        ->required()
                        ->native(false)
                        ->live()
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Set $set, ?SelfDriveBooking $record): void {
                            if ($record?->end_datetime) {
                                $set('end_date', $record->end_datetime->format('Y-m-d'));
                            }
                        })
                        ->afterStateUpdated(fn (Get $get, Set $set) =>
                            static::syncRentalDateTime($get, $set)),

                    Forms\Components\TimePicker::make('end_time')
                        ->label('End Time')
                        ->seconds(false)
                        ->required()
                        ->native()
                        ->displayFormat('h:i A')
                        ->format('H:i')
                        ->suffixIcon('heroicon-m-clock')
                        ->live()
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Set $set, ?SelfDriveBooking $record): void {
                            if ($record?->end_datetime) {
                                $set('end_time', $record->end_datetime->format('H:i'));
                            }
                        })
                        ->afterStateUpdated(fn (Get $get, Set $set) =>
                            static::syncRentalDateTime($get, $set)),

                    Forms\Components\Select::make('vehicle_id')
                        ->label('Available Car')
                        ->options(function (Get $get, ?SelfDriveBooking $record): array {
                            $start = static::buildDateTime(
                                $get('start_date'),
                                $get('start_time')
                            );
                            $end = static::buildDateTime(
                                $get('end_date'),
                                $get('end_time')
                            );

                            if (! $start || ! $end || $end->lte($start)) {
                                return [];
                            }

                            $busyVehicleIds = SelfDriveBooking::query()
                                ->activeBooking()
                                ->overlapping($start, $end)
                                ->when(
                                    $record?->exists,
                                    fn (Builder $query) =>
                                        $query->whereKeyNot($record->getKey())
                                )
                                ->pluck('vehicle_id');

                            return Vehicle::query()
                                ->with('transporter')
                                ->where('is_active', true)
                                ->whereNotIn('id', $busyVehicleIds)
                                ->orderBy('car_company_name')
                                ->orderBy('model_name')
                                ->get()
                                ->mapWithKeys(function (Vehicle $vehicle): array {
                                    $name = trim(
                                        ($vehicle->car_company_name ?? '') . ' ' .
                                        ($vehicle->model_name ?? '')
                                    );
                                    $number = $vehicle->vehicle_number ?: 'No Number';
                                    $daily = (float) ($vehicle->daily_price ?? 0);
                                    $hourly = (float) ($vehicle->hourly_price ?? 0);

                                    $rate = $daily > 0
                                        ? '₹' . number_format($daily, 0) . '/day'
                                        : '₹' . number_format($hourly, 0) . '/hour';

                                    return [
                                        $vehicle->id =>
                                            "{$name} | {$number} | {$rate}"
                                    ];
                                })
                                ->all();
                        })
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->helperText('Start and end date/time select karne ke baad sirf available cars dikhengi.')
                        ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                            $vehicle = Vehicle::query()->find($state);

                            if (! $vehicle) {
                                $set('transporter_profile_id', null);
                                $set('hourly_price', 0);
                                $set('price_per_day', 0);
                                $set('security_deposit', 0);
                                static::recalculateBooking($get, $set);
                                return;
                            }

                            $set('transporter_profile_id', $vehicle->transporter_profile_id);
                            $set('hourly_price', (float) ($vehicle->hourly_price ?? 0));
                            $set('price_per_day', (float) ($vehicle->daily_price ?? 0));
                            $set('security_deposit', (float) ($vehicle->security_deposit ?? 0));
                            $set(
                                'minimum_booking_hours',
                                max(1, (int) ($vehicle->minimum_booking_hours ?? 1))
                            );
                            $set('extra_hour_rate', (float) ($vehicle->extra_hour_rate ?? 0));
                            $set('extra_km_rate', (float) ($vehicle->extra_km_rate ?? 0));

                            static::recalculateBooking($get, $set);
                        }),

                    Forms\Components\Hidden::make('start_datetime'),
                    Forms\Components\Hidden::make('end_datetime'),
                    Forms\Components\Hidden::make('transporter_profile_id'),
                    Forms\Components\Hidden::make('minimum_booking_hours'),
                    Forms\Components\Hidden::make('extra_hour_rate'),
                    Forms\Components\Hidden::make('extra_km_rate'),

                    Forms\Components\TextInput::make('booked_hours')
                        ->label('Total Hours')
                        ->numeric()
                        ->readOnly()
                        ->dehydrated(),

                    Forms\Components\TextInput::make('total_days')
                        ->label('Total Days')
                        ->numeric()
                        ->readOnly()
                        ->dehydrated(),
                ])
                ->columns(4),

            Forms\Components\Section::make('Delivery / Pickup Service')
                ->description('Optional. Enable only when customer wants doorstep delivery or return pickup.')
                ->schema([
                    Forms\Components\Toggle::make('delivery_required')
                        ->label('Customer wants Delivery')
                        ->live()
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Set $set, ?SelfDriveBooking $record): void {
                            $set(
                                'delivery_required',
                                filled($record?->delivery_address)
                                || (float) ($record?->delivery_price ?? 0) > 0
                            );
                        })
                        ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                            if (! $state) {
                                $set('delivery_address', null);
                                $set('delivery_price', 0);
                            }
                            static::recalculateBooking($get, $set);
                        }),

                    Forms\Components\Textarea::make('delivery_address')
                        ->label('Delivery Address')
                        ->rows(2)
                        ->visible(fn (Get $get): bool =>
                            (bool) $get('delivery_required'))
                        ->required(fn (Get $get): bool =>
                            (bool) $get('delivery_required'))
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('delivery_price')
                        ->label('Delivery Price')
                        ->numeric()
                        ->prefix('₹')
                        ->default(0)
                        ->minValue(0)
                        ->live()
                        ->visible(fn (Get $get): bool =>
                            (bool) $get('delivery_required'))
                        ->afterStateUpdated(fn (Get $get, Set $set) =>
                            static::recalculateBooking($get, $set)),

                    Forms\Components\Toggle::make('pickup_required')
                        ->label('Customer wants Return Pickup')
                        ->live()
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Set $set, ?SelfDriveBooking $record): void {
                            $set(
                                'pickup_required',
                                filled($record?->pickup_address)
                                || (float) ($record?->pickup_price ?? 0) > 0
                            );
                        })
                        ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                            if (! $state) {
                                $set('pickup_address', null);
                                $set('pickup_price', 0);
                            }
                            static::recalculateBooking($get, $set);
                        }),

                    Forms\Components\Textarea::make('pickup_address')
                        ->label('Return Pickup Address')
                        ->rows(2)
                        ->visible(fn (Get $get): bool =>
                            (bool) $get('pickup_required'))
                        ->required(fn (Get $get): bool =>
                            (bool) $get('pickup_required'))
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('pickup_price')
                        ->label('Pickup Price')
                        ->numeric()
                        ->prefix('₹')
                        ->default(0)
                        ->minValue(0)
                        ->live()
                        ->visible(fn (Get $get): bool =>
                            (bool) $get('pickup_required'))
                        ->afterStateUpdated(fn (Get $get, Set $set) =>
                            static::recalculateBooking($get, $set)),
                ])
                ->columns(4),

            Forms\Components\Section::make('Price & GST')
                ->description('Automatic DB fare and Manual Price are GST-inclusive. Security Deposit is refundable and collected separately.')
                ->schema([
                    Forms\Components\TextInput::make('hourly_price')
                        ->label('Hourly DB Rate')
                        ->numeric()
                        ->prefix('₹')
                        ->readOnly()
                        ->dehydrated(),

                    Forms\Components\TextInput::make('price_per_day')
                        ->label('Daily DB Rate')
                        ->numeric()
                        ->prefix('₹')
                        ->readOnly()
                        ->dehydrated(),

                    Forms\Components\TextInput::make('total_amount')
                        ->label('Price as per Database')
                        ->numeric()
                        ->prefix('₹')
                        ->readOnly()
                        ->dehydrated(),

                    Forms\Components\TextInput::make('gst_percent')
                        ->label('GST')
                        ->numeric()
                        ->suffix('%')
                        ->default(18)
                        ->readOnly()
                        ->dehydrated(),

                    Forms\Components\TextInput::make('gst_amount')
                        ->label('GST Amount')
                        ->numeric()
                        ->prefix('₹')
                        ->readOnly()
                        ->dehydrated(),

                    Forms\Components\TextInput::make('discount_amount')
                        ->label('Discount')
                        ->numeric()
                        ->prefix('₹')
                        ->default(0)
                        ->minValue(0)
                        ->live()
                        ->afterStateUpdated(fn (Get $get, Set $set) =>
                            static::recalculateBooking($get, $set)),

                    Forms\Components\TextInput::make('final_amount')
                        ->label('Grand Total')
                        ->numeric()
                        ->prefix('₹')
                        ->readOnly()
                        ->dehydrated(),

                    Forms\Components\TextInput::make('security_deposit')
                        ->label('Security Deposit (Refundable)')
                        ->numeric()
                        ->prefix('₹')
                        ->default(0)
                        ->minValue(0)
                        ->helperText('Fare/GST se separate refundable amount.'),

                    Forms\Components\TextInput::make('manual_price')
                        ->label('Manual Price (Optional)')
                        ->numeric()
                        ->prefix('₹')
                        ->minValue(0)
                        ->live()
                        ->helperText('Entered amount is treated as GST-inclusive final fare. Leave blank for automatic price.')
                        ->afterStateUpdated(fn (Get $get, Set $set) =>
                            static::recalculateBooking($get, $set)),
                ])
                ->columns(3),

            Forms\Components\Section::make('Payment')
                ->schema([
                    Forms\Components\Select::make('payment_type')
                        ->label('Payment Type')
                        ->options([
                            'advance' => '₹500 Advance',
                            'full' => 'Full Payment',
                            'custom' => 'Custom Received Amount',
                        ])
                        ->required()
                        ->default('advance')
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                            if ($state !== 'custom') {
                                $set('paid_amount', 0);
                            }
                            static::syncPaymentFields($get, $set);
                        }),

                    Forms\Components\TextInput::make('paid_amount')
                        ->label('Custom Received Amount')
                        ->numeric()
                        ->prefix('₹')
                        ->minValue(0)
                        ->default(0)
                        ->live()
                        ->visible(fn (Get $get): bool =>
                            $get('payment_type') === 'custom')
                        ->afterStateUpdated(fn (Get $get, Set $set) =>
                            static::syncPaymentFields($get, $set)),

                    Forms\Components\Select::make('payment_method')
                        ->label('Payment Method')
                        ->options(static::paymentMethods())
                        ->required()
                        ->default('cash')
                        ->native(false),

                    Forms\Components\TextInput::make('payment_reference')
                        ->label('Payment Reference')
                        ->placeholder('Optional'),

                    Forms\Components\TextInput::make('advance_amount')
                        ->label('Advance')
                        ->numeric()
                        ->prefix('₹')
                        ->readOnly()
                        ->dehydrated(),

                    Forms\Components\TextInput::make('remaining_amount')
                        ->label('Balance')
                        ->numeric()
                        ->prefix('₹')
                        ->readOnly()
                        ->dehydrated(),

                    Forms\Components\Select::make('payment_status')
                        ->label('Payment Status')
                        ->options(static::paymentStatuses())
                        ->disabled()
                        ->dehydrated()
                        ->native(false),
                ])
                ->columns(4),

            Forms\Components\Section::make('Booking Status')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label('Booking Status')
                        ->options(static::statusOptions())
                        ->required()
                        ->default(SelfDriveBooking::STATUS_CONFIRMED)
                        ->native(false),

                    Forms\Components\Hidden::make('booking_status')
                        ->default('confirmed'),

                    Forms\Components\Hidden::make('vendor_confirmation_status')
                        ->default('confirmed'),

                    Forms\Components\Hidden::make('document_status')
                        ->default('not_uploaded'),

                    Forms\Components\Hidden::make('settlement_status')
                        ->default(SelfDriveBooking::SETTLEMENT_PENDING),
                ])
                ->columns(2),

            Forms\Components\Section::make('Customer Live Tracking')
                ->description('Live customer/device location while the Self Drive trip is running.')
                ->schema([
                    Forms\Components\ViewField::make('customer_live_tracking')
                        ->label('')
                        ->view('filament.forms.components.self-drive-live-tracking')
                        ->dehydrated(false)
                        ->columnSpanFull(),
                ])
                ->visible(fn (?SelfDriveBooking $record): bool =>
                    (bool) $record?->exists)
                ->columnSpanFull(),

            /*
             * Operational fields stay available only after a booking exists.
             * They are intentionally hidden from the simple Create Booking form.
             */
            Forms\Components\Section::make('Trip, KM & Final Charges')
                ->schema([
                    Forms\Components\TextInput::make('start_km')->numeric(),
                    Forms\Components\TextInput::make('end_km')->numeric(),
                    Forms\Components\TextInput::make('free_km')->numeric()->default(0),
                    Forms\Components\TextInput::make('extra_hour_rate')->numeric()->prefix('₹'),
                    Forms\Components\TextInput::make('extra_km_rate')->numeric()->prefix('₹'),
                    Forms\Components\TextInput::make('extra_hour_amount')->numeric()->prefix('₹'),
                    Forms\Components\TextInput::make('extra_km_amount')->numeric()->prefix('₹'),
                    Forms\Components\TextInput::make('damage_amount')->numeric()->prefix('₹'),
                    Forms\Components\TextInput::make('fuel_charge')->numeric()->prefix('₹'),
                    Forms\Components\TextInput::make('cleaning_charge')->numeric()->prefix('₹'),
                    Forms\Components\TextInput::make('late_return_charge')->numeric()->prefix('₹'),
                    Forms\Components\TextInput::make('other_charge')->numeric()->prefix('₹'),
                    Forms\Components\Textarea::make('damage_note')->columnSpan(2),
                    Forms\Components\Textarea::make('other_charge_note')->columnSpan(2),
                    Forms\Components\Select::make('settlement_status')
                        ->options([
                            'pending' => 'Pending',
                            'balance_due' => 'Balance Due',
                            'completed' => 'Completed',
                        ])
                        ->default('pending')
                        ->native(false),
                ])
                ->columns(4)
                ->collapsed()
                ->visible(fn (?SelfDriveBooking $record): bool =>
                    (bool) $record?->exists),

            Forms\Components\Section::make('OTP & Trip Times')
                ->schema([
                    Forms\Components\TextInput::make('pickup_otp')->readOnly()->dehydrated(false),
                    Forms\Components\DateTimePicker::make('pickup_otp_verified_at')->readOnly()->dehydrated(false),
                    Forms\Components\DateTimePicker::make('trip_start_datetime')->readOnly()->dehydrated(false),
                    Forms\Components\TextInput::make('return_otp')->readOnly()->dehydrated(false),
                    Forms\Components\DateTimePicker::make('return_otp_verified_at')->readOnly()->dehydrated(false),
                    Forms\Components\DateTimePicker::make('trip_end_datetime')->readOnly()->dehydrated(false),
                    Forms\Components\DateTimePicker::make('return_admin_confirmed_at')->label('Admin return approval time')->disabled()->dehydrated(false),
                    Forms\Components\TextInput::make('return_admin_confirmed_by')->label('Approved by admin ID')->disabled()->dehydrated(false),
                    Forms\Components\Textarea::make('return_admin_reason')->label('Admin override reason')->disabled()->dehydrated(false),
                ])
                ->columns(3)
                ->collapsed()
                ->visible(fn (?SelfDriveBooking $record): bool =>
                    (bool) $record?->exists),

            Forms\Components\Section::make('Customer KYC Documents')
                ->relationship('customer')
                ->schema([
                    Forms\Components\TextInput::make('aadhar_number')
                        ->label('Aadhaar Number')
                        ->disabled(),
                    Forms\Components\FileUpload::make('aadhar_front')
                        ->label('Aadhaar Front')
                        ->disk('public')
                        ->image()
                        ->disabled(),
                    Forms\Components\FileUpload::make('aadhar_back')
                        ->label('Aadhaar Back')
                        ->disk('public')
                        ->image()
                        ->disabled(),
                    Forms\Components\TextInput::make('driving_licence_number')
                        ->label('Driving Licence Number')
                        ->disabled(),
                    Forms\Components\FileUpload::make('driving_licence_front')
                        ->label('Driving Licence Front')
                        ->disk('public')
                        ->image()
                        ->disabled(),
                    Forms\Components\FileUpload::make('driving_licence_back')
                        ->label('Driving Licence Back')
                        ->disk('public')
                        ->image()
                        ->disabled(),
                ])
                ->columns(2)
                ->collapsible()
                ->collapsed()
                ->visible(fn (?SelfDriveBooking $record): bool =>
                    (bool) $record?->exists),
        ]);
    }

    private static function buildDateTime(mixed $date, mixed $time): ?Carbon
    {
        if (blank($date) || blank($time)) {
            return null;
        }

        try {
            return Carbon::parse(
                Carbon::parse($date)->format('Y-m-d')
                . ' '
                . Carbon::parse($time)->format('H:i:s')
            );
        } catch (\Throwable) {
            return null;
        }
    }

    public static function syncRentalDateTime(Get $get, Set $set): void
    {
        $start = static::buildDateTime(
            $get('start_date'),
            $get('start_time')
        );
        $end = static::buildDateTime(
            $get('end_date'),
            $get('end_time')
        );

        $set('start_datetime', $start?->format('Y-m-d H:i:s'));
        $set('end_datetime', $end?->format('Y-m-d H:i:s'));

        /*
         * Date/time changed: force car re-selection because availability
         * may have changed for the new window.
         */
        $set('vehicle_id', null);
        $set('transporter_profile_id', null);
        $set('hourly_price', 0);
        $set('price_per_day', 0);

        static::recalculateBooking($get, $set);
    }

    public static function recalculateBooking(Get $get, Set $set): void
    {
        $start = static::buildDateTime(
            $get('start_date'),
            $get('start_time')
        );
        $end = static::buildDateTime(
            $get('end_date'),
            $get('end_time')
        );

        if (! $start || ! $end || $end->lte($start)) {
            $set('booked_hours', 0);
            $set('total_days', 0);
            $set('total_amount', 0);
            $set('gst_amount', 0);
            $set('final_amount', 0);
            static::syncPaymentFields($get, $set);
            return;
        }

        $minutes = max(1, $start->diffInMinutes($end));
        $hours = max(1, (int) ceil($minutes / 60));
        $minimum = max(1, (int) ($get('minimum_booking_hours') ?: 1));
        $billableHours = max($hours, $minimum);

        $hourly = max(0, (float) ($get('hourly_price') ?: 0));
        $daily = max(0, (float) ($get('price_per_day') ?: 0));

        /*
         * Automatic database rate:
         * - hourly rate when daily rate is unavailable;
         * - otherwise choose the cheaper DB combination of whole days +
         *   remaining hours versus charging all required 24-hour blocks.
         */
        if ($daily > 0) {
            $fullDays = intdiv($billableHours, 24);
            $remainingHours = $billableHours % 24;

            $mixedRate = ($fullDays * $daily)
                + (
                    $remainingHours > 0
                        ? min(
                            $daily,
                            $hourly > 0
                                ? $remainingHours * $hourly
                                : $daily
                        )
                        : 0
                );

            $allDailyRate = (int) ceil($billableHours / 24) * $daily;

            $databasePrice = min(
                $mixedRate > 0 ? $mixedRate : $allDailyRate,
                $allDailyRate
            );
        } else {
            $databasePrice = $billableHours * $hourly;
        }

        $delivery = max(0, (float) ($get('delivery_price') ?: 0));
        $pickup = max(0, (float) ($get('pickup_price') ?: 0));
        $discount = max(0, (float) ($get('discount_amount') ?: 0));

        $gstPercent = 18.0;
        $manual = $get('manual_price');
        $manualPrice = filled($manual)
            ? max(0, (float) $manual)
            : null;

        $set('booked_hours', $hours);
        $set('total_days', max(1, (int) ceil($hours / 24)));
        $set('gst_percent', $gstPercent);
        $set('total_amount', round($databasePrice, 2));

        if ($manualPrice !== null && $manualPrice > 0) {
            /*
             * Manual Price is GST inclusive. No extra GST is added.
             */
            $rentalTotal = max(0, $manualPrice);
            $taxable = $rentalTotal / 1.18;
            $gstAmount = $rentalTotal - $taxable;

            $set('gst_amount', round($gstAmount, 2));
            $set('final_amount', round($rentalTotal, 2));
        } else {
            /*
             * IMPORTANT:
             * DB rental price, delivery and pickup are treated as GST-inclusive.
             * GST is only calculated as a breakup and is NOT added again.
             */
            $rentalTotal = max(
                0,
                $databasePrice + $delivery + $pickup - $discount
            );

            $taxable = $rentalTotal / 1.18;
            $gstAmount = $rentalTotal - $taxable;

            $set('gst_amount', round($gstAmount, 2));
            $set('final_amount', round($rentalTotal, 2));
        }

        static::syncPaymentFields($get, $set);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('booking_no')
                    ->searchable()->sortable()->copyable()->weight('bold'),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')->searchable()
                    ->description(fn (SelfDriveBooking $record) =>
                        $record->customer?->mobile ?: 'No mobile'),
                Tables\Columns\TextColumn::make('vehicle.vehicle_number')
                    ->label('Vehicle')->searchable()
                    ->description(fn (SelfDriveBooking $record) =>
                        trim(
                            ($record->vehicle?->car_company_name ?? '') . ' ' .
                            ($record->vehicle?->model_name ?? '')
                        )),
                Tables\Columns\TextColumn::make('start_datetime')
                    ->label('Pickup')->dateTime('d M Y, h:i A')->sortable(),
                Tables\Columns\TextColumn::make('final_amount')
                    ->label('Rental Total')->money('INR')->sortable(),
                Tables\Columns\TextColumn::make('security_deposit')
                    ->label('Security')->money('INR')->sortable(),
                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('Paid')->money('INR')->sortable(),
                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('Balance')->money('INR')->sortable(),
                Tables\Columns\TextColumn::make('payment_type')
                    ->badge()->formatStateUsing(
                        fn ($state) => $state === 'advance' ? '₹500 Advance' : 'Full'
                    ),
                Tables\Columns\TextColumn::make('payment_status')
                    ->badge()->color(fn ($state) => match ($state) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        'refunded' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('return_draft.admin_request.reason')
                    ->label('Return approval request')->limit(45)->wrap()
                    ->tooltip(fn (SelfDriveBooking $record) => $record->return_draft['admin_request']['reason'] ?? null),
                Tables\Columns\TextColumn::make('return_admin_confirmed_at')
                    ->label('Admin return approval')->dateTime(),
                Tables\Columns\SelectColumn::make('status')
                    ->label('Status')
                    ->options(static::statusOptions())
                    ->selectablePlaceholder(false)
                    ->beforeStateUpdated(function (SelfDriveBooking $record, $state): void {
                        if (
                            $state === SelfDriveBooking::STATUS_COMPLETED
                            && (
                                $record->payment_status !== 'paid'
                                || (float) $record->remaining_amount > 0.009
                            )
                        ) {
                            throw ValidationException::withMessages([
                                'status' => 'Full payment receive hone ke baad hi booking Completed ho sakti hai.',
                            ]);
                        }
                    })
                    ->afterStateUpdated(function (SelfDriveBooking $record, $state): void {
                        $record->refresh();

                        Notification::make()
                            ->title('Booking status updated')
                            ->body('Status changed to ' . Str::headline((string) $state) . '.')
                            ->success()
                            ->send();
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_status')
                    ->options(static::paymentStatuses()),
                Tables\Filters\SelectFilter::make('status')
                    ->options(static::statusOptions()),
                Tables\Filters\SelectFilter::make('vehicle_id')
                    ->relationship('vehicle', 'vehicle_number')
                    ->searchable()->preload(),
            ])
            ->actions([
                static::securityRefundAction(),
                Tables\Actions\EditAction::make()
                    ->visible(fn () => ! static::isTransporterPanel()),

                Tables\Actions\Action::make('receive_payment')
                    ->label('Receive Payment')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (SelfDriveBooking $record) =>
                        ! static::isTransporterPanel()
                        && (float) $record->remaining_amount > 0
                        && ! in_array($record->status, ['cancelled', 'rejected'], true))
                    ->form([
                        Forms\Components\Placeholder::make('payment_summary')
                            ->label('Payment Summary')
                            ->content(function (SelfDriveBooking $record): string {
                                return 'Rental: ₹' . number_format($record->effectiveRentalAmount(), 2)
                                    . ' | Security: ₹' . number_format((float) ($record->security_deposit ?? 0), 2)
                                    . ' | Paid: ₹' . number_format((float) ($record->paid_amount ?? 0), 2)
                                    . ' | Balance: ₹' . number_format((float) ($record->remaining_amount ?? 0), 2);
                            })
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('amount')
                            ->label('Amount')
                            ->numeric()
                            ->prefix('₹')
                            ->required()
                            ->minValue(1)
                            ->maxValue(fn (SelfDriveBooking $record): float =>
                                max(1, (float) $record->remaining_amount))
                            ->helperText(fn (SelfDriveBooking $record): string =>
                                'Maximum current balance: ₹'
                                . number_format((float) $record->remaining_amount, 2)),

                        Forms\Components\Select::make('method')
                            ->label('Method')
                            ->options(static::paymentMethods())
                            ->required()
                            ->native(false)
                            ->live(),

                        Forms\Components\TextInput::make('reference')
                            ->label('Reference')
                            ->placeholder('UPI / Bank / Card reference (optional)')
                            ->visible(fn (Get $get): bool =>
                                $get('method') !== 'razorpay_link'),
                    ])
                    ->action(function (SelfDriveBooking $record, array $data): void {
                        $record->loadMissing(['customer', 'vehicle']);

                        $amount = round((float) $data['amount'], 2);
                        $balance = round((float) $record->remaining_amount, 2);
                        $method = (string) $data['method'];

                        if ($amount <= 0) {
                            throw ValidationException::withMessages([
                                'amount' => 'Valid payment amount required hai.',
                            ]);
                        }

                        if ($amount > $balance + 0.009) {
                            throw ValidationException::withMessages([
                                'amount' => 'Amount current balance se zyada nahi ho sakta.',
                            ]);
                        }

                        /*
                         * RAZORPAY PAYMENT LINK:
                         * Creating/sending a link does NOT mark payment as received.
                         * Razorpay webhook will update paid/balance after actual payment.
                         */
                        if ($method === 'razorpay_link') {
                            $referenceId = 'SDPL-'
                                . $record->id
                                . '-'
                                . now()->format('YmdHis');

                            $result = RazorpayService::createPaymentLink(
                                amount: $amount,
                                referenceId: $referenceId,
                                description: 'Dura Cabs Self Drive - ' . $record->booking_no,
                                customer: [
                                    'name' => $record->customer?->name ?? 'Customer',
                                    'contact' => $record->customer?->mobile ?? '',
                                    'email' => $record->customer?->email ?? '',
                                ],
                                notes: [
                                    'booking_id' => (string) $record->id,
                                    'booking_no' => (string) $record->booking_no,
                                    'booking_source' => 'self_drive',
                                    'requested_amount' => number_format($amount, 2, '.', ''),
                                ],
                            );

                            if (! (bool) ($result['status'] ?? false)) {
                                throw ValidationException::withMessages([
                                    'amount' => (string) (
                                        $result['message']
                                        ?? 'Razorpay payment link create nahi ho saka.'
                                    ),
                                ]);
                            }

                            $linkData = is_array($result['data'] ?? null)
                                ? $result['data']
                                : [];

                            $paymentLink = trim((string) ($linkData['short_url'] ?? ''));
                            $paymentLinkId = trim((string) ($linkData['id'] ?? ''));

                            if ($paymentLink === '') {
                                throw ValidationException::withMessages([
                                    'amount' => 'Razorpay ne valid payment URL return nahi kiya.',
                                ]);
                            }

                            /*
                             * Store latest link reference only.
                             * IMPORTANT: do not change paid_amount/payment_status here.
                             */
                            $record->payment_method = 'razorpay';
                            $record->payment_reference = $paymentLinkId !== ''
                                ? $paymentLinkId
                                : $paymentLink;
                            $record->save();

                            $mobile = trim((string) ($record->customer?->mobile ?? ''));
                            $whatsAppSent = false;

                            if ($mobile !== '') {
                                $wa = WhatsAppService::sendByKey(
                                    templateKey: 'payment_reminder',
                                    number: $mobile,
                                    bodyParameters: [
                                        (string) ($record->customer?->name ?? 'Customer'),
                                        (string) ($record->booking_no ?: $record->id),
                                        number_format($amount, 2, '.', ''),
                                        $paymentLink,
                                    ]
                                );

                                $whatsAppSent = (bool) (
                                    $wa['status']
                                    ?? $wa['success']
                                    ?? false
                                );
                            }

                            Notification::make()
                                ->title(
                                    $whatsAppSent
                                        ? 'Payment link sent'
                                        : 'Payment link created'
                                )
                                ->body(
                                    '₹' . number_format($amount, 2)
                                    . ' Razorpay link: '
                                    . $paymentLink
                                    . (
                                        $whatsAppSent
                                            ? ' | WhatsApp sent.'
                                            : ' | WhatsApp template send failed/not configured.'
                                    )
                                )
                                ->success()
                                ->persistent()
                                ->send();

                            return;
                        }

                        /*
                         * CASH / UPI / BANK / CARD:
                         * Admin is confirming that money has actually been received.
                         */
                        $record->paid_amount =
                            (float) $record->paid_amount + $amount;
                        $record->payment_method = $method;
                        $record->payment_reference = $data['reference'] ?? null;
                        $record->syncPayment();

                        if (
                            $record->payment_status === 'paid'
                            && (float) $record->remaining_amount <= 0.009
                            && (
                                filled($record->trip_end_datetime)
                                && filled($record->final_bill_generated_at)
                                && (filled($record->return_otp_verified_at) || (
                                    filled($record->return_admin_confirmed_at)
                                    && filled($record->return_admin_confirmed_by)
                                    && filled($record->return_admin_reason)
                                ))
                            )
                        ) {
                            $record->status = SelfDriveBooking::STATUS_COMPLETED;
                            $record->booking_status = 'completed';
                            $record->settlement_status = 'completed';
                            $record->completed_at = $record->completed_at ?: now();
                        }

                        $record->save();

                        $record->sendSelfDriveTemplate(
                            'selfdrive_payment_received',
                            ['received_amount' => number_format($amount, 2, '.', '')]
                        );

                        Notification::make()
                            ->title('Payment received')
                            ->body(
                                '₹' . number_format($amount, 2)
                                . ' received via '
                                . Str::headline($method)
                                . '.'
                            )
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('vendor_confirm')
                    ->label('Approve Booking')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (SelfDriveBooking $record) =>
                        $record->vendor_confirmation_status === 'pending'
                        && ! in_array($record->status, [
                            SelfDriveBooking::STATUS_CANCELLED,
                            'rejected',
                            SelfDriveBooking::STATUS_COMPLETED,
                        ], true))
                    ->requiresConfirmation()
                    ->action(function (SelfDriveBooking $record): void {
                        $customer = $record->customer;
                        $approved = $customer?->isKycApproved() ?? false;
                        $complete = $customer?->hasCompleteKyc() ?? false;

                        $record->update([
                            'vendor_confirmation_status' => 'confirmed',
                            'vendor_confirmed_at' => now(),
                            'document_status' => $approved
                                ? 'approved'
                                : ($complete ? 'under_verification' : 'not_uploaded'),
                            'booking_status' => $approved
                                ? 'confirmed'
                                : ($complete
                                    ? 'documents_under_verification'
                                    : 'documents_required'),
                            'status' => $approved
                                ? SelfDriveBooking::STATUS_CONFIRMED
                                : SelfDriveBooking::STATUS_PENDING,
                            'booking_confirmed_at' => $approved ? now() : null,
                        ]);

                        Notification::make()
                            ->title('Booking approved')
                            ->body(
                                $approved
                                    ? 'Customer KYC already approved. Pickup OTP can now be generated.'
                                    : 'Customer can now upload Aadhaar and Driving Licence.'
                            )
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('vendor_reject')
                    ->label('Reject Booking')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (SelfDriveBooking $record) =>
                        $record->vendor_confirmation_status === 'pending'
                        && ! in_array($record->status, [
                            SelfDriveBooking::STATUS_CANCELLED,
                            SelfDriveBooking::STATUS_COMPLETED,
                        ], true))
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->requiresConfirmation()
                    ->action(function (SelfDriveBooking $record, array $data): void {
                        $record->update([
                            'vendor_confirmation_status' => 'rejected',
                            'vendor_rejected_at' => now(),
                            'vendor_rejection_reason' => $data['reason'],
                            'booking_status' => 'vendor_rejected',
                            'status' => SelfDriveBooking::STATUS_CANCELLED,
                            'refund_status' => (float) $record->paid_amount > 0
                                ? 'pending'
                                : 'not_applicable',
                        ]);

                        Notification::make()
                            ->title('Booking rejected')
                            ->danger()
                            ->send();
                    }),

                Tables\Actions\Action::make('approve_documents')
                    ->label('Approve Documents')
                    ->icon('heroicon-o-shield-check')
                    ->color('success')
                    ->visible(fn (SelfDriveBooking $record) =>
                        $record->vendor_confirmation_status === 'confirmed'
                        && $record->document_status === 'under_verification'
                        && ! in_array($record->status, [
                            SelfDriveBooking::STATUS_CANCELLED,
                            SelfDriveBooking::STATUS_COMPLETED,
                        ], true))
                    ->requiresConfirmation()
                    ->action(function (SelfDriveBooking $record): void {
                        if (! $record->customer?->hasCompleteKyc()) {
                            throw ValidationException::withMessages([
                                'documents' => 'Customer Aadhaar and Driving Licence are incomplete.',
                            ]);
                        }

                        $record->customer->update([
                            'kyc_status' => User::KYC_VENDOR_APPROVED,
                        ]);

                        $record->update([
                            'document_status' => 'approved',
                            'documents_verified_at' => now(),
                            'booking_confirmed_at' => now(),
                            'booking_status' => 'confirmed',
                            'status' => SelfDriveBooking::STATUS_CONFIRMED,
                        ]);

                        Notification::make()
                            ->title('Customer documents approved')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('reject_documents')
                    ->label('Reject Documents')
                    ->icon('heroicon-o-shield-exclamation')
                    ->color('danger')
                    ->visible(fn (SelfDriveBooking $record) =>
                        $record->vendor_confirmation_status === 'confirmed'
                        && $record->document_status === 'under_verification'
                        && ! in_array($record->status, [
                            SelfDriveBooking::STATUS_CANCELLED,
                            SelfDriveBooking::STATUS_COMPLETED,
                        ], true))
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->action(function (SelfDriveBooking $record, array $data): void {
                        $record->customer?->update([
                            'kyc_status' => User::KYC_VENDOR_REJECTED,
                        ]);

                        $record->update([
                            'document_status' => 'rejected',
                            'documents_rejected_at' => now(),
                            'document_rejection_reason' => $data['reason'],
                            'booking_status' => 'documents_rejected',
                            'status' => SelfDriveBooking::STATUS_PENDING,
                        ]);

                        Notification::make()
                            ->title('Customer documents rejected')
                            ->danger()
                            ->send();
                    }),

                Tables\Actions\Action::make('generate_pickup_otp')
                    ->label('Pickup OTP')
                    ->icon('heroicon-o-key')
                    ->visible(fn (SelfDriveBooking $record) =>
                        $record->vendor_confirmation_status === 'confirmed'
                        && $record->document_status === 'approved'
                        && blank($record->pickup_otp_verified_at)
                        && ! in_array($record->status, [
                            SelfDriveBooking::STATUS_RUNNING,
                            SelfDriveBooking::STATUS_COMPLETED,
                            SelfDriveBooking::STATUS_CANCELLED,
                        ], true))
                    ->requiresConfirmation()
                    ->action(function (SelfDriveBooking $record): void {
                        $otp = (string) random_int(1000, 9999);

                        $record->update([
                            'pickup_otp' => $otp,
                            'pickup_otp_generated_at' => now(),
                            'pickup_otp_expires_at' => now()->addMinutes(30),
                            'pickup_otp_attempts' => 0,
                            'booking_status' => 'pickup_otp_generated',
                            'status' => SelfDriveBooking::STATUS_CONFIRMED,
                        ]);

                        $record->sendSelfDriveTemplate(
                            'selfdrive_pickup_otp',
                            ['otp' => $otp]
                        );

                        Notification::make()
                            ->title("Pickup OTP: {$otp}")
                            ->body('OTP verification ke baad customer Start KM aur pickup photos upload karega.')
                            ->success()
                            ->persistent()
                            ->send();
                    }),

                Tables\Actions\Action::make('verify_pickup_otp')
                    ->label('Verify Pickup OTP')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (SelfDriveBooking $record) =>
                        filled($record->pickup_otp)
                        && blank($record->pickup_otp_verified_at)
                        && ! in_array($record->status, [
                            SelfDriveBooking::STATUS_RUNNING,
                            SelfDriveBooking::STATUS_COMPLETED,
                            SelfDriveBooking::STATUS_CANCELLED,
                        ], true))
                    ->form([
                        Forms\Components\TextInput::make('otp')
                            ->label('Pickup OTP')
                            ->required()
                            ->numeric(),
                        Forms\Components\TextInput::make('start_km')
                            ->label('Start KM')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                    ])
                    ->action(function (SelfDriveBooking $record, array $data): void {
                        if (
                            blank($record->pickup_otp)
                            || ! hash_equals((string) $record->pickup_otp, (string) $data['otp'])
                        ) {
                            throw ValidationException::withMessages([
                                'otp' => 'Pickup OTP galat hai.',
                            ]);
                        }

                        if (
                            $record->pickup_otp_expires_at
                            && now()->gt($record->pickup_otp_expires_at)
                        ) {
                            throw ValidationException::withMessages([
                                'otp' => 'Pickup OTP expire ho chuka hai. Naya OTP generate karein.',
                            ]);
                        }

                        $record->update([
                            'pickup_otp_verified_at' => now(),
                            'trip_start_datetime' => now(),
                            'start_km' => $data['start_km'],
                            'booking_status' => 'running',
                            'status' => SelfDriveBooking::STATUS_RUNNING,
                        ]);

                        Notification::make()
                            ->title('Pickup OTP verified')
                            ->body('Trip status Running kar diya gaya hai.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('start_trip')
                    ->label('Start Trip Override')
                    ->icon('heroicon-o-play')
                    ->color('warning')
                    ->visible(fn (SelfDriveBooking $record) =>
                        ! static::isTransporterPanel()
                        && filled($record->pickup_otp_verified_at)
                        && $record->status !== SelfDriveBooking::STATUS_RUNNING
                        && ! in_array($record->status, [
                            SelfDriveBooking::STATUS_COMPLETED,
                            SelfDriveBooking::STATUS_CANCELLED,
                        ], true))
                    ->form([
                        Forms\Components\TextInput::make('start_km')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                        Forms\Components\Textarea::make('reason')
                            ->label('Admin Override Reason')
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->action(function (SelfDriveBooking $record, array $data): void {
                        $record->update([
                            'trip_start_datetime' => now(),
                            'start_km' => $data['start_km'],
                            'damage_note' => $data['reason'],
                            'booking_status' => 'running',
                            'status' => SelfDriveBooking::STATUS_RUNNING,
                        ]);

                        Notification::make()
                            ->title('Trip started with admin override')
                            ->warning()
                            ->send();
                    }),

                Tables\Actions\Action::make('generate_return_otp')
                    ->label('End OTP')->icon('heroicon-o-key')
                    ->visible(fn (SelfDriveBooking $record) =>
                        ! static::isTransporterPanel() && auth()->user()?->canUseAdminLogin()
                        && $record->status === SelfDriveBooking::STATUS_RUNNING)
                    ->requiresConfirmation()
                    ->action(function (SelfDriveBooking $record): void {
                        $otp = (string) random_int(1000, 9999);
                        $record->update([
                            'return_otp' => $otp,
                            'return_otp_generated_at' => now(),
                            'return_otp_expires_at' => now()->addMinutes(30),
                            'return_otp_attempts' => 0,
                            'end_requested_at' => now(),
                            'booking_status' => 'end_otp_generated',
                            'status' => SelfDriveBooking::STATUS_RUNNING,
                        ]);

                        $record->sendSelfDriveTemplate(
                            'selfdrive_return_otp',
                            ['otp' => $otp]
                        );

                        Notification::make()->title("End OTP: {$otp}")
                            ->success()->persistent()->send();
                    }),

                static::adminReturnAction(),

                Tables\Actions\Action::make('invoice_pdf')
                    ->label('Invoice PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->visible(fn (): bool => Route::has('invoice.shared'))
                    ->url(fn (SelfDriveBooking $record): string =>
                        URL::temporarySignedRoute(
                            'invoice.shared',
                            now()->addMinutes(30),
                            [
                                'booking' => $record->booking_no,
                            ]
                        )
                    )
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('rental_agreement_pdf')
                    ->label('Rental Agreement PDF')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->visible(fn (): bool =>
                        Route::has('self-drive.agreement.shared'))
                    ->url(fn (SelfDriveBooking $record): string =>
                        URL::temporarySignedRoute(
                            'self-drive.agreement.shared',
                            now()->addMinutes(30),
                            [
                                'booking' => $record->booking_no,
                            ]
                        )
                    )
                    ->openUrlInNewTab(),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => ! static::isTransporterPanel()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => ! static::isTransporterPanel()),
                ]),
            ]);
    }

    public static function securityRefundForm(): array
    {
        return [
            Forms\Components\Placeholder::make('security_summary')->label('Security refund summary / history')
                ->content(function (SelfDriveBooking $record): string {
                    $summary = \App\Services\SelfDriveSecurityRefundService::summary($record);
                    $text = 'Security: ₹'.$summary['deposit'].' | Refunded: ₹'.$summary['refunded']
                        .' | Pending: '.($summary['pending'] === null ? 'Legacy refund requires review' : '₹'.$summary['pending']);
                    foreach ($summary['history'] as $entry) {
                        $text .= ' | ₹'.$entry['amount'].' / '.ucfirst($entry['method']).' / '.$entry['refunded_at']
                            .' / '.($entry['reference'] ?: 'Cash').' / '.$entry['note'];
                    }
                    return $text;
                }),
            Forms\Components\Placeholder::make('refund_help')->label('Before recording')
                ->content('Record only money already returned to the customer. This action does not send money. Return and final bill must be complete, and customer balance must be fully paid. Return charges are already in the rental bill; do not deduct them again from security.'),
            Forms\Components\TextInput::make('amount')->label('Amount actually refunded')->prefix('₹')
                ->numeric()->required()->minValue(0.01)
                ->default(fn (SelfDriveBooking $record) => \App\Services\SelfDriveSecurityRefundService::summary($record)['pending']),
            Forms\Components\Select::make('method')->label('Refund mode')->options(['cash' => 'Cash', 'online' => 'Online (UPI / Bank / Gateway)'])
                ->required()->live(),
            Forms\Components\TextInput::make('reference')->label('Transaction ID / reference')->maxLength(200)
                ->required(fn (Get $get) => $get('method') === 'online'),
            Forms\Components\DateTimePicker::make('refunded_at')->label('Actual refund date / time')
                ->required()->maxDate(now())->default(now()),
            Forms\Components\Textarea::make('note')->label('Refund note / receipt details')->required()->minLength(3)->maxLength(2000),
            Forms\Components\Hidden::make('request_key')->default(fn () => (string) Str::uuid()),
            Forms\Components\Toggle::make('confirmed')->label('I confirm this amount has actually been returned to the customer')
                ->accepted()->required(),
        ];
    }

    public static function securityRefundAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('security_refund')->label('Security Refund')->icon('heroicon-o-arrow-uturn-left')
            ->visible(fn (SelfDriveBooking $record) => ! static::isTransporterPanel()
                && auth()->user()?->canUseAdminLogin() && $record->booking_type === 'car'
                && (float) $record->security_deposit > 0)
            ->form(static::securityRefundForm())
            ->action(function (SelfDriveBooking $record, array $data): void {
                abort_unless(! static::isTransporterPanel() && auth()->user()?->canUseAdminLogin(), 403);
                app(\App\Services\SelfDriveSecurityRefundService::class)->record($record->id, $data, (int) auth()->id());
                Notification::make()->title('Actual security refund recorded')->success()->send();
            });
    }

    public static function returnInput(Get $get): array
    {
        $result = [];
        foreach (['returned_at', 'end_km', 'fuel', 'note', 'damage_amount', 'fuel_charge',
            'cleaning_charge', 'other_charge', 'reason', 'started_at', 'start_km'] as $key) {
            $result[$key] = $get($key);
        }
        return $result;
    }

    public static function returnOverrideForm(): array
    {
        return [
            Forms\Components\Placeholder::make('request_reason')->label('Host request / previous approval')
                ->content(fn (SelfDriveBooking $record) => $record->return_draft['admin_request']['reason']
                    ?? $record->return_admin_reason ?? 'No host request. Confirm actual return before overriding.'),
            Forms\Components\Placeholder::make('return_photos')->label('Host return photos')
                ->content(function (SelfDriveBooking $record) {
                    $links = [];
                    foreach (array_keys($record->return_draft['photos'] ?? []) as $slot) {
                        if (! in_array($slot, ['front','back','left','right'], true)) continue;
                        $url = url('api/v1/partner/admin-return/'.$record->id.'/photo/'.$slot);
                        $links[] = '<a href="'.e($url).'" target="_blank" rel="noopener noreferrer">View '.e($slot).' photo</a>';
                    }
                    return new \Illuminate\Support\HtmlString($links ? implode(' | ', $links) : 'No return photos uploaded.');
                }),
            Forms\Components\DateTimePicker::make('started_at')->label('Actual pickup date / time')
                ->default(fn (SelfDriveBooking $record) => $record->trip_start_datetime
                    ?? $record->return_draft['details']['started_at'] ?? $record->start_datetime)
                ->helperText(fn (SelfDriveBooking $record) => $record->trip_start_datetime
                    ? 'Recorded actual pickup time will be used.'
                    : 'Booking pickup time is prefilled. Confirm or correct it to the actual pickup time before submitting.')
                ->required(fn (SelfDriveBooking $record) => ! $record->trip_start_datetime)
                ->maxDate(now())->live(onBlur: true),
            Forms\Components\TextInput::make('start_km')->label('Start KM (only if missing)')->numeric()->minValue(0)
                ->default(fn (SelfDriveBooking $record) => $record->start_km)->live(onBlur: true),
            Forms\Components\DateTimePicker::make('returned_at')->label('Actual return date / time')->required()->maxDate(now())
                ->default(fn (SelfDriveBooking $record) => $record->return_draft['details']['returned_at'] ?? $record->trip_end_datetime)
                ->live(onBlur: true),
            Forms\Components\TextInput::make('end_km')->numeric()->required()->minValue(0)
                ->default(fn (SelfDriveBooking $record) => $record->return_draft['details']['end_km'] ?? $record->end_km)->live(onBlur: true),
            Forms\Components\Select::make('fuel')->label('Return fuel')->required()
                ->options(['empty'=>'Empty','quarter'=>'1/4','half'=>'1/2','three_quarters'=>'3/4','full'=>'Full'])
                ->default(fn (SelfDriveBooking $record) => $record->return_draft['details']['fuel'] ?? $record->drop_fuel_level)->live(),
            ...array_map(fn ($key) => Forms\Components\TextInput::make($key)->numeric()->minValue(0)->prefix('₹')
                ->default(fn (SelfDriveBooking $record) => $record->{$key} ?? 0)->live(onBlur: true),
                ['damage_amount','fuel_charge','cleaning_charge','other_charge']),
            Forms\Components\Textarea::make('note')->label('Inspection / extra charge note')->maxLength(2000)->live(onBlur: true)
                ->required(fn (Get $get) => collect(['damage_amount', 'fuel_charge', 'cleaning_charge', 'other_charge'])
                    ->contains(fn ($key) => is_numeric($get($key)) && (float) $get($key) > 0))
                ->helperText('Explain any damage, fuel, cleaning or other charge greater than zero.')
                ->default(fn (SelfDriveBooking $record) => $record->return_draft['details']['note'] ?? $record->damage_note),
            Forms\Components\Textarea::make('reason')->label('Why OTP / photos are being skipped')->required()->minLength(10)
                ->maxLength(2000)->live(onBlur: true),
            Forms\Components\Placeholder::make('bill_preview')->label('Final bill preview')
                ->content(function (SelfDriveBooking $record, Get $get): string {
                    try {
                        $bill = app(\App\Services\PartnerReturnCompletionService::class)->preview($record, static::returnInput($get), true);
                        return 'Base rental: ₹'.number_format($bill['base_rental'], 2)
                            .' | Extra time: ₹'.number_format($bill['extra_hour_amount'], 2)
                            .' | Extra KM: ₹'.number_format($bill['extra_km_amount'], 2)
                            .' | Damage / fuel / cleaning / late / other: ₹'.number_format(
                                $bill['damage_amount'] + $bill['fuel_charge'] + $bill['cleaning_charge']
                                + $bill['late_return_charge'] + $bill['other_charge'], 2)
                            .' | Rental including return charges: ₹'.number_format($bill['rental'], 2)
                            .' | Security: ₹'.number_format($bill['security_deposit'], 2)
                            .' | Payable: ₹'.number_format($bill['payable'], 2)
                            .' | Paid: ₹'.number_format($bill['paid'], 2)
                            .' | Balance: ₹'.number_format($bill['balance_due'], 2);
                    } catch (ValidationException $e) {
                        return collect($e->errors())->flatten()->first() ?? 'Fill actual return details.';
                    }
                }),
            Forms\Components\Hidden::make('confirmation'),
            Forms\Components\Toggle::make('reviewed')->label('I confirmed the actual return and reviewed this bill')
                ->accepted()->required()->live()
                ->afterStateUpdated(function ($state, SelfDriveBooking $record, Get $get, Set $set): void {
                    $set('confirmation', null);
                    if (! $state) return;
                    try {
                        $set('confirmation', app(\App\Services\PartnerReturnCompletionService::class)
                            ->preview($record, static::returnInput($get), true)['confirmation']);
                    } catch (ValidationException $e) {
                        $set('reviewed', false);
                        Notification::make()->title('Complete the return details first')
                            ->body(collect($e->errors())->flatten()->first() ?? 'Check the final bill preview.')
                            ->warning()->send();
                    }
                }),
        ];
    }

    public static function adminReturnAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('admin_return_bill')->label('Admin Return & Bill')->color('warning')
            ->icon('heroicon-o-document-check')
            ->visible(fn (SelfDriveBooking $record) => ! static::isTransporterPanel()
                && auth()->user()?->canUseAdminLogin()
                && ! in_array($record->status, ['cancelled','rejected','failed'], true)
                && (! $record->final_bill_generated_at || (! $record->return_otp_verified_at && ! $record->return_admin_confirmed_at)))
            ->form(static::returnOverrideForm())
            ->action(function (SelfDriveBooking $record, array $data): void {
                abort_unless(! static::isTransporterPanel() && auth()->user()?->canUseAdminLogin(), 403);
                try {
                    $done = app(\App\Services\PartnerReturnCompletionService::class)->finish($record->id, $data, (int) auth()->id(), true);
                } catch (ValidationException $e) {
                    Notification::make()->title('Return could not be saved')
                        ->body(collect($e->errors())->flatten()->implode(' '))->danger()->persistent()->send();
                    throw ValidationException::withMessages([
                        'mountedTableActionsData.0.reviewed' => collect($e->errors())->flatten()->first(),
                    ]);
                } catch (\Throwable $e) {
                    report($e);
                    $message = 'Server error while saving return. Nothing was committed. Check the Laravel log for this attempt.';
                    Notification::make()->title('Return could not be saved')->body($message)->danger()->persistent()->send();
                    throw ValidationException::withMessages(['mountedTableActionsData.0.reviewed' => $message]);
                }
                Notification::make()->title($done->status === 'completed' ? 'Return approved, bill generated and trip completed'
                    : 'Return approved and bill generated; customer payment is pending')->success()->send();
            });
    }

    public static function syncPaymentFields(Get $get, Set $set): void
    {
        /*
         * Customer payable = GST-inclusive Rental Total + Refundable Security Deposit.
         * Security is collected separately from rental income, but it is part of the
         * amount that must be received before the booking can be fully paid/completed.
         */
        $payable = max(
            0,
            (float) ($get('final_amount') ?: 0)
            + (float) ($get('security_deposit') ?: 0)
        );

        $type = (string) ($get('payment_type') ?: 'advance');

        if ($type === 'full') {
            $paid = $payable;
            $advance = 0;
        } elseif ($type === 'custom') {
            $paid = min(
                $payable,
                max(0, (float) ($get('paid_amount') ?: 0))
            );
            $advance = 0;
        } else {
            $paid = min(500, $payable);
            $advance = $paid;
        }

        $set('advance_amount', round($advance, 2));
        $set('paid_amount', round($paid, 2));
        $set('remaining_amount', round(max(0, $payable - $paid), 2));
        $set(
            'payment_status',
            $paid <= 0
                ? 'pending'
                : ($paid < $payable ? 'partial' : 'paid')
        );
    }

    public static function paymentMethods(): array
    {
        return [
            'cash' => 'Cash',
            'upi' => 'UPI',
            'card' => 'Card',
            'bank_transfer' => 'Bank Transfer',
            'razorpay_link' => 'Razorpay Payment Link',
            'online' => 'Online / Razorpay',
        ];
    }

    public static function paymentStatuses(): array
    {
        return [
            'pending' => 'Pending',
            'partial' => 'Partial',
            'paid' => 'Paid',
            'refunded' => 'Refunded',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'running' => 'Running',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
    }

    public static function workflowOptions(): array
    {
        return [
            'pending_vendor_confirmation' => 'Pending Vendor Confirmation',
            'documents_required' => 'Documents Required',
            'documents_under_verification' => 'Documents Under Verification',
            'documents_rejected' => 'Documents Rejected',
            'confirmed' => 'Confirmed',
            'pickup_otp_generated' => 'Pickup OTP Generated',
            'pickup_inspection_required' => 'Pickup Photos + Start KM Required',
            'running' => 'Running',
            'end_otp_generated' => 'End OTP Generated',
            'inspection_pending' => 'Inspection Pending',
            'final_bill_pending' => 'Final Bill Pending',
            'final_bill_generated' => 'Final Bill Generated',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSelfDriveBookings::route('/'),
            'create' => Pages\CreateSelfDriveBooking::route('/create'),
            'edit' => Pages\EditSelfDriveBooking::route('/{record}/edit'),
        ];
    }
}
