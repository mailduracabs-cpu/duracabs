<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransporterProfileResource\Pages;
use App\Models\FleetManagement\TransporterProfile;
use App\Models\User;
use App\Services\MapsService;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Throwable;

class TransporterProfileResource extends Resource
{
    protected static ?string $model = TransporterProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?int $navigationSort = 2;

    /*
    |--------------------------------------------------------------------------
    | Panel Configuration
    |--------------------------------------------------------------------------
    */

    public static function isTransporterPanel(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'transporter';
    }

    public static function getNavigationLabel(): string
    {
        return static::isTransporterPanel()
            ? 'My Profile'
            : 'Partners';
    }

    public static function getNavigationGroup(): ?string
    {
        return static::isTransporterPanel()
            ? null
            : 'Fleet Management';
    }

    public static function getModelLabel(): string
    {
        return static::isTransporterPanel()
            ? 'My Profile'
            : 'Partner';
    }

    public static function getPluralModelLabel(): string
    {
        return static::isTransporterPanel()
            ? 'My Profile'
            : 'Partners';
    }

    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    */

    public static function canCreate(): bool
    {
        return ! static::isTransporterPanel();
    }

    public static function canDelete(Model $record): bool
    {
        return ! static::isTransporterPanel();
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Resource Query
    |--------------------------------------------------------------------------
    */

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with('user')
            ->withCount([
                'vehicles',
                'taxiBookings',
                'selfDriveBookings',
            ]);

        if (! static::isTransporterPanel()) {
            return $query;
        }

        $authenticatedUser = auth()->user();

        if (! $authenticatedUser) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(
            function (Builder $profileQuery) use (
                $authenticatedUser
            ): void {
                $profileQuery->where(
                    'user_id',
                    $authenticatedUser->id
                );

                if (filled($authenticatedUser->mobile)) {
                    $profileQuery->orWhere(
                        'mobile',
                        $authenticatedUser->mobile
                    );
                }
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Partner Form
    |--------------------------------------------------------------------------
    */

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Account Assignment')
                ->description(
                    static::isTransporterPanel()
                        ? 'This profile is linked to your partner account.'
                        : 'Assign a transporter account and define the partner type.'
                )
                ->schema([
                    Select::make('user_id')
                        ->label('Partner Account')
                        ->options(
                            fn (): array =>
                                static::partnerAccountOptions()
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->disabled(
                            fn (): bool =>
                                static::isTransporterPanel()
                        )
                        ->dehydrated()
                        ->hidden(
                            fn (): bool =>
                                static::isTransporterPanel()
                        ),

                    Select::make('partner_type')
                        ->label('Partner Type')
                        ->options([
                            TransporterProfile::TYPE_HOST =>
                                'Host - Self Drive',

                            TransporterProfile::TYPE_VENDOR =>
                                'Vendor - With Driver',

                            TransporterProfile::TYPE_BOTH =>
                                'Host and Vendor',
                        ])
                        ->default(TransporterProfile::TYPE_HOST)
                        ->required()
                        ->native(false)
                        ->disabled(
                            fn (): bool =>
                                static::isTransporterPanel()
                        )
                        ->dehydrated()
                        ->helperText(
                            static::isTransporterPanel()
                                ? 'Only an administrator can change the partner type.'
                                : 'Select the services this partner is permitted to provide.'
                        ),
                ])
                ->columns(2),

            Section::make('Company Information')
                ->schema([
                    TextInput::make('company_name')
                        ->label('Company Name')
                        ->required()
                        ->maxLength(180),

                    TextInput::make('contact_person')
                        ->label('Contact Person')
                        ->required()
                        ->maxLength(180),

                    TextInput::make('mobile')
                        ->label('Mobile Number')
                        ->tel()
                        ->required()
                        ->maxLength(20)
                        ->unique(
                            table: 'fleet_transporter_profiles',
                            column: 'mobile',
                            ignoreRecord: true
                        )
                        ->readOnly(
                            fn (): bool =>
                                static::isTransporterPanel()
                        )
                        ->helperText(
                            static::isTransporterPanel()
                                ? 'Contact an administrator to change the registered mobile number.'
                                : 'A mobile number can be assigned to only one partner profile.'
                        ),

                    TextInput::make('whatsapp_number')
                        ->label('WhatsApp Number')
                        ->tel()
                        ->maxLength(20),

                    TextInput::make('email')
                        ->label('Email Address')
                        ->email()
                        ->maxLength(180),
                ])
                ->columns(2),

            Section::make('Tax and Identity Information')
                ->schema([
                    TextInput::make('aadhaar_number')
                        ->label('Aadhaar Number')
                        ->maxLength(20),

                    TextInput::make('pan_number')
                        ->label('PAN Number')
                        ->maxLength(20),

                    TextInput::make('gst_number')
                        ->label('GST Number')
                        ->maxLength(30),
                ])
                ->columns(3)
                ->collapsed(),

            Section::make('Office and Pickup Location')
                ->description(
                    'The pickup coordinates and location details are generated from the office address.'
                )
                ->schema([
                    Textarea::make('office_address')
                        ->label('Office or Pickup Address')
                        ->rows(3)
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(
                            fn ($state, Set $set) =>
                                static::resolveOfficeAddress(
                                    $state,
                                    $set
                                )
                        )
                        ->columnSpanFull(),

                    TextInput::make('city')
                        ->label('City')
                        ->required()
                        ->maxLength(120),

                    TextInput::make('state')
                        ->label('State')
                        ->required()
                        ->maxLength(120),

                    TextInput::make('pincode')
                        ->label('Postal Code')
                        ->maxLength(20),

                    TextInput::make('service_radius_km')
                        ->label('Service Radius')
                        ->numeric()
                        ->default(40)
                        ->required()
                        ->minValue(1)
                        ->suffix(' km')
                        ->helperText(
                            'Vehicles are shown when the customer pickup location is within this radius.'
                        ),

                    Hidden::make('pickup_place_id'),

                    Hidden::make('pickup_latitude'),

                    Hidden::make('pickup_longitude'),
                ])
                ->columns(4),

            Section::make('Documents')
                ->schema([
                    FileUpload::make('aadhaar_image')
                        ->label('Aadhaar Document')
                        ->image()
                        ->imagePreviewHeight('180')
                        ->directory('fleet/transporters/aadhaar')
                        ->visibility('public'),

                    FileUpload::make('pan_image')
                        ->label('PAN Document')
                        ->image()
                        ->imagePreviewHeight('180')
                        ->directory('fleet/transporters/pan')
                        ->visibility('public'),

                    FileUpload::make('gst_image')
                        ->label('GST Document')
                        ->image()
                        ->imagePreviewHeight('180')
                        ->directory('fleet/transporters/gst')
                        ->visibility('public'),

                    FileUpload::make('company_document')
                        ->label('Company Document')
                        ->directory('fleet/transporters/company')
                        ->visibility('public'),

                    FileUpload::make('office_photo')
                        ->label('Office Photo')
                        ->image()
                        ->imagePreviewHeight('180')
                        ->directory('fleet/transporters/office')
                        ->visibility('public'),
                ])
                ->columns(2)
                ->collapsed(),

            Section::make('Verification and Status')
                ->schema([
                    Select::make('verification_status')
                        ->label('Verification Status')
                        ->options([
                            TransporterProfile::VERIFICATION_PENDING =>
                                'Pending',

                            TransporterProfile::VERIFICATION_VERIFIED =>
                                'Verified',

                            TransporterProfile::VERIFICATION_REJECTED =>
                                'Rejected',
                        ])
                        ->default(
                            TransporterProfile::VERIFICATION_PENDING
                        )
                        ->required()
                        ->native(false)
                        ->disabled(
                            fn (): bool =>
                                static::isTransporterPanel()
                        )
                        ->dehydrated(),

                    Select::make('status')
                        ->label('Account Status')
                        ->options([
                            1 => 'Active',
                            0 => 'Inactive',
                        ])
                        ->default(1)
                        ->required()
                        ->native(false)
                        ->disabled(
                            fn (): bool =>
                                static::isTransporterPanel()
                        )
                        ->dehydrated(),
                ])
                ->columns(2),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Partner Table
    |--------------------------------------------------------------------------
    */

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('company_name')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(
                        fn (TransporterProfile $record): string =>
                            $record->contact_person
                                ?: 'No contact person'
                    ),

                TextColumn::make('user.name')
                    ->label('Account')
                    ->searchable()
                    ->placeholder('Not linked')
                    ->description(
                        fn (TransporterProfile $record): string =>
                            $record->user?->email
                                ?: 'No account email'
                    )
                    ->hidden(
                        fn (): bool =>
                            static::isTransporterPanel()
                    ),

                SelectColumn::make('partner_type')
                    ->label('Partner Type')
                    ->options([
                        TransporterProfile::TYPE_HOST =>
                            'Host',

                        TransporterProfile::TYPE_VENDOR =>
                            'Vendor',

                        TransporterProfile::TYPE_BOTH =>
                            'Both',
                    ])
                    ->disabled(
                        fn (): bool =>
                            static::isTransporterPanel()
                    )
                    ->hidden(
                        fn (): bool =>
                            static::isTransporterPanel()
                    ),

                TextColumn::make('partner_type_label')
                    ->label('Partner Type')
                    ->badge()
                    ->getStateUsing(
                        fn (TransporterProfile $record): string =>
                            $record->partnerTypeLabel()
                    )
                    ->color(
                        fn (string $state): string =>
                            match ($state) {
                                'Host' => 'info',
                                'Vendor' => 'success',
                                'Host and Vendor' => 'primary',
                                default => 'gray',
                            }
                    )
                    ->visible(
                        fn (): bool =>
                            static::isTransporterPanel()
                    ),

                TextColumn::make('mobile')
                    ->label('Mobile')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->placeholder('Not available')
                    ->toggleable(),

                TextColumn::make('city')
                    ->label('City')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('vehicles_count')
                    ->label('Vehicles')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('taxi_bookings_count')
                    ->label('Taxi')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('self_drive_bookings_count')
                    ->label('Self Drive')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('total_bookings')
                    ->label('Total Bookings')
                    ->getStateUsing(
                        fn (TransporterProfile $record): int =>
                            $record->totalBookingCount()
                    )
                    ->badge()
                    ->color('primary'),

                SelectColumn::make('verification_status')
                    ->label('Verification')
                    ->options([
                        TransporterProfile::VERIFICATION_PENDING =>
                            'Pending',

                        TransporterProfile::VERIFICATION_VERIFIED =>
                            'Verified',

                        TransporterProfile::VERIFICATION_REJECTED =>
                            'Rejected',
                    ])
                    ->disabled(
                        fn (): bool =>
                            static::isTransporterPanel()
                    ),

                ToggleColumn::make('status')
                    ->label('Active')
                    ->disabled(
                        fn (): bool =>
                            static::isTransporterPanel()
                    ),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('partner_type')
                    ->label('Partner Type')
                    ->options([
                        TransporterProfile::TYPE_HOST =>
                            'Host',

                        TransporterProfile::TYPE_VENDOR =>
                            'Vendor',

                        TransporterProfile::TYPE_BOTH =>
                            'Host and Vendor',
                    ]),

                SelectFilter::make('verification_status')
                    ->label('Verification')
                    ->options([
                        TransporterProfile::VERIFICATION_PENDING =>
                            'Pending',

                        TransporterProfile::VERIFICATION_VERIFIED =>
                            'Verified',

                        TransporterProfile::VERIFICATION_REJECTED =>
                            'Rejected',
                    ]),

                SelectFilter::make('status')
                    ->label('Account Status')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ]),

                SelectFilter::make('city')
                    ->label('City')
                    ->options(
                        fn (): array =>
                            TransporterProfile::query()
                                ->whereNotNull('city')
                                ->where('city', '!=', '')
                                ->orderBy('city')
                                ->pluck('city', 'city')
                                ->all()
                    )
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(
                            fn (
                                TransporterProfile $record
                            ): bool =>
                                ! static::isTransporterPanel()
                                && ! $record->status
                        )
                        ->requiresConfirmation()
                        ->action(
                            fn (
                                TransporterProfile $record
                            ) => static::setPartnerStatus(
                                $record,
                                true
                            )
                        ),

                    Tables\Actions\Action::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-no-symbol')
                        ->color('warning')
                        ->visible(
                            fn (
                                TransporterProfile $record
                            ): bool =>
                                ! static::isTransporterPanel()
                                && $record->status
                        )
                        ->requiresConfirmation()
                        ->action(
                            fn (
                                TransporterProfile $record
                            ) => static::setPartnerStatus(
                                $record,
                                false
                            )
                        ),

                    Tables\Actions\Action::make('delete_partner')
                        ->label('Delete')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->visible(
                            fn (): bool =>
                                ! static::isTransporterPanel()
                        )
                        ->requiresConfirmation()
                        ->modalHeading('Delete partner')
                        ->modalDescription(
                            'The partner can be deleted only when no vehicles, bookings, or fleet records are linked.'
                        )
                        ->action(
                            fn (
                                TransporterProfile $record
                            ) => static::deletePartner($record)
                        ),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make(
                        'activate_selected'
                    )
                        ->label('Activate selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(
                            function (
                                Collection $records
                            ): void {
                                $records->each->update([
                                    'status' => true,
                                ]);

                                Notification::make()
                                    ->title(
                                        'Selected partners activated'
                                    )
                                    ->success()
                                    ->send();
                            }
                        ),

                    Tables\Actions\BulkAction::make(
                        'deactivate_selected'
                    )
                        ->label('Deactivate selected')
                        ->icon('heroicon-o-no-symbol')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(
                            function (
                                Collection $records
                            ): void {
                                $records->each->update([
                                    'status' => false,
                                ]);

                                Notification::make()
                                    ->title(
                                        'Selected partners deactivated'
                                    )
                                    ->success()
                                    ->send();
                            }
                        ),
                ])
                    ->visible(
                        fn (): bool =>
                            ! static::isTransporterPanel()
                    ),
            ])
            ->emptyStateHeading('No partners found')
            ->emptyStateDescription(
                'Create a partner profile to manage hosts, vendors, vehicles, and bookings.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Form and Table Helpers
    |--------------------------------------------------------------------------
    */

    private static function partnerAccountOptions(): array
    {
        return User::query()
            ->role('Transporter')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(
                function (User $user): array {
                    $name = $user->name ?: 'Unnamed Account';
                    $mobile = $user->mobile ?: 'No Mobile';
                    $email = $user->email ?: 'No Email';

                    return [
                        $user->id =>
                            "{$name} | {$mobile} | {$email}",
                    ];
                }
            )
            ->all();
    }

    private static function resolveOfficeAddress(
        mixed $address,
        Set $set
    ): void {
        if (blank($address)) {
            return;
        }

        try {
            $result = MapsService::geocode(
                (string) $address
            );

            if (! ($result['status'] ?? false)) {
                return;
            }

            $location =
                $result['data']['results'][0] ?? null;

            if (! $location) {
                return;
            }

            $set(
                'office_address',
                $location['formatted_address']
                    ?? $address
            );

            $set(
                'pickup_latitude',
                data_get(
                    $location,
                    'geometry.location.lat'
                )
            );

            $set(
                'pickup_longitude',
                data_get(
                    $location,
                    'geometry.location.lng'
                )
            );

            $set(
                'pickup_place_id',
                $location['place_id'] ?? null
            );

            foreach (
                $location['address_components'] ?? []
                as $component
            ) {
                $types = $component['types'] ?? [];

                if (in_array('locality', $types, true)) {
                    $set(
                        'city',
                        $component['long_name'] ?? null
                    );
                }

                if (
                    in_array(
                        'administrative_area_level_1',
                        $types,
                        true
                    )
                ) {
                    $set(
                        'state',
                        $component['long_name'] ?? null
                    );
                }

                if (
                    in_array(
                        'postal_code',
                        $types,
                        true
                    )
                ) {
                    $set(
                        'pincode',
                        $component['long_name'] ?? null
                    );
                }
            }
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Unable to resolve office address')
                ->body(
                    'The address was retained, but its location details could not be generated.'
                )
                ->warning()
                ->send();
        }
    }

    private static function setPartnerStatus(
        TransporterProfile $record,
        bool $status
    ): void {
        $record->update([
            'status' => $status,
        ]);

        Notification::make()
            ->title(
                $status
                    ? 'Partner activated'
                    : 'Partner deactivated'
            )
            ->success()
            ->send();
    }

    private static function deletePartner(
        TransporterProfile $record
    ): void {
        if (! $record->canBeDeletedSafely()) {
            Notification::make()
                ->title('Partner cannot be deleted')
                ->body(
                    $record->deletionBlockReason()
                    ?? 'The partner has linked operational records.'
                )
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        try {
            $record->delete();

            Notification::make()
                ->title('Partner deleted')
                ->body(
                    'The partner profile was deleted successfully.'
                )
                ->success()
                ->send();
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Unable to delete partner')
                ->body(
                    'The partner profile could not be deleted.'
                )
                ->danger()
                ->persistent()
                ->send();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Resource Pages
    |--------------------------------------------------------------------------
    */

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' =>
                Pages\ListTransporterProfiles::route('/'),

            'create' =>
                Pages\CreateTransporterProfile::route(
                    '/create'
                ),

            'edit' =>
                Pages\EditTransporterProfile::route(
                    '/{record}/edit'
                ),
        ];
    }
}