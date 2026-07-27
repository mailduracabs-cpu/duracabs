<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers\AddressRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\OrdersRelationManager;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
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