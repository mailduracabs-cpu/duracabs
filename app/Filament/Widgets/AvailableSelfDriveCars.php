<?php

namespace App\Filament\Widgets;

use App\Models\SelfDriveBooking;
use App\Models\Vehicle;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class AvailableSelfDriveCars extends BaseWidget
{
    protected static ?string $heading = 'Available Self Drive Cars Now';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        $now = now();

        return $table
            ->query(
                Vehicle::query()
                    ->with([
                        'transporter',
                        'frontMedia',
                    ])
                    ->where('service_type', Vehicle::SERVICE_SELF_DRIVE)
                    ->where('vehicle_type', Vehicle::TYPE_CAR)
                    ->where('is_active', true)
                    ->where('is_live', true)
                    ->where('is_verified', true)

                    /*
                     * Vehicle is unavailable only when an active
                     * Self Drive booking covers the CURRENT time.
                     */
                    ->whereDoesntHave(
                        'selfDriveBookings',
                        function (Builder $query) use ($now): void {
                            $query
                                ->activeBooking()
                                ->where('start_datetime', '<=', $now)
                                ->where('end_datetime', '>', $now);
                        }
                    )
                    ->orderBy('car_company_name')
                    ->orderBy('model_name')
            )

            ->description(
                'Live availability as of '
                . $now->format('d M Y, h:i A')
            )

            ->columns([
                Tables\Columns\ImageColumn::make('front_image_url')
                    ->label('Car')
                    ->circular(false)
                    ->square()
                    ->defaultImageUrl(
                        'https://ui-avatars.com/api/?name=Car'
                    ),

                Tables\Columns\TextColumn::make('vehicle_name')
                    ->label('Vehicle')
                    ->state(function (Vehicle $record): string {
                        $name = trim(
                            (string) ($record->car_company_name ?? '')
                            . ' '
                            . (string) ($record->model_name ?? '')
                        );

                        return $name !== ''
                            ? $name
                            : 'Self Drive Car';
                    })
                    ->description(
                        fn (Vehicle $record): string =>
                            (string) ($record->vehicle_number ?: 'No number')
                    )
                    ->searchable([
                        'car_company_name',
                        'model_name',
                        'vehicle_number',
                    ])
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('transporter_name')
                    ->label('Vendor')
                    ->state(function (Vehicle $record): string {
                        $transporter = $record->transporter;

                        if (! $transporter) {
                            return '—';
                        }

                        return (string) (
                            $transporter->business_name
                            ?? $transporter->company_name
                            ?? $transporter->name
                            ?? 'Vendor'
                        );
                    }),

                Tables\Columns\TextColumn::make('daily_price')
                    ->label('Price / Day')
                    ->money('INR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('next_booking')
                    ->label('Next Booking')
                    ->state(function (Vehicle $record) use ($now): string {
                        $nextBooking = SelfDriveBooking::query()
                            ->where('vehicle_id', $record->id)
                            ->activeBooking()
                            ->where('start_datetime', '>', $now)
                            ->orderBy('start_datetime')
                            ->first();

                        if (! $nextBooking) {
                            return 'No upcoming booking';
                        }

                        return $nextBooking->start_datetime
                            ? $nextBooking->start_datetime->format(
                                'd M Y, h:i A'
                            )
                            : '—';
                    })
                    ->badge()
                    ->color(
                        fn (string $state): string =>
                            $state === 'No upcoming booking'
                                ? 'success'
                                : 'warning'
                    ),

                Tables\Columns\TextColumn::make('availability')
                    ->label('Status')
                    ->state('Available')
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-m-check-circle'),
            ])

            ->defaultPaginationPageOption(10)

            ->paginationPageOptions([
                5,
                10,
                25,
                50,
            ])

            ->emptyStateHeading(
                'No Self Drive cars available right now'
            )

            ->emptyStateDescription(
                'All active Self Drive cars currently have bookings.'
            )

            ->emptyStateIcon('heroicon-o-car');
    }
}