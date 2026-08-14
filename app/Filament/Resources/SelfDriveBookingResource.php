<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SelfDriveBookingResource\Pages;
use App\Models\FleetManagement\TransporterProfile;
use App\Models\SelfDriveBooking;
use App\Models\User;
use App\Models\Vehicle;
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
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SelfDriveBookingResource extends Resource
{
    protected static ?string $model = SelfDriveBooking::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Self Drive';
    protected static ?string $navigationLabel = 'Bookings';
    protected static ?int $navigationSort = 2;

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
            Forms\Components\Section::make('Booking')
                ->schema([
                    Forms\Components\TextInput::make('booking_no')
                        ->label('Booking ID')
                        ->placeholder('Auto generated after booking is saved')
                        ->readOnly()
                        ->dehydrated(false),

                    Forms\Components\Select::make('customer_id')
                        ->label('Customer')
                        ->relationship('customer', 'name')
                        ->getOptionLabelFromRecordUsing(
                            fn (User $record) =>
                                ($record->name ?: 'No Name') . ' | ' .
                                ($record->mobile ?: 'No Mobile')
                        )
                        ->searchable(['name', 'mobile'])->preload()->required()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')->required(),
                            Forms\Components\TextInput::make('mobile')->tel()->required(),
                        ])
                        ->createOptionUsing(function (array $data): int {
                            $mobile = preg_replace('/\D+/', '', $data['mobile']);

                            return User::query()->firstOrCreate(
                                ['mobile' => $mobile],
                                [
                                    'name' => $data['name'],
                                    'password' => bcrypt(Str::random(24)),
                                ]
                            )->id;
                        }),

                    Forms\Components\Select::make('vehicle_id')
                        ->label('Vehicle')
                        ->options(fn () => Vehicle::query()
                            ->with('transporter')
                            ->where('is_active', true)
                            ->get()
                            ->mapWithKeys(function (Vehicle $vehicle): array {
                                $name = trim(
                                    ($vehicle->car_company_name ?? '') . ' ' .
                                    ($vehicle->model_name ?? '')
                                );
                                $number = $vehicle->vehicle_number ?: 'No Number';
                                $partner = $vehicle->transporter?->company_name ?: 'No Partner';

                                return [$vehicle->id => "{$name} | {$number} | {$partner}"];
                            })->all())
                        ->searchable()->preload()->required()->live()
                        ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                            $vehicle = Vehicle::query()->find($state);

                            if (! $vehicle) {
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
                            static::recalculate($get, $set);
                        }),

                    Forms\Components\Hidden::make('transporter_profile_id'),
                ])->columns(3),

            Forms\Components\Section::make('Pickup & Duration')
                ->schema([
                    Forms\Components\Textarea::make('pickup_location')
                        ->required()->columnSpanFull(),
                    Forms\Components\TextInput::make('pickup_latitude')->numeric(),
                    Forms\Components\TextInput::make('pickup_longitude')->numeric(),
                    Forms\Components\DateTimePicker::make('start_datetime')
                        ->seconds(false)->required()->live()
                        ->afterStateUpdated(fn (Get $get, Set $set) =>
                            static::recalculate($get, $set)),
                    Forms\Components\DateTimePicker::make('end_datetime')
                        ->seconds(false)->required()->after('start_datetime')->live()
                        ->afterStateUpdated(fn (Get $get, Set $set) =>
                            static::recalculate($get, $set)),
                    Forms\Components\TextInput::make('booked_hours')
                        ->numeric()->readOnly()->dehydrated(),
                    Forms\Components\TextInput::make('total_days')
                        ->numeric()->readOnly()->dehydrated(),
                    Forms\Components\TextInput::make('minimum_booking_hours')
                        ->numeric()->readOnly()->dehydrated(),
                ])->columns(4),

            Forms\Components\Section::make('Fare & Booking Payment')
                ->schema([
                    Forms\Components\TextInput::make('hourly_price')
                        ->numeric()->prefix('₹')->live()
                        ->afterStateUpdated(fn (Get $get, Set $set) =>
                            static::recalculate($get, $set)),
                    Forms\Components\TextInput::make('price_per_day')
                        ->numeric()->prefix('₹'),
                    Forms\Components\TextInput::make('security_deposit')
                        ->numeric()->prefix('₹'),
                    Forms\Components\TextInput::make('total_amount')
                        ->label('Booking Amount (Manual Editable)')
                        ->numeric()->prefix('₹')->required()->live()
                        ->afterStateUpdated(fn (Get $get, Set $set) =>
                            static::syncPaymentFields($get, $set)),
                    Forms\Components\Select::make('payment_type')
                        ->options([
                            'advance' => '₹500 Advance',
                            'full' => 'Full Payment',
                        ])
                        ->required()->default('advance')->native(false)->live()
                        ->afterStateUpdated(fn (Get $get, Set $set) =>
                            static::syncPaymentFields($get, $set)),
                    Forms\Components\Select::make('payment_method')
                        ->options(static::paymentMethods())
                        ->required()->default('cash')->native(false),
                    Forms\Components\TextInput::make('payment_reference')
                        ->label('Transaction / Reference'),
                    Forms\Components\TextInput::make('advance_amount')
                        ->numeric()->prefix('₹')->readOnly()->dehydrated(),
                    Forms\Components\TextInput::make('paid_amount')
                        ->numeric()->prefix('₹')->readOnly()->dehydrated(),
                    Forms\Components\TextInput::make('remaining_amount')
                        ->numeric()->prefix('₹')->readOnly()->dehydrated(),
                    Forms\Components\Select::make('payment_status')
                        ->options(static::paymentStatuses())
                        ->disabled()
                        ->dehydrated()
                        ->native(false),
                    Forms\Components\TextInput::make('final_amount')
                        ->label('Final Amount (Manual Editable)')
                        ->numeric()->prefix('₹')->live()
                        ->afterStateUpdated(fn (Get $get, Set $set) =>
                            static::syncPaymentFields($get, $set)),
                ])->columns(4),

            Forms\Components\Section::make('Workflow')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->options(static::statusOptions())
                        ->required()->default(SelfDriveBooking::STATUS_PENDING)
                        ->native(false),
                    Forms\Components\Select::make('booking_status')
                        ->options(static::workflowOptions())
                        ->default('pending_vendor_confirmation')->native(false),
                    Forms\Components\Select::make('vendor_confirmation_status')
                        ->options([
                            'pending' => 'Pending',
                            'confirmed' => 'Confirmed',
                            'rejected' => 'Rejected',
                        ])->default('pending')->native(false),
                    Forms\Components\Select::make('document_status')
                        ->options([
                            'not_uploaded' => 'Not Uploaded',
                            'under_verification' => 'Under Verification',
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                        ])->default('not_uploaded')->native(false),
                ])->columns(4),

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
                        ])->default('pending')->native(false),
                ])->columns(4)->collapsed(),

            Forms\Components\Section::make('OTP & Trip Times')
                ->schema([
                    Forms\Components\TextInput::make('pickup_otp')->readOnly(),
                    Forms\Components\DateTimePicker::make('pickup_otp_verified_at')->readOnly(),
                    Forms\Components\DateTimePicker::make('trip_start_datetime')->readOnly(),
                    Forms\Components\TextInput::make('return_otp')->readOnly(),
                    Forms\Components\DateTimePicker::make('return_otp_verified_at')->readOnly(),
                    Forms\Components\DateTimePicker::make('trip_end_datetime')->readOnly(),
                ])->columns(3)->collapsed(),
				
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
    ->collapsible(),	
        ]);
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
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Amount')->money('INR')->sortable(),
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
                Tables\Columns\TextColumn::make('status')
                    ->badge()->color(fn ($state) => match ($state) {
                        'running' => 'success',
                        'completed' => 'info',
                        'cancelled', 'rejected', 'failed' => 'danger',
                        default => 'warning',
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
                Tables\Actions\EditAction::make()
                    ->visible(fn () => ! static::isTransporterPanel()),

                Tables\Actions\Action::make('receive_payment')
                    ->label('Receive Payment')->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (SelfDriveBooking $record) =>
                        ! static::isTransporterPanel()
                        && (float) $record->remaining_amount > 0
                        && ! in_array($record->status, ['cancelled', 'rejected'], true))
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->numeric()->prefix('₹')->required()->minValue(1),
                        Forms\Components\Select::make('method')
                            ->options(static::paymentMethods())->required()->native(false),
                        Forms\Components\TextInput::make('reference'),
                    ])
                    ->action(function (SelfDriveBooking $record, array $data): void {
                        $amount = (float) $data['amount'];
                        $balance = (float) $record->remaining_amount;

                        if ($amount > $balance) {
                            throw ValidationException::withMessages([
                                'amount' => 'Amount current balance se zyada nahi ho sakta.',
                            ]);
                        }

                        $record->paid_amount =
                            (float) $record->paid_amount + $amount;
                        $record->payment_method = $data['method'];
                        $record->payment_reference = $data['reference'] ?? null;
                        $record->syncPayment();
                        $record->save();

                        Notification::make()
                            ->title('Payment updated')
                            ->body('₹' . number_format($amount, 2) . ' received.')
                            ->success()->send();
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

                        Notification::make()
                            ->title("Pickup OTP: {$otp}")
                            ->body('OTP verification ke baad customer Start KM aur pickup photos upload karega.')
                            ->success()
                            ->persistent()
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
                        $record->status === SelfDriveBooking::STATUS_RUNNING)
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
                            'status' => SelfDriveBooking::STATUS_RETURN_PENDING,
                        ]);

                        Notification::make()->title("End OTP: {$otp}")
                            ->success()->persistent()->send();
                    }),

                Tables\Actions\Action::make('end_trip')
                    ->label('End Trip')->icon('heroicon-o-stop')
                    ->color('danger')
                    ->visible(fn (SelfDriveBooking $record) =>
                        in_array($record->status, [
                            SelfDriveBooking::STATUS_RUNNING,
                            SelfDriveBooking::STATUS_RETURN_PENDING,
                        ], true))
                    ->form([
                        Forms\Components\TextInput::make('otp')
                            ->label('End OTP')->required(),
                        Forms\Components\TextInput::make('end_km')
                            ->numeric()->required()->minValue(0),
                        Forms\Components\TextInput::make('damage_amount')
                            ->numeric()->prefix('₹')->default(0),
                        Forms\Components\TextInput::make('fuel_charge')
                            ->numeric()->prefix('₹')->default(0),
                        Forms\Components\TextInput::make('cleaning_charge')
                            ->numeric()->prefix('₹')->default(0),
                        Forms\Components\TextInput::make('other_charge')
                            ->numeric()->prefix('₹')->default(0),
                        Forms\Components\Textarea::make('reason')
                            ->label('Admin/Vendor Completion Note')->required(),
                    ])
                    ->action(function (SelfDriveBooking $record, array $data): void {
                        if (
                            blank($record->return_otp) ||
                            ! hash_equals((string) $record->return_otp, (string) $data['otp'])
                        ) {
                            throw ValidationException::withMessages([
                                'otp' => 'End OTP galat hai.',
                            ]);
                        }

                        if ((float) $data['end_km'] < (float) $record->start_km) {
                            throw ValidationException::withMessages([
                                'end_km' => 'End KM, Start KM se kam nahi ho sakta.',
                            ]);
                        }

                        $record->fill([
                            'return_otp_verified_at' => now(),
                            'trip_end_datetime' => now(),
                            'end_km' => $data['end_km'],
                            'damage_amount' => $data['damage_amount'] ?? 0,
                            'fuel_charge' => $data['fuel_charge'] ?? 0,
                            'cleaning_charge' => $data['cleaning_charge'] ?? 0,
                            'other_charge' => $data['other_charge'] ?? 0,
                            'damage_note' => $data['reason'],
                            'booking_status' => 'completed',
                            'status' => SelfDriveBooking::STATUS_COMPLETED,
                            'settlement_status' => 'completed',
                            'completed_at' => now(),
                        ]);

                        $record->refreshTripAmounts();
                        $record->save();
                    }),

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

    public static function recalculate(Get $get, Set $set): void
    {
        $start = $get('start_datetime');
        $end = $get('end_datetime');

        if (! $start || ! $end) {
            return;
        }

        $startAt = Carbon::parse($start);
        $endAt = Carbon::parse($end);

        if ($endAt->lte($startAt)) {
            return;
        }

        $hours = max(1, (int) ceil($startAt->diffInMinutes($endAt) / 60));
        $minimum = max(1, (int) ($get('minimum_booking_hours') ?: 1));
        $billableHours = max($hours, $minimum);
        $hourlyPrice = max(0, (float) ($get('hourly_price') ?: 0));

        $set('booked_hours', $hours);
        $set('total_days', max(1, (int) ceil($hours / 24)));
        $set('total_amount', round($billableHours * $hourlyPrice, 2));
        $set('final_amount', round($billableHours * $hourlyPrice, 2));

        static::syncPaymentFields($get, $set);
    }

    public static function syncPaymentFields(Get $get, Set $set): void
    {
        $payable = max(
            0,
            (float) ($get('final_amount') ?: $get('total_amount') ?: 0)
        );
        $type = $get('payment_type') ?: 'advance';
        $paid = $type === 'full' ? $payable : min(500, $payable);

        $set('advance_amount', $type === 'advance' ? $paid : 0);
        $set('paid_amount', $paid);
        $set('remaining_amount', max(0, $payable - $paid));
        $set('payment_status', $paid <= 0
            ? 'pending'
            : ($paid < $payable ? 'partial' : 'paid'));
    }

    public static function paymentMethods(): array
    {
        return [
            'cash' => 'Cash',
            'upi' => 'UPI',
            'card' => 'Card',
            'bank_transfer' => 'Bank Transfer',
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
            'payment_pending' => 'Payment Pending',
            'confirmed' => 'Confirmed',
            'pickup_pending' => 'Pickup Pending',
            'running' => 'Running',
            'return_pending' => 'Return Pending',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'rejected' => 'Rejected',
            'failed' => 'Failed',
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