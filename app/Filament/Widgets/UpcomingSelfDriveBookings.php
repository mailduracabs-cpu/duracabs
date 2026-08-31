<?php

namespace App\Filament\Widgets;

use App\Models\SelfDriveBooking;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class UpcomingSelfDriveBookings extends BaseWidget
{
    protected static ?string $heading = 'Upcoming Self Drive Bookings';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        $now = now();

        return $table
            ->query(
                SelfDriveBooking::query()
                    ->with([
                        'customer',
                        'vehicle',
                        'transporter',
                    ])
                    ->where('start_datetime', '>', $now)
                    ->whereNotIn('status', [
                        SelfDriveBooking::STATUS_COMPLETED,
                        SelfDriveBooking::STATUS_CANCELLED,
                        SelfDriveBooking::STATUS_REJECTED,
                        SelfDriveBooking::STATUS_FAILED,
                    ])
                    ->orderBy('start_datetime')
            )

            ->description(
                'Next scheduled Self Drive bookings from '
                . $now->format('d M Y, h:i A')
            )

            ->columns([
                Tables\Columns\TextColumn::make('booking_no')
                    ->label('Booking')
                    ->searchable()
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->placeholder('Customer'),

                Tables\Columns\TextColumn::make('vehicle_name')
                    ->label('Car')
                    ->state(function (SelfDriveBooking $record): string {
                        if (! $record->vehicle) {
                            return 'Vehicle not assigned';
                        }

                        $name = trim(
                            (string) ($record->vehicle->car_company_name ?? '')
                            . ' '
                            . (string) ($record->vehicle->model_name ?? '')
                        );

                        return $name !== ''
                            ? $name
                            : 'Self Drive Car';
                    })
                    ->description(
                        fn (SelfDriveBooking $record): string =>
                            (string) (
                                $record->vehicle?->vehicle_number
                                ?: 'No vehicle number'
                            )
                    )
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('start_datetime')
                    ->label('Pickup')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('end_datetime')
                    ->label('Return')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),

                Tables\Columns\TextColumn::make('pickup_location')
                    ->label('Location')
                    ->limit(30)
                    ->tooltip(
                        fn (SelfDriveBooking $record): ?string =>
                            $record->pickup_location
                    )
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string =>
                            ucfirst($state ?: 'pending')
                    )
                    ->color(fn (?string $state): string => match ($state) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        'refunded' => 'gray',
                        default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string =>
                            ucwords(
                                str_replace(
                                    '_',
                                    ' ',
                                    $state ?: 'pending'
                                )
                            )
                    )
                    ->color(fn (?string $state): string => match ($state) {
                        SelfDriveBooking::STATUS_CONFIRMED => 'success',
                        SelfDriveBooking::STATUS_PICKUP_PENDING => 'warning',
                        SelfDriveBooking::STATUS_PAYMENT_PENDING => 'danger',
                        default => 'gray',
                    }),
            ])

            ->defaultPaginationPageOption(10)

            ->paginationPageOptions([
                5,
                10,
                25,
                50,
            ])

            ->emptyStateHeading('No upcoming Self Drive bookings')

            ->emptyStateDescription(
                'Future Self Drive bookings will appear here.'
            )

            ->emptyStateIcon('heroicon-o-calendar-days');
    }
}