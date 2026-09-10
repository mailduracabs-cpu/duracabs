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
                Forms\Components\Section::make('Payout Details')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('payout_no')
                            ->label('Payout No.')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Select::make('transporter_profile_id')
                            ->label('Vendor')
                            ->relationship(
                                name: 'transporter',
                                titleAttribute: 'id'
                            )
                            ->getOptionLabelFromRecordUsing(
                                fn (TransporterProfile $record): string =>
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

                        Forms\Components\TextInput::make('total_booking_units')
                            ->label('24H Units')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('gross_booking_amount')
                            ->label('Customer Booking Amount')
                            ->prefix('₹')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('payout_amount')
                            ->label('Vendor Payout')
                            ->prefix('₹')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('paid_amount')
                            ->label('Paid Amount')
                            ->prefix('₹')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('remaining_amount')
                            ->label('Remaining Amount')
                            ->prefix('₹')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'pending' => 'Pending',
                                'partial' => 'Partial',
                                'paid' => 'Paid',
                                'cancelled' => 'Cancelled',
                            ])
                            ->disabled(),

                        Forms\Components\TextInput::make('payment_method')
                            ->label('Payment Method')
                            ->disabled(),

                        Forms\Components\TextInput::make('payment_reference')
                            ->label('Payment Reference')
                            ->disabled(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Payout Items')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->disabled()
                            ->columns(4)
                            ->schema([
                                Forms\Components\TextInput::make('self_drive_booking_id')
                                    ->label('Booking ID'),

                                Forms\Components\TextInput::make('vehicle_id')
                                    ->label('Vehicle ID'),

                                Forms\Components\TextInput::make('booked_hours')
                                    ->label('Booked Hours'),

                                Forms\Components\TextInput::make('booking_units')
                                    ->label('24H Units'),

                                Forms\Components\TextInput::make('customer_daily_rate')
                                    ->label('24H Price')
                                    ->prefix('₹'),

                                Forms\Components\TextInput::make('commission_percentage')
                                    ->label('Commission')
                                    ->suffix('%'),

                                Forms\Components\TextInput::make('vendor_rate_per_24h')
                                    ->label('Vendor / 24H')
                                    ->prefix('₹'),

                                Forms\Components\TextInput::make('payout_amount')
                                    ->label('Payout')
                                    ->prefix('₹'),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('payout_no')
                    ->label('Payout No.')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('transporter.id')
                    ->label('Vendor')
                    ->formatStateUsing(
                        fn ($record): string =>
                            self::getTransporterName($record->transporter)
                    )
                    ->searchable()
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

                Tables\Columns\TextColumn::make('total_booking_units')
                    ->label('24H Units')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('gross_booking_amount')
                    ->label('Customer Amount')
                    ->money('INR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payout_amount')
                    ->label('Vendor Payout')
                    ->money('INR')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('Paid')
                    ->money('INR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('Balance')
                    ->money('INR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(
                        fn (string $state): string =>
                            ucfirst($state)
                    )
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        'cancelled' => 'danger',
                        'draft' => 'gray',
                        default => 'info',
                    }),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Paid At')
                    ->dateTime('d M Y h:i A')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'partial' => 'Partial',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                    ]),

                Tables\Filters\SelectFilter::make('transporter_profile_id')
                    ->label('Vendor')
                    ->options(
                        TransporterProfile::query()
                            ->orderBy('id')
                            ->get()
                            ->mapWithKeys(
                                fn (TransporterProfile $record): array => [
                                    $record->id =>
                                        self::getTransporterName($record),
                                ]
                            )
                            ->all()
                    )
                    ->searchable(),

                Tables\Filters\Filter::make('period')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From'),

                        Forms\Components\DatePicker::make('to')
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
                                    fn (Builder $query, $date): Builder =>
                                        $query->whereDate(
                                            'period_from',
                                            '>=',
                                            $date
                                        )
                                )
                                ->when(
                                    $data['to'] ?? null,
                                    fn (Builder $query, $date): Builder =>
                                        $query->whereDate(
                                            'period_to',
                                            '<=',
                                            $date
                                        )
                                );
                        }
                    ),
            ])

            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('receive_payment')
                    ->label('Pay Vendor')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(
                        fn (SelfDriveVendorPayout $record): bool =>
                            ! $record->isPaid()
                            && ! $record->isCancelled()
                            && (float) $record->remaining_amount > 0
                    )
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount')
                            ->prefix('₹')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->default(
                                fn (SelfDriveVendorPayout $record): float =>
                                    (float) $record->remaining_amount
                            ),

                        Forms\Components\Select::make('method')
                            ->label('Payment Method')
                            ->options([
                                'cash' => 'Cash',
                                'upi' => 'UPI',
                                'bank_transfer' => 'Bank Transfer',
                                'cheque' => 'Cheque',
                                'other' => 'Other',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('reference')
                            ->label('Reference / UTR / Transaction ID')
                            ->maxLength(255),

                        Forms\Components\Textarea::make('notes')
                            ->label('Payment Note'),
                    ])
                    ->action(
                        function (
                            SelfDriveVendorPayout $record,
                            array $data
                        ): void {
                            $amount = (float) $data['amount'];

                            if (
                                $amount >
                                (float) $record->remaining_amount
                            ) {
                                throw \Illuminate\Validation\ValidationException::withMessages([
                                    'amount' =>
                                        'Payment amount cannot be greater than remaining balance.',
                                ]);
                            }

                            $record->receivePayment(
                                amount: $amount,
                                method: $data['method'],
                                reference: $data['reference'] ?? null
                            );

                            if (! empty($data['notes'])) {
                                $record->notes = trim(
                                    ($record->notes ? $record->notes . PHP_EOL : '')
                                    . '[Payment] '
                                    . $data['notes']
                                );

                                $record->save();
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Vendor payment updated')
                                ->body(
                                    '₹'
                                    . number_format($amount, 2)
                                    . ' received against '
                                    . $record->payout_no
                                )
                                ->success()
                                ->send();
                        }
                    ),

                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(
                        fn (SelfDriveVendorPayout $record): bool =>
                            ! $record->isPaid()
                            && ! $record->isCancelled()
                            && (float) $record->paid_amount <= 0
                    )
                    ->action(
                        function (
                            SelfDriveVendorPayout $record
                        ): void {
                            $record->status =
                                SelfDriveVendorPayout::STATUS_CANCELLED;

                            $record->save();

                            \Filament\Notifications\Notification::make()
                                ->title('Payout cancelled')
                                ->success()
                                ->send();
                        }
                    ),
            ])

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' =>
                Pages\ListSelfDriveVendorPayouts::route('/'),

            'create' =>
                Pages\CreateSelfDriveVendorPayout::route('/create'),

            'view' =>
                Pages\ViewSelfDriveVendorPayout::route('/{record}'),

            'edit' =>
                Pages\EditSelfDriveVendorPayout::route('/{record}/edit'),
        ];
    }

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
                return trim((string) $candidate);
            }
        }

        if ($record->user) {
            if (filled($record->user->name ?? null)) {
                return trim((string) $record->user->name);
            }
        }

        return 'Vendor #' . $record->id;
    }
}