<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SelfDriveVendorPayoutResource\Pages;
use App\Models\FleetManagement\TransporterProfile;
use App\Models\SelfDriveVendorPayout;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class SelfDriveVendorPayoutResource extends Resource
{
    protected static ?string $model = SelfDriveVendorPayout::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Vendor Payouts';

    protected static ?string $modelLabel = 'Vendor Payout';

    protected static ?string $pluralModelLabel = 'Vendor Payouts';

    protected static ?string $navigationGroup = 'Self Drive';

    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                /*
                |--------------------------------------------------------------------------
                | Payout Details
                |--------------------------------------------------------------------------
                */

                Forms\Components\Section::make('Payout Details')
                    ->description(
                        'Vendor payout summary, payment status and settlement details.'
                    )
                    ->columns(4)
                    ->schema([

                        Forms\Components\TextInput::make('payout_no')
                            ->label('Payout No.')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Select::make(
                            'transporter_profile_id'
                        )
                            ->label('Vendor')
                            ->relationship(
                                name: 'transporter',
                                titleAttribute: 'id'
                            )
                            ->getOptionLabelFromRecordUsing(
                                fn (
                                    TransporterProfile $record
                                ): string =>
                                    self::getTransporterName($record)
                            )
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\DatePicker::make('period_from')
                            ->label('Period From')
                            ->required(),

                        Forms\Components\DatePicker::make('period_to')
                            ->label('Period To')
                            ->required()
                            ->afterOrEqual('period_from'),

                        Forms\Components\TextInput::make(
                            'total_booking_units'
                        )
                            ->label('Total 24H Units')
                            ->numeric()
                            ->suffix(' Units')
                            ->disabled(),

                        Forms\Components\TextInput::make(
                            'gross_booking_amount'
                        )
                            ->label('Customer Booking Amount')
                            ->prefix('₹')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make(
                            'payout_amount'
                        )
                            ->label('Vendor Payout')
                            ->prefix('₹')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make(
                            'remaining_amount'
                        )
                            ->label('Balance Payable')
                            ->prefix('₹')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('paid_amount')
                            ->label('Paid Amount')
                            ->prefix('₹')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\Select::make('status')
                            ->label('Payment Status')
                            ->options([
                                'draft' => 'Draft',
                                'pending' => 'Pending',
                                'partial' => 'Partial',
                                'paid' => 'Paid',
                                'cancelled' => 'Cancelled',
                            ])
                            ->disabled(),

                        Forms\Components\TextInput::make(
                            'payment_method'
                        )
                            ->label('Payment Method')
                            ->disabled(),

                        Forms\Components\TextInput::make(
                            'payment_reference'
                        )
                            ->label(
                                'Reference / UTR / Transaction ID'
                            )
                            ->disabled(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                /*
                |--------------------------------------------------------------------------
                | Payout Items
                |--------------------------------------------------------------------------
                */

                Forms\Components\Section::make(
                    'Booking & Vehicle Payout Details'
                )
                    ->description(
                        'Each payout unit represents one 24-hour booking period.'
                    )
                    ->schema([

                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->disabled()
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->columns(4)
                            ->schema([

                                /*
                                |--------------------------------------------------------------------------
                                | Booking
                                |--------------------------------------------------------------------------
                                */

                                Forms\Components\Placeholder::make(
                                    'booking_number_display'
                                )
                                    ->label('Booking No.')
                                    ->content(
                                        function ($record): string {
                                            if (! $record) {
                                                return '-';
                                            }

                                            return (string) (
                                                $record->booking
                                                    ?->booking_no
                                                ?? (
                                                    'Booking #'
                                                    . $record
                                                        ->self_drive_booking_id
                                                )
                                            );
                                        }
                                    ),

                                /*
                                |--------------------------------------------------------------------------
                                | Vehicle Name
                                |--------------------------------------------------------------------------
                                */

                                Forms\Components\Placeholder::make(
                                    'vehicle_display'
                                )
                                    ->label('Car / Vehicle')
                                    ->content(
                                        function ($record): string {
                                            if (! $record) {
                                                return '-';
                                            }

                                            $vehicle =
                                                $record->vehicle;

                                            if (! $vehicle) {
                                                return 'Vehicle #'
                                                    . (
                                                        $record
                                                            ->vehicle_id
                                                        ?? '-'
                                                    );
                                            }

                                            $company =
                                                $vehicle
                                                    ->car_company_name
                                                ?? $vehicle->brand
                                                ?? $vehicle
                                                    ->company_name
                                                ?? null;

                                            $model =
                                                $vehicle->model_name
                                                ?? $vehicle->model
                                                ?? $vehicle
                                                    ->vehicle_name
                                                ?? $vehicle->name
                                                ?? null;

                                            $name = collect([
                                                $company,
                                                $model,
                                            ])
                                                ->filter(
                                                    fn ($value) =>
                                                        filled($value)
                                                )
                                                ->map(
                                                    fn ($value) =>
                                                        trim(
                                                            (string) $value
                                                        )
                                                )
                                                ->unique()
                                                ->implode(' ');

                                            if ($name !== '') {
                                                return $name;
                                            }

                                            return 'Vehicle #'
                                                . $record->vehicle_id;
                                        }
                                    ),

                                /*
                                |--------------------------------------------------------------------------
                                | Registration
                                |--------------------------------------------------------------------------
                                */

                                Forms\Components\Placeholder::make(
                                    'registration_display'
                                )
                                    ->label('Registration No.')
                                    ->content(
                                        function ($record): string {
                                            if (! $record) {
                                                return '-';
                                            }

                                            return (string) (
                                                $record->vehicle
                                                    ?->registration_number
                                                ?? $record->vehicle
                                                    ?->vehicle_number
                                                ?? $record->vehicle
                                                    ?->registration_no
                                                ?? '-'
                                            );
                                        }
                                    ),

                                /*
                                |--------------------------------------------------------------------------
                                | Rental Period
                                |--------------------------------------------------------------------------
                                */

                                Forms\Components\Placeholder::make(
                                    'rental_period_display'
                                )
                                    ->label('Rental Period')
                                    ->content(
                                        function ($record): string {
                                            if (
                                                ! $record
                                                || ! $record
                                                    ->start_datetime
                                                || ! $record
                                                    ->end_datetime
                                            ) {
                                                return '-';
                                            }

                                            return $record
                                                ->start_datetime
                                                ->format(
                                                    'd M Y h:i A'
                                                )
                                                . ' → '
                                                . $record
                                                    ->end_datetime
                                                    ->format(
                                                        'd M Y h:i A'
                                                    );
                                        }
                                    ),

                                /*
                                |--------------------------------------------------------------------------
                                | Hours / Units
                                |--------------------------------------------------------------------------
                                */

                                Forms\Components\TextInput::make(
                                    'booked_hours'
                                )
                                    ->label('Booked Hours')
                                    ->suffix(' Hours')
                                    ->numeric()
                                    ->disabled(),

                                Forms\Components\TextInput::make(
                                    'booking_units'
                                )
                                    ->label('24H Units')
                                    ->suffix(' Unit(s)')
                                    ->numeric()
                                    ->disabled(),

                                /*
                                |--------------------------------------------------------------------------
                                | Customer Rate
                                |--------------------------------------------------------------------------
                                */

                                Forms\Components\TextInput::make(
                                    'customer_daily_rate'
                                )
                                    ->label('Customer / 24H')
                                    ->prefix('₹')
                                    ->numeric()
                                    ->disabled(),

                                /*
                                |--------------------------------------------------------------------------
                                | Commission
                                |--------------------------------------------------------------------------
                                */

                                Forms\Components\TextInput::make(
                                    'commission_percentage'
                                )
                                    ->label('Commission')
                                    ->suffix('%')
                                    ->numeric()
                                    ->disabled(),

                                /*
                                |--------------------------------------------------------------------------
                                | Vendor Rate
                                |--------------------------------------------------------------------------
                                */

                                Forms\Components\TextInput::make(
                                    'vendor_rate_per_24h'
                                )
                                    ->label('Vendor / 24H')
                                    ->prefix('₹')
                                    ->numeric()
                                    ->disabled(),

                                /*
                                |--------------------------------------------------------------------------
                                | Customer Booking Total
                                |--------------------------------------------------------------------------
                                */

                                Forms\Components\TextInput::make(
                                    'customer_booking_amount'
                                )
                                    ->label(
                                        'Customer Booking Amount'
                                    )
                                    ->prefix('₹')
                                    ->numeric()
                                    ->disabled(),

                                /*
                                |--------------------------------------------------------------------------
                                | Vendor Payout
                                |--------------------------------------------------------------------------
                                */

                                Forms\Components\TextInput::make(
                                    'payout_amount'
                                )
                                    ->label('Vendor Payout')
                                    ->prefix('₹')
                                    ->numeric()
                                    ->disabled(),

                            ])
                            ->columnSpanFull(),

                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')

            /*
            |--------------------------------------------------------------------------
            | Columns
            |--------------------------------------------------------------------------
            */

            ->columns([

                Tables\Columns\TextColumn::make('payout_no')
                    ->label('Payout No.')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make(
                    'transporter.id'
                )
                    ->label('Vendor')
                    ->formatStateUsing(
                        fn ($record): string =>
                            self::getTransporterName(
                                $record->transporter
                            )
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('period_from')
                    ->label('From')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('period_to')
                    ->label('To')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('items_count')
                    ->label('Bookings')
                    ->counts('items')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make(
                    'total_booking_units'
                )
                    ->label('24H Units')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make(
                    'gross_booking_amount'
                )
                    ->label('Customer Amount')
                    ->money('INR')
                    ->sortable(),

                Tables\Columns\TextColumn::make(
                    'payout_amount'
                )
                    ->label('Vendor Payout')
                    ->money('INR')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make(
                    'paid_amount'
                )
                    ->label('Paid')
                    ->money('INR')
                    ->sortable(),

                Tables\Columns\TextColumn::make(
                    'remaining_amount'
                )
                    ->label('Balance')
                    ->money('INR')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(
                        fn (string $state): string =>
                            ucfirst($state)
                    )
                    ->color(
                        fn (string $state): string =>
                            match ($state) {
                                'paid' => 'success',
                                'partial' => 'warning',
                                'cancelled' => 'danger',
                                'draft' => 'gray',
                                default => 'info',
                            }
                    ),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Paid At')
                    ->dateTime('d M Y h:i A')
                    ->placeholder('-')
                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),

                Tables\Columns\TextColumn::make(
                    'created_at'
                )
                    ->label('Created')
                    ->dateTime('d M Y h:i A')
                    ->sortable()
                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),
            ])

            /*
            |--------------------------------------------------------------------------
            | Filters
            |--------------------------------------------------------------------------
            */

            ->filters([

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'partial' => 'Partial',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                    ]),

                Tables\Filters\SelectFilter::make(
                    'transporter_profile_id'
                )
                    ->label('Vendor')
                    ->options(
                        TransporterProfile::query()
                            ->orderBy('id')
                            ->get()
                            ->mapWithKeys(
                                fn (
                                    TransporterProfile $record
                                ): array => [
                                    $record->id =>
                                        self::getTransporterName(
                                            $record
                                        ),
                                ]
                            )
                            ->all()
                    )
                    ->searchable(),

                Tables\Filters\Filter::make('period')
                    ->form([

                        Forms\Components\DatePicker::make(
                            'from'
                        )
                            ->label('From'),

                        Forms\Components\DatePicker::make(
                            'to'
                        )
                            ->label('To'),

                    ])
                    ->query(
                        function (
                            Builder $query,
                            array $data
                        ): Builder {
                            return $query
                                ->when(
                                    $data['from'] ?? null,
                                    fn (
                                        Builder $query,
                                        $date
                                    ): Builder =>
                                        $query->whereDate(
                                            'period_from',
                                            '>=',
                                            $date
                                        )
                                )
                                ->when(
                                    $data['to'] ?? null,
                                    fn (
                                        Builder $query,
                                        $date
                                    ): Builder =>
                                        $query->whereDate(
                                            'period_to',
                                            '<=',
                                            $date
                                        )
                                );
                        }
                    ),
            ])

            /*
            |--------------------------------------------------------------------------
            | Actions
            |--------------------------------------------------------------------------
            */

            ->actions([

                /*
                | View Payout
                */

                Tables\Actions\ViewAction::make()
                    ->label('View')
                    ->icon('heroicon-o-eye'),

                /*
                | View PDF
                */

                Tables\Actions\Action::make('view_pdf')
                    ->label('View PDF')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->url(
                        fn (
                            SelfDriveVendorPayout $record
                        ): string =>
                            route(
                                'admin.self-drive-vendor-payouts.pdf.view',
                                [
                                    'payout' =>
                                        $record->getKey(),
                                ]
                            )
                    )
                    ->openUrlInNewTab(),

                /*
                | Download PDF
                */

                Tables\Actions\Action::make(
                    'download_pdf'
                )
                    ->label('Download PDF')
                    ->icon(
                        'heroicon-o-arrow-down-tray'
                    )
                    ->color('info')
                    ->url(
                        fn (
                            SelfDriveVendorPayout $record
                        ): string =>
                            route(
                                'admin.self-drive-vendor-payouts.pdf',
                                [
                                    'payout' =>
                                        $record->getKey(),
                                ]
                            )
                    )
                    ->openUrlInNewTab(),

                /*
                | Edit
                */

                Tables\Actions\EditAction::make()
                    ->label('Edit'),

                /*
                |--------------------------------------------------------------------------
                | Receive Vendor Payment
                |--------------------------------------------------------------------------
                */

                Tables\Actions\Action::make(
                    'receive_payment'
                )
                    ->label('Pay Vendor')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(
                        fn (
                            SelfDriveVendorPayout $record
                        ): bool =>
                            ! $record->isPaid()
                            && ! $record->isCancelled()
                            && (
                                float
                            ) $record->remaining_amount > 0
                    )
                    ->form([

                        Forms\Components\TextInput::make(
                            'amount'
                        )
                            ->label('Payment Amount')
                            ->prefix('₹')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->default(
                                fn (
                                    SelfDriveVendorPayout $record
                                ): float =>
                                    (
                                        float
                                    ) $record->remaining_amount
                            ),

                        Forms\Components\Select::make(
                            'method'
                        )
                            ->label('Payment Method')
                            ->options([
                                'cash' => 'Cash',
                                'upi' => 'UPI',
                                'bank_transfer' =>
                                    'Bank Transfer',
                                'cheque' => 'Cheque',
                                'other' => 'Other',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make(
                            'reference'
                        )
                            ->label(
                                'Reference / UTR / Transaction ID'
                            )
                            ->maxLength(255),

                        Forms\Components\Textarea::make(
                            'notes'
                        )
                            ->label('Payment Note')
                            ->rows(3),

                    ])
                    ->action(
                        function (
                            SelfDriveVendorPayout $record,
                            array $data
                        ): void {

                            $amount =
                                (float) $data['amount'];

                            if (
                                $amount >
                                (
                                    float
                                ) $record->remaining_amount
                            ) {
                                throw ValidationException
                                    ::withMessages([
                                        'amount' =>
                                            'Payment amount cannot be greater than remaining balance.',
                                    ]);
                            }

                            $record->receivePayment(
                                amount: $amount,
                                method:
                                    $data['method'],
                                reference:
                                    $data['reference']
                                    ?? null
                            );

                            if (
                                ! empty(
                                    $data['notes']
                                )
                            ) {
                                $record->notes = trim(
                                    (
                                        $record->notes
                                        ? $record->notes
                                            . PHP_EOL
                                        : ''
                                    )
                                    . '[Payment] '
                                    . $data['notes']
                                );

                                $record->save();
                            }

                            \Filament\Notifications\Notification
                                ::make()
                                ->title(
                                    'Vendor payment updated'
                                )
                                ->body(
                                    '₹'
                                    . number_format(
                                        $amount,
                                        2
                                    )
                                    . ' received against '
                                    . $record->payout_no
                                )
                                ->success()
                                ->send();
                        }
                    ),

                /*
                |--------------------------------------------------------------------------
                | Cancel Payout
                |--------------------------------------------------------------------------
                */

                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(
                        'Cancel Vendor Payout'
                    )
                    ->modalDescription(
                        'Are you sure you want to cancel this vendor payout?'
                    )
                    ->visible(
                        fn (
                            SelfDriveVendorPayout $record
                        ): bool =>
                            ! $record->isPaid()
                            && ! $record->isCancelled()
                            && (
                                float
                            ) $record->paid_amount <= 0
                    )
                    ->action(
                        function (
                            SelfDriveVendorPayout $record
                        ): void {

                            $record->status =
                                SelfDriveVendorPayout
                                    ::STATUS_CANCELLED;

                            $record->save();

                            \Filament\Notifications\Notification
                                ::make()
                                ->title(
                                    'Payout cancelled'
                                )
                                ->success()
                                ->send();
                        }
                    ),
            ])

            /*
            |--------------------------------------------------------------------------
            | Bulk Actions
            |--------------------------------------------------------------------------
            */

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public static function getRelations(): array
    {
        return [];
    }

    /*
    |--------------------------------------------------------------------------
    | Pages
    |--------------------------------------------------------------------------
    */

    public static function getPages(): array
    {
        return [

            'index' =>
                Pages\ListSelfDriveVendorPayouts
                    ::route('/'),

            'create' =>
                Pages\CreateSelfDriveVendorPayout
                    ::route('/create'),

            'view' =>
                Pages\ViewSelfDriveVendorPayout
                    ::route('/{record}'),

            'edit' =>
                Pages\EditSelfDriveVendorPayout
                    ::route('/{record}/edit'),

        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Vendor Display Name
    |--------------------------------------------------------------------------
    */

    private static function getTransporterName(
        ?TransporterProfile $record
    ): string {
        if (! $record) {
            return 'Unknown Vendor';
        }

        $candidates = [

            $record->business_name ?? null,

            $record->company_name ?? null,

            $record->name ?? null,

            $record->vendor_name ?? null,

        ];

        foreach ($candidates as $candidate) {

            if (filled($candidate)) {
                return trim(
                    (string) $candidate
                );
            }
        }

        if ($record->user) {

            if (
                filled(
                    $record->user->name
                    ?? null
                )
            ) {
                return trim(
                    (string) $record->user->name
                );
            }
        }

        return 'Vendor #' . $record->id;
    }
}