<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerLeadResource\Pages;
use App\Models\CustomerSearchActivity;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;

class CustomerLeadResource extends Resource
{
    protected static ?string $model = CustomerSearchActivity::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $activeNavigationIcon = 'heroicon-s-user-group';

    protected static ?string $navigationLabel = 'Customer Leads';

    protected static ?string $modelLabel = 'Customer Lead';

    protected static ?string $pluralModelLabel = 'Customer Leads';
    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'customer_name';

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::query()
            ->openLeads()
            ->notConverted()
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::query()
            ->whereIn('priority', [
                CustomerSearchActivity::PRIORITY_HIGH,
                CustomerSearchActivity::PRIORITY_URGENT,
            ])
            ->openLeads()
            ->notConverted()
            ->exists()
                ? 'danger'
                : 'primary';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Lead Management')
                    ->description('Update lead status, assignment and follow-up details.')
                    ->icon('heroicon-o-briefcase')
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                        ])->schema([
                            Select::make('lead_status')
                                ->label('Lead Status')
                                ->options(self::leadStatusOptions())
                                ->native(false)
                                ->required(),

                            Select::make('priority')
                                ->label('Priority')
                                ->options(self::priorityOptions())
                                ->native(false)
                                ->required(),

                            Select::make('assigned_to')
                                ->label('Assigned Executive')
                                ->options(
                                    fn (): array => User::query()
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all()
                                )
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->nullable(),

                            DateTimePicker::make('follow_up_at')
                                ->label('Next Follow-up')
                                ->seconds(false)
                                ->native(false)
                                ->nullable(),
                        ]),

                        Textarea::make('lead_notes')
                            ->label('Internal Lead Notes')
                            ->rows(5)
                            ->columnSpanFull(),
                    ]),

                Section::make('Customer & Journey')
                    ->icon('heroicon-o-map')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        Placeholder::make('customer_summary')
                            ->label('Customer')
                            ->content(
                                fn (?CustomerSearchActivity $record): string =>
                                    $record?->customer_display_name ?? 'New lead'
                            ),

                        Placeholder::make('mobile_summary')
                            ->label('Mobile')
                            ->content(
                                fn (?CustomerSearchActivity $record): string =>
                                    $record?->mobile ?: 'Not available'
                            ),

                        Placeholder::make('service_summary')
                            ->label('Service')
                            ->content(
                                fn (?CustomerSearchActivity $record): string =>
                                    $record?->service_label ?? 'Not available'
                            ),

                        Placeholder::make('route_summary')
                            ->label('Route')
                            ->content(
                                fn (?CustomerSearchActivity $record): string =>
                                    $record?->route_summary ?? 'Not available'
                            ),

                        Placeholder::make('stage_summary')
                            ->label('Journey Stage')
                            ->content(
                                fn (?CustomerSearchActivity $record): string =>
                                    $record?->stage_label ?? 'Not available'
                            ),

                        Placeholder::make('amount_summary')
                            ->label('Estimated Amount')
                            ->content(
                                fn (?CustomerSearchActivity $record): string =>
                                    $record?->formatted_amount ?? 'Not available'
                            ),
                    ]),

                Section::make('Booking Information')
                    ->icon('heroicon-o-check-badge')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->collapsed()
                    ->schema([
                        Placeholder::make('booking_number_summary')
                            ->label('Booking Number')
                            ->content(
                                fn (?CustomerSearchActivity $record): string =>
                                    $record?->booking_number ?: 'Not converted'
                            ),

                        Placeholder::make('payment_status_summary')
                            ->label('Payment Status')
                            ->content(
                                fn (?CustomerSearchActivity $record): HtmlString =>
                                    new HtmlString(
                                        e(
                                            ucfirst(
                                                str_replace(
                                                    '_',
                                                    ' ',
                                                    (string) (
                                                        $record?->payment_status
                                                        ?? 'not started'
                                                    )
                                                )
                                            )
                                        )
                                    )
                            ),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn (Builder $query): Builder => $query
                    ->with(['user', 'assignedUser'])
                    ->latestFirst()
            )
            ->columns([
                TextColumn::make('customer_display_name')
                    ->label('Customer')
                    ->searchable([
                        'customer_name',
                        'mobile',
                        'customer_email',
                    ])
                    ->description(
                        fn (CustomerSearchActivity $record): string =>
                            $record->customer_email ?: ($record->is_guest ? 'Guest' : 'Registered')
                    )
                    ->weight('bold')
                    ->wrap(),

                TextColumn::make('mobile')
                    ->label('Mobile')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Mobile copied')
                    ->placeholder('Not available'),

                TextColumn::make('service_type')
                    ->label('Service')
                    ->formatStateUsing(
                        fn (CustomerSearchActivity $record): string =>
                            $record->service_label
                    )
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        CustomerSearchActivity::SERVICE_SELF_DRIVE => 'info',
                        CustomerSearchActivity::SERVICE_BIKE_RENTAL => 'warning',
                        CustomerSearchActivity::SERVICE_ONE_WAY,
                        CustomerSearchActivity::SERVICE_ROUND_TRIP,
                        CustomerSearchActivity::SERVICE_LOCAL,
                        CustomerSearchActivity::SERVICE_AIRPORT,
                        CustomerSearchActivity::SERVICE_TOUR => 'primary',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('route_summary')
                    ->label('Route')
                    ->searchable([
                        'pickup_location',
                        'pickup_city',
                        'drop_location',
                        'drop_city',
                    ])
                    ->limit(45)
                    ->tooltip(
                        fn (CustomerSearchActivity $record): string =>
                            $record->route_summary
                    )
                    ->wrap(),

                TextColumn::make('start_datetime')
                    ->label('Trip Date')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->placeholder('Not selected')
                    ->toggleable(),

                TextColumn::make('vehicle_name')
                    ->label('Vehicle')
                    ->searchable()
                    ->placeholder('Not selected')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('grand_total')
                    ->label('Estimated Fare')
                    ->formatStateUsing(
                        fn (mixed $state, CustomerSearchActivity $record): string =>
                            $record->formatted_amount
                    )
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('intent_score')
                    ->label('Intent')
                    ->badge()
                    ->suffix('/100')
                    ->color(fn (int $state): string => match (true) {
                        $state >= 80 => 'danger',
                        $state >= 60 => 'warning',
                        $state >= 30 => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('priority')
                    ->label('Priority')
                    ->badge()
                    ->formatStateUsing(
                        fn (string $state): string => ucfirst($state)
                    )
                    ->color(fn (string $state): string => match ($state) {
                        CustomerSearchActivity::PRIORITY_URGENT => 'danger',
                        CustomerSearchActivity::PRIORITY_HIGH => 'warning',
                        CustomerSearchActivity::PRIORITY_MEDIUM => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('lead_status')
                    ->label('Lead Status')
                    ->badge()
                    ->formatStateUsing(
                        fn (string $state): string =>
                            str($state)->replace('_', ' ')->title()->toString()
                    )
                    ->color(fn (string $state): string => match ($state) {
                        CustomerSearchActivity::LEAD_CONVERTED => 'success',
                        CustomerSearchActivity::LEAD_LOST,
                        CustomerSearchActivity::LEAD_NOT_INTERESTED => 'danger',
                        CustomerSearchActivity::LEAD_FOLLOW_UP => 'warning',
                        CustomerSearchActivity::LEAD_CONTACTED => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('assignedUser.name')
                    ->label('Assigned To')
                    ->placeholder('Unassigned')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('follow_up_at')
                    ->label('Follow-up')
                    ->dateTime('d M Y, h:i A')
                    ->color(
                        fn (?CustomerSearchActivity $record): string =>
                            $record?->requires_follow_up ? 'danger' : 'gray'
                    )
                    ->sortable()
                    ->placeholder('Not scheduled')
                    ->toggleable(),

                TextColumn::make('booking_number')
                    ->label('Booking')
                    ->searchable()
                    ->placeholder(
                        fn (CustomerSearchActivity $record): string =>
                            $record->is_converted ? 'Converted' : 'Not converted'
                    )
                    ->badge()
                    ->color(
                        fn (CustomerSearchActivity $record): string =>
                            $record->is_converted ? 'success' : 'gray'
                    )
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('searched_at')
                    ->label('Created')
                    ->dateTime('d M Y, h:i A')
                    ->since()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('lead_status')
                    ->label('Lead Status')
                    ->options(self::leadStatusOptions())
                    ->multiple()
                    ->preload(),

                SelectFilter::make('priority')
                    ->label('Priority')
                    ->options(self::priorityOptions())
                    ->multiple()
                    ->preload(),

                SelectFilter::make('module')
                    ->label('Module')
                    ->options([
                        CustomerSearchActivity::MODULE_TAXI => 'Taxi',
                        CustomerSearchActivity::MODULE_SELF_DRIVE => 'Self Drive',
                        CustomerSearchActivity::MODULE_BIKE_RENTAL => 'Bike Rental',
                    ])
                    ->multiple()
                    ->preload(),

                SelectFilter::make('service_type')
                    ->label('Service')
                    ->options(self::serviceOptions())
                    ->multiple()
                    ->preload(),

                SelectFilter::make('assigned_to')
                    ->label('Assigned Executive')
                    ->options(
                        fn (): array => User::query()
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all()
                    )
                    ->searchable()
                    ->preload(),

                Filter::make('high_priority')
                    ->label('High / Urgent Priority')
                    ->query(
                        fn (Builder $query): Builder => $query->whereIn(
                            'priority',
                            [
                                CustomerSearchActivity::PRIORITY_HIGH,
                                CustomerSearchActivity::PRIORITY_URGENT,
                            ]
                        )
                    ),

                Filter::make('follow_up_due')
                    ->label('Follow-up Due')
                    ->query(
                        fn (Builder $query): Builder => $query->needsFollowUp()
                    ),

                Filter::make('abandoned')
                    ->label('Abandoned Searches')
                    ->query(
                        fn (Builder $query): Builder => $query->abandoned()
                    ),

                Filter::make('converted')
                    ->label('Converted')
                    ->query(
                        fn (Builder $query): Builder => $query->converted()
                    ),

                Filter::make('today')
                    ->label('Today')
                    ->query(
                        fn (Builder $query): Builder => $query->today()
                    ),

                Filter::make('last_7_days')
                    ->label('Last 7 Days')
                    ->query(
                        fn (Builder $query): Builder =>
                            $query->where(
                                'searched_at',
                                '>=',
                                now()->subDays(7)->startOfDay()
                            )
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make(),

                Action::make('call')
                    ->label('Call')
                    ->icon('heroicon-o-phone')
                    ->color('success')
                    ->url(
                        fn (CustomerSearchActivity $record): ?string =>
                            filled($record->mobile)
                                ? 'tel:' . $record->mobile
                                : null
                    )
                    ->openUrlInNewTab(false)
                    ->visible(
                        fn (CustomerSearchActivity $record): bool =>
                            filled($record->mobile)
                    ),

                Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->url(
                        fn (CustomerSearchActivity $record): ?string =>
                            self::whatsAppUrl($record)
                    )
                    ->openUrlInNewTab()
                    ->visible(
                        fn (CustomerSearchActivity $record): bool =>
                            filled($record->mobile)
                    ),

                Action::make('assign')
                    ->label('Assign')
                    ->icon('heroicon-o-user-plus')
                    ->form([
                        Select::make('assigned_to')
                            ->label('Executive')
                            ->options(
                                fn (): array => User::query()
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all()
                            )
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->required(),
                    ])
                    ->fillForm(
                        fn (CustomerSearchActivity $record): array => [
                            'assigned_to' => $record->assigned_to,
                        ]
                    )
                    ->action(
                        function (
                            CustomerSearchActivity $record,
                            array $data
                        ): void {
                            $record->update([
                                'assigned_to' => $data['assigned_to'],
                                'last_activity_at' => now(),
                            ]);
                        }
                    )
                    ->successNotificationTitle('Lead assigned successfully'),

                Action::make('follow_up')
                    ->label('Follow-up')
                    ->icon('heroicon-o-calendar-days')
                    ->color('warning')
                    ->form([
                        DateTimePicker::make('follow_up_at')
                            ->label('Follow-up Date & Time')
                            ->seconds(false)
                            ->native(false)
                            ->required(),

                        Textarea::make('lead_notes')
                            ->label('Notes')
                            ->rows(4),
                    ])
                    ->fillForm(
                        fn (CustomerSearchActivity $record): array => [
                            'follow_up_at' => $record->follow_up_at,
                            'lead_notes' => $record->lead_notes,
                        ]
                    )
                    ->action(
                        function (
                            CustomerSearchActivity $record,
                            array $data
                        ): void {
                            $record->update([
                                'lead_status' =>
                                    CustomerSearchActivity::LEAD_FOLLOW_UP,
                                'follow_up_at' => $data['follow_up_at'],
                                'lead_notes' =>
                                    $data['lead_notes']
                                    ?? $record->lead_notes,
                                'last_activity_at' => now(),
                            ]);
                        }
                    )
                    ->successNotificationTitle('Follow-up scheduled'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    BulkAction::make('assign_executive')
                        ->label('Assign Executive')
                        ->icon('heroicon-o-user-plus')
                        ->form([
                            Select::make('assigned_to')
                                ->label('Executive')
                                ->options(
                                    fn (): array => User::query()
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all()
                                )
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->required(),
                        ])
                        ->action(
                            function (
                                Collection $records,
                                array $data
                            ): void {
                                $records->each(
                                    fn (CustomerSearchActivity $record) =>
                                        $record->update([
                                            'assigned_to' =>
                                                $data['assigned_to'],
                                            'last_activity_at' => now(),
                                        ])
                                );
                            }
                        )
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Leads assigned'),

                    BulkAction::make('change_status')
                        ->label('Change Lead Status')
                        ->icon('heroicon-o-arrow-path')
                        ->form([
                            Select::make('lead_status')
                                ->label('Lead Status')
                                ->options(self::leadStatusOptions())
                                ->native(false)
                                ->required(),
                        ])
                        ->action(
                            function (
                                Collection $records,
                                array $data
                            ): void {
                                $records->each(
                                    fn (CustomerSearchActivity $record) =>
                                        $record->update([
                                            'lead_status' =>
                                                $data['lead_status'],
                                            'last_activity_at' => now(),
                                        ])
                                );
                            }
                        )
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Lead statuses updated'),
                ]),
            ])
            ->defaultSort('searched_at', 'desc')
            ->poll('60s')
            ->striped()
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistSortInSession();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'customer_name',
            'mobile',
            'customer_email',
            'pickup_location',
            'pickup_city',
            'drop_location',
            'drop_city',
            'vehicle_name',
            'booking_number',
        ];
    }

    public static function getGlobalSearchResultTitle(
        \Illuminate\Database\Eloquent\Model $record
    ): string {
        /** @var CustomerSearchActivity $record */
        return $record->customer_display_name;
    }

    public static function getGlobalSearchResultDetails(
        \Illuminate\Database\Eloquent\Model $record
    ): array {
        /** @var CustomerSearchActivity $record */
        return [
            'Service' => $record->service_label,
            'Route' => $record->route_summary,
            'Mobile' => $record->mobile ?: 'Not available',
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomerLeads::route('/'),
            'view' => Pages\ViewCustomerLead::route('/{record}'),
            'edit' => Pages\EditCustomerLead::route('/{record}/edit'),
        ];
    }

    private static function leadStatusOptions(): array
    {
        return [
            CustomerSearchActivity::LEAD_NEW => 'New',
            CustomerSearchActivity::LEAD_CONTACTED => 'Contacted',
            CustomerSearchActivity::LEAD_FOLLOW_UP => 'Follow Up',
            CustomerSearchActivity::LEAD_CONVERTED => 'Converted',
            CustomerSearchActivity::LEAD_LOST => 'Lost',
            CustomerSearchActivity::LEAD_NOT_INTERESTED =>
                'Not Interested',
        ];
    }

    private static function priorityOptions(): array
    {
        return [
            CustomerSearchActivity::PRIORITY_LOW => 'Low',
            CustomerSearchActivity::PRIORITY_MEDIUM => 'Medium',
            CustomerSearchActivity::PRIORITY_HIGH => 'High',
            CustomerSearchActivity::PRIORITY_URGENT => 'Urgent',
        ];
    }

    private static function serviceOptions(): array
    {
        return [
            CustomerSearchActivity::SERVICE_ONE_WAY => 'One Way',
            CustomerSearchActivity::SERVICE_ROUND_TRIP => 'Round Trip',
            CustomerSearchActivity::SERVICE_LOCAL => 'Local Rental',
            CustomerSearchActivity::SERVICE_AIRPORT => 'Airport Transfer',
            CustomerSearchActivity::SERVICE_TOUR => 'Multi-City Tour',
            CustomerSearchActivity::SERVICE_SELF_DRIVE => 'Self Drive',
            CustomerSearchActivity::SERVICE_BIKE_RENTAL => 'Bike Rental',
        ];
    }

    private static function whatsAppUrl(
        CustomerSearchActivity $record
    ): ?string {
        if (blank($record->mobile)) {
            return null;
        }

        $mobile = preg_replace('/\D+/', '', (string) $record->mobile);

        if (strlen($mobile) === 10) {
            $mobile = '91' . $mobile;
        }

        $message = implode("\n", [
            'Hello ' . $record->customer_display_name . ',',
            '',
            'This is Dura Cabs regarding your '
                . $record->service_label
                . ' enquiry.',
            'Route: ' . $record->route_summary,
            'Estimated Fare: ' . $record->formatted_amount,
            '',
            'Please reply here if you need any assistance.',
        ]);

        return 'https://wa.me/'
            . $mobile
            . '?text='
            . rawurlencode($message);
    }
}
