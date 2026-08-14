<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers\AddressRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\OrdersRelationManager;
use App\Models\User;
use App\Models\Wallet;
use App\Services\OtpService;
use App\Services\WalletService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $recordTitleAttribute = 'name';
    protected static ?int $navigationSort = 1;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Setting';

    protected static ?string $navigationLabel = 'All Users';

    protected static ?string $modelLabel = 'User';

    protected static ?string $pluralModelLabel = 'All Users';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Basic Details')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('mobile')
                        ->label('Mobile Number')
                        ->tel()
                        ->maxLength(15)
                        ->unique(ignoreRecord: true),

                    Forms\Components\TextInput::make('email')
                        ->label('Email Address')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),

                    Forms\Components\DateTimePicker::make(
                        'email_verified_at'
                    )
                        ->label('Email Verified At'),

                    Forms\Components\Select::make('login_type')
                        ->label('Login Source')
                        ->options([
                            'otp' => 'Flutter / OTP',
                            'website' => 'Website',
                            'email' => 'Email',
                            'google' => 'Google',
                            'firebase' => 'Firebase',
                            'whatsapp' => 'WhatsApp',
                            'facebook' => 'Facebook',
                            'apple' => 'Apple',
                            'guest' => 'Guest',
                        ])
                        ->searchable()
                        ->native(false),

                    Forms\Components\TextInput::make('password')
                        ->label('Password')
                        ->password()
                        ->revealable()
                        ->dehydrated(
                            fn ($state): bool => filled($state)
                        )
                        ->required(
                            fn ($livewire): bool =>
                                $livewire instanceof CreateRecord
                        ),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->disabled(
                            fn (): bool =>
                                ! Auth::user()?->hasAnyRole([
                                    'Admin',
                                    'Transporter',
                                ])
                        ),
                ])
                ->columns(2),

            Forms\Components\Section::make('Role')
                ->schema([
                    Forms\Components\Select::make('roles')
                        ->label('User Role')
                        ->relationship('roles', 'name')
                        ->multiple()
                        ->preload()
                        ->searchable(),
                ])
                ->visible(
                    fn (): bool =>
                        Auth::user()?->hasAnyRole([
                            'Admin',
                            'Transporter',
                        ]) ?? false
                ),

            Forms\Components\Section::make('Company Details')
                ->schema([
                    Forms\Components\TextInput::make('company_name')
                        ->label('Company Name'),

                    Forms\Components\TextInput::make('gst_number')
                        ->label('GST Number'),

                    Forms\Components\Textarea::make('office_address')
                        ->label('Office Address')
                        ->columnSpanFull(),

                    Forms\Components\FileUpload::make('gst_image')
                        ->label('GST Image')
                        ->image(),

                    Forms\Components\FileUpload::make('aadhar_image')
                        ->label('Aadhaar Image')
                        ->image(),
                ])
                ->columns(2)
                ->collapsed(),

            Forms\Components\Section::make('Customer KYC')
                ->schema([
                    Forms\Components\TextInput::make('aadhar_number')
                        ->label('Aadhaar Number'),

                    Forms\Components\TextInput::make(
                        'driving_licence_number'
                    )
                        ->label('Driving Licence Number'),

                    Forms\Components\FileUpload::make('aadhar_front')
                        ->label('Aadhaar Front')
                        ->image(),

                    Forms\Components\FileUpload::make('aadhar_back')
                        ->label('Aadhaar Back')
                        ->image(),

                    Forms\Components\FileUpload::make(
                        'driving_licence_front'
                    )
                        ->label('Licence Front')
                        ->image(),

                    Forms\Components\FileUpload::make(
                        'driving_licence_back'
                    )
                        ->label('Licence Back')
                        ->image(),

                    Forms\Components\Select::make('kyc_status')
                        ->label('KYC Status')
                        ->options([
                            User::KYC_NOT_UPLOADED =>
                                'Not Uploaded',
                            User::KYC_UPLOADED =>
                                'Uploaded',
                            User::KYC_VENDOR_APPROVED =>
                                'Approved',
                            User::KYC_VENDOR_REJECTED =>
                                'Rejected',
                        ])
                        ->native(false),
                ])
                ->columns(2)
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->placeholder('No Name'),

                Tables\Columns\TextColumn::make('mobile')
                    ->label('Mobile')
                    ->searchable()
                    ->copyable()
                    ->placeholder('No Mobile'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->separator(',')
                    ->placeholder('Customer'),

                Tables\Columns\TextColumn::make('login_type')
                    ->label('Login Source')
                    ->badge()
                    ->formatStateUsing(
                        fn ($state): string =>
                            static::loginSourceLabel($state)
                    ),

                Tables\Columns\TextColumn::make('wallet_balance')
                    ->label('Wallet')
                    ->getStateUsing(
                        fn (User $record): float =>
                            (float) (
                                Wallet::query()
                                    ->where('user_id', $record->id)
                                    ->where('wallet_type', 'customer')
                                    ->value('balance') ?? 0
                            )
                    )
                    ->formatStateUsing(
                        fn ($state): string =>
                            '₹' . number_format((float) $state, 2)
                    )
                    ->badge()
                    ->color('success'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registered At')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->label('Role')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload(),

                Tables\Filters\SelectFilter::make('login_type')
                    ->label('Login Source')
                    ->options([
                        'otp' => 'Flutter / OTP',
                        'website' => 'Website',
                        'email' => 'Email',
                        'google' => 'Google',
                        'firebase' => 'Firebase',
                        'whatsapp' => 'WhatsApp',
                        'facebook' => 'Facebook',
                        'apple' => 'Apple',
                        'guest' => 'Guest',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                Tables\Actions\Action::make('all_bookings')
                    ->label('All Bookings')
                    ->icon('heroicon-o-calendar-days')
                    ->color('success')
                    ->url(
                        fn (User $record): string =>
                            static::getUrl(
                                'bookings',
                                ['record' => $record]
                            )
                    ),

                Tables\Actions\Action::make('wallet_recharge')
                    ->label('Recharge Wallet')
                    ->icon('heroicon-o-wallet')
                    ->color('warning')
                    ->modalHeading(
                        fn (User $record): string =>
                            'Recharge Wallet - ' .
                            ($record->name ?: $record->mobile ?: ('User #' . $record->id))
                    )
                    ->modalDescription(
                        'Amount aur reason enter karke Super Admin ko OTP bhejein.'
                    )
                    ->form([
                        Forms\Components\Placeholder::make('current_wallet_balance')
                            ->label('Current Wallet Balance')
                            ->content(
                                fn (User $record): string =>
                                    '₹' . number_format(
                                        (float) (
                                            Wallet::query()
                                                ->where('user_id', $record->id)
                                                ->where('wallet_type', 'customer')
                                                ->value('balance') ?? 0
                                        ),
                                        2
                                    )
                            ),

                        Forms\Components\TextInput::make('amount')
                            ->label('Recharge Amount')
                            ->prefix('₹')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(500000)
                            ->required(),

                        Forms\Components\Textarea::make('reason')
                            ->label('Reason')
                            ->placeholder('Example: Refund adjustment / manual recharge')
                            ->rows(3)
                            ->minLength(3)
                            ->maxLength(500)
                            ->required(),
                    ])
                    ->action(function (User $record, array $data): void {
                        $admin = Auth::user();

                        if (! static::canRechargeWallet()) {
                            Notification::make()
                                ->title('Unauthorized')
                                ->body('Aap customer wallet recharge nahi kar sakte.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $mobile = static::superAdminMobile();

                        if (strlen($mobile) !== 10) {
                            Notification::make()
                                ->title('Super Admin mobile missing')
                                ->body('WALLET_SUPER_ADMIN_MOBILE configure karein.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $purpose = static::walletRechargePurpose((int) $admin->id);

                        $result = app(OtpService::class)->sendPurposeOtp(
                            $mobile,
                            $purpose,
                            [
                                'customer_id' => (int) $record->id,
                                'customer_name' => (string) ($record->name ?? 'Customer'),
                                'customer_mobile' => (string) ($record->mobile ?? ''),
                                'amount' => round((float) $data['amount'], 2),
                                'reason' => trim((string) $data['reason']),
                                'admin_id' => (int) $admin->id,
                                'admin_name' => (string) ($admin->name ?? 'Admin'),
                                'source' => 'filament_admin',
                            ]
                        );

                        if (! ($result['status'] ?? false)) {
                            Notification::make()
                                ->title('OTP send failed')
                                ->body(
                                    (string) (
                                        $result['message']
                                        ?? 'Unable to send wallet recharge OTP.'
                                    )
                                )
                                ->danger()
                                ->send();

                            return;
                        }

                        $channels = collect(
                            $result['delivered_on'] ?? []
                        )->implode(', ');

                        Notification::make()
                            ->title('OTP sent successfully')
                            ->body(
                                'Super Admin OTP sent to ' .
                                ($result['mobile'] ?? 'configured mobile') .
                                ($channels !== ''
                                    ? '. Delivered via: ' . $channels
                                    : '') .
                                '. Ab "Verify Recharge OTP" use karein.'
                            )
                            ->success()
                            ->persistent()
                            ->send();
                    })
                    ->visible(
                        fn (User $record): bool =>
                            static::canRechargeWallet()
                            && static::isWalletCustomer($record)
                    ),

                Tables\Actions\Action::make('wallet_recharge_verify')
                    ->label('Verify Recharge OTP')
                    ->icon('heroicon-o-shield-check')
                    ->color('info')
                    ->modalHeading('Verify Wallet Recharge OTP')
                    ->modalDescription(
                        'Super Admin ko mila 4-digit OTP enter karein.'
                    )
                    ->form([
                        Forms\Components\TextInput::make('otp')
                            ->label('4-digit OTP')
                            ->numeric()
                            ->length(4)
                            ->required()
                            ->autocomplete('one-time-code'),
                    ])
                    ->action(function (User $record, array $data): void {
                        $admin = Auth::user();

                        if (! static::canRechargeWallet()) {
                            Notification::make()
                                ->title('Unauthorized')
                                ->body('Aap customer wallet recharge verify nahi kar sakte.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $mobile = static::superAdminMobile();

                        if (strlen($mobile) !== 10) {
                            Notification::make()
                                ->title('Super Admin mobile missing')
                                ->body('WALLET_SUPER_ADMIN_MOBILE configure karein.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $purpose = static::walletRechargePurpose((int) $admin->id);

                        $otpResult = app(OtpService::class)->verifyPurposeOtp(
                            $mobile,
                            $purpose,
                            (string) $data['otp']
                        );

                        if (! ($otpResult['status'] ?? false)) {
                            Notification::make()
                                ->title('OTP verification failed')
                                ->body(
                                    (string) (
                                        $otpResult['message']
                                        ?? 'Invalid or expired OTP.'
                                    )
                                )
                                ->danger()
                                ->send();

                            return;
                        }

                        $payload = $otpResult['data']['payload'] ?? [];

                        if (
                            ! is_array($payload)
                            || (int) ($payload['admin_id'] ?? 0) !== (int) $admin->id
                            || (int) ($payload['customer_id'] ?? 0) !== (int) $record->id
                        ) {
                            Notification::make()
                                ->title('Recharge request mismatch')
                                ->body(
                                    'Ye OTP is customer/admin recharge request se match nahi karta.'
                                )
                                ->danger()
                                ->send();

                            return;
                        }

                        $amount = round((float) ($payload['amount'] ?? 0), 2);
                        $reason = trim((string) ($payload['reason'] ?? ''));

                        if ($amount <= 0 || $reason === '') {
                            Notification::make()
                                ->title('Invalid recharge request')
                                ->body('Recharge amount ya reason missing hai.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $result = app(WalletService::class)->adminRecharge(
                            customer: $record,
                            amount: $amount,
                            reason: $reason,
                            admin: $admin,
                            verificationId: (string) (
                                $otpResult['data']['verification_id'] ?? ''
                            )
                        );

                        if (! ($result['status'] ?? false)) {
                            Notification::make()
                                ->title('Wallet recharge failed')
                                ->body(
                                    (string) (
                                        $result['message']
                                        ?? 'Unable to recharge customer wallet.'
                                    )
                                )
                                ->danger()
                                ->persistent()
                                ->send();

                            return;
                        }

                        $wallet = $result['data']['wallet'] ?? [];
                        $newBalance = (float) (
                            $wallet['balance']
                            ?? $wallet['available_balance']
                            ?? 0
                        );

                        Notification::make()
                            ->title('Wallet recharged successfully')
                            ->body(
                                '₹' . number_format($amount, 2) .
                                ' credited to ' .
                                ($record->name ?: $record->mobile ?: ('User #' . $record->id)) .
                                '. New balance: ₹' .
                                number_format($newBalance, 2)
                            )
                            ->success()
                            ->persistent()
                            ->send();
                    })
                    ->visible(
                        fn (User $record): bool =>
                            static::canRechargeWallet()
                            && static::isWalletCustomer($record)
                    ),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }


    private static function canRechargeWallet(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        try {
            if (
                method_exists($user, 'can')
                && (
                    $user->can('admin-wallet-recharge')
                    || $user->can('manage-wallets')
                )
            ) {
                return true;
            }
        } catch (\Throwable) {
            // Continue with role/flag checks.
        }

        try {
            if (
                method_exists($user, 'hasRole')
                && $user->hasRole('Admin')
            ) {
                return true;
            }
        } catch (\Throwable) {
            // Continue with flag checks.
        }

        foreach (['is_super_admin', 'is_admin', 'super_admin'] as $flag) {
            if (
                isset($user->{$flag})
                && (bool) $user->{$flag}
            ) {
                return true;
            }
        }

        return false;
    }

    private static function isWalletCustomer(User $record): bool
    {
        try {
            if (
                method_exists($record, 'hasAnyRole')
                && $record->hasAnyRole([
                    'Admin',
                    'Transporter',
                    'Driver',
                ])
            ) {
                return false;
            }
        } catch (\Throwable) {
            // If role lookup fails, keep the customer action available.
        }

        return true;
    }

    private static function walletRechargePurpose(int $adminId): string
    {
        return 'admin_wallet_recharge_' . $adminId;
    }

    private static function superAdminMobile(): string
    {
        $mobile = (string) config(
            'services.wallet.super_admin_mobile',
            env(
                'WALLET_SUPER_ADMIN_MOBILE',
                '7088873331'
            )
        );

        $mobile = preg_replace('/\D+/', '', $mobile) ?? '';

        if (
            strlen($mobile) > 10
            && str_starts_with($mobile, '91')
        ) {
            $mobile = substr($mobile, -10);
        }

        return $mobile;
    }

    private static function loginSourceLabel(mixed $state): string
    {
        return match (strtolower((string) $state)) {
            'otp' => 'Flutter / OTP',
            'website' => 'Website',
            'email' => 'Email',
            'google' => 'Google',
            'firebase' => 'Firebase',
            'whatsapp' => 'WhatsApp',
            'facebook' => 'Facebook',
            'apple' => 'Apple',
            'guest' => 'Guest',
            default => 'Website / Old User',
        };
    }

    public static function getRelations(): array
    {
        return [
            OrdersRelationManager::class,
            AddressRelationManager::class,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'name',
            'mobile',
            'email',
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),

            'bookings' =>
                Pages\CustomerBookings::route(
                    '/{record}/all-bookings'
                ),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with('roles');

        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('Admin')) {
            return $query;
        }

        if ($user->hasRole('Transporter')) {
            return $query->where(function (Builder $builder) use ($user) {
                $builder
                    ->where('id', $user->id)
                    ->orWhere('created_by', $user->id);
            });
        }

        return $query->where('id', $user->id);
    }
}