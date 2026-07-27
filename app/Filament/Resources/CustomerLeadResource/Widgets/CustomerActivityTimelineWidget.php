<?php

namespace App\Filament\Resources\CustomerLeadResource\Widgets;

use App\Models\CustomerActivity;
use App\Models\CustomerSearchActivity;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class CustomerActivityTimelineWidget extends BaseWidget
{
    public ?CustomerSearchActivity $record = null;

    protected static ?string $heading = 'Customer Journey Timeline';

    protected static ?string $description =
        'Search, vehicle, checkout, payment and booking activity for this customer.';

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '60s';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getActivityQuery())
            ->columns([
                TextColumn::make('activity_title')
                    ->label('Activity')
                    ->icon(fn (CustomerActivity $record): string => match ($record->event) {
                        'otp_requested',
                        'otp_verified',
                        'user_registered',
                        'user_login' => 'heroicon-o-user-circle',

                        'one_way_search',
                        'round_trip_search',
                        'local_search',
                        'airport_search',
                        'tour_search',
                        'self_drive_search',
                        'bike_rental_search' => 'heroicon-o-magnifying-glass',

                        'vehicle_viewed' => 'heroicon-o-eye',
                        'vehicle_selected' => 'heroicon-o-check-circle',
                        'checkout_started' => 'heroicon-o-shopping-cart',
                        'payment_started' => 'heroicon-o-credit-card',
                        'payment_failed' => 'heroicon-o-x-circle',
                        'payment_success' => 'heroicon-o-banknotes',
                        'booking_created' => 'heroicon-o-check-badge',
                        'booking_cancelled' => 'heroicon-o-no-symbol',
                        'trip_started' => 'heroicon-o-play-circle',
                        'trip_completed' => 'heroicon-o-flag',
                        default => 'heroicon-o-bolt',
                    })
                    ->description(
                        fn (CustomerActivity $record): string =>
                            str($record->stage ?: $record->event)
                                ->replace('_', ' ')
                                ->title()
                                ->toString()
                    )
                    ->weight('bold')
                    ->wrap()
                    ->searchable(['event', 'stage']),

                TextColumn::make('module')
                    ->label('Module')
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string =>
                            str($state ?: 'unknown')
                                ->replace('_', ' ')
                                ->title()
                                ->toString()
                    )
                    ->color(fn (?string $state): string => match ($state) {
                        'taxi' => 'primary',
                        'self_drive' => 'info',
                        'bike_rental' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('service_type')
                    ->label('Service')
                    ->formatStateUsing(
                        fn (?string $state): string =>
                            str($state ?: 'unknown')
                                ->replace('_', ' ')
                                ->title()
                                ->toString()
                    )
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('route_summary')
                    ->label('Route')
                    ->placeholder('Not applicable')
                    ->limit(45)
                    ->tooltip(
                        fn (CustomerActivity $record): ?string =>
                            $record->route_summary
                    )
                    ->wrap()
                    ->searchable([
                        'pickup_location',
                        'pickup_city',
                        'drop_location',
                        'drop_city',
                    ]),

                TextColumn::make('vehicle_name')
                    ->label('Vehicle')
                    ->placeholder('Not selected')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('estimated_amount')
                    ->label('Amount')
                    ->money('INR')
                    ->placeholder('Not available')
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('priority')
                    ->label('Priority')
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string =>
                            ucfirst($state ?: 'low')
                    )
                    ->color(fn (?string $state): string => match ($state) {
                        'urgent' => 'danger',
                        'high' => 'warning',
                        'normal' => 'info',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('occurred_at')
                    ->label('Date & Time')
                    ->dateTime('d M Y, h:i A')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('event_group')
                    ->label('Activity Type')
                    ->options([
                        'authentication' => 'Login / OTP',
                        'search' => 'Search',
                        'vehicle' => 'Vehicle',
                        'checkout' => 'Checkout',
                        'payment' => 'Payment',
                        'booking' => 'Booking',
                        'trip' => 'Trip',
                    ])
                    ->query(function (
                        Builder $query,
                        array $data
                    ): Builder {
                        return match ($data['value'] ?? null) {
                            'authentication' => $query->whereIn('event', [
                                'otp_requested',
                                'otp_verified',
                                'user_registered',
                                'user_login',
                            ]),

                            'search' => $query->whereIn('event', [
                                'one_way_search',
                                'round_trip_search',
                                'local_search',
                                'airport_search',
                                'tour_search',
                                'self_drive_search',
                                'bike_rental_search',
                            ]),

                            'vehicle' => $query->whereIn('event', [
                                'vehicle_viewed',
                                'vehicle_selected',
                            ]),

                            'checkout' => $query->where(
                                'event',
                                'checkout_started'
                            ),

                            'payment' => $query->whereIn('event', [
                                'payment_started',
                                'payment_failed',
                                'payment_success',
                            ]),

                            'booking' => $query->whereIn('event', [
                                'booking_created',
                                'booking_cancelled',
                            ]),

                            'trip' => $query->whereIn('event', [
                                'trip_started',
                                'trip_completed',
                            ]),

                            default => $query,
                        };
                    }),

                SelectFilter::make('module')
                    ->label('Module')
                    ->options([
                        'taxi' => 'Taxi',
                        'self_drive' => 'Self Drive',
                        'bike_rental' => 'Bike Rental',
                    ]),

                SelectFilter::make('service_type')
                    ->label('Service')
                    ->options([
                        'one_way' => 'One Way',
                        'round_trip' => 'Round Trip',
                        'local' => 'Local Rental',
                        'airport' => 'Airport Transfer',
                        'tour' => 'Multi-City Tour',
                        'self_drive' => 'Self Drive',
                        'bike_rental' => 'Bike Rental',
                    ]),
            ])
            ->defaultSort('occurred_at', 'desc')
            ->emptyStateHeading('No customer activity found')
            ->emptyStateDescription(
                'New customer events will appear here automatically.'
            )
            ->emptyStateIcon('heroicon-o-clock')
            ->paginated([10, 25, 50])
            ->striped();
    }

    private function getActivityQuery(): Builder
    {
        $query = CustomerActivity::query();

        if (! $this->record) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $builder): void {
            $hasIdentity = false;

            if ($this->record->user_id !== null) {
                $builder->orWhere(
                    'user_id',
                    $this->record->user_id
                );
                $hasIdentity = true;
            }

            if (filled($this->record->mobile)) {
                $builder->orWhere(
                    'mobile',
                    $this->record->mobile
                );
                $hasIdentity = true;
            }

            if (filled($this->record->session_id)) {
                $builder->orWhere(
                    'session_id',
                    $this->record->session_id
                );
                $hasIdentity = true;
            }

            if (filled($this->record->device_id)) {
                $builder->orWhere(
                    'device_id',
                    $this->record->device_id
                );
                $hasIdentity = true;
            }

            if (! $hasIdentity) {
                $builder->whereRaw('1 = 0');
            }
        });
    }
}
