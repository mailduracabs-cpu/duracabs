<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Booking Overview')
                ->icon('heroicon-o-clipboard-document-check')
                ->schema([
                    Grid::make(4)
                        ->schema([
                            TextEntry::make('booking_reference')
                                ->label('Booking Number')
                                ->state(fn (): string =>
                                    OrderResource::bookingNumber($this->record)
                                )
                                ->copyable()
                                ->weight('bold'),

                            TextEntry::make('ride_type')
                                ->label('Booking Type')
                                ->badge()
                                ->formatStateUsing(
                                    fn ($state): string =>
                                    OrderResource::rideTypes()[$state]
                                        ?? Str::headline((string) $state)
                                ),

                            TextEntry::make('status')
                                ->label('Booking Status')
                                ->badge()
                                ->formatStateUsing(
                                    fn ($state): string =>
                                    OrderResource::statusOptions()[$state]
                                        ?? Str::headline((string) $state)
                                )
                                ->color(fn ($state): string => match ($state) {
                                    'confirm', 'start', 'closed' => 'success',
                                    'cancelled' => 'danger',
                                    'refund' => 'info',
                                    'reconfirmation', 'modification' => 'warning',
                                    default => 'gray',
                                }),

                            TextEntry::make('created_at')
                                ->label('Created')
                                ->dateTime('d M Y, h:i A'),
                        ]),
                ]),

            Section::make('Customer')
                ->icon('heroicon-o-user')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            TextEntry::make('user.name')
                                ->label('Customer Name')
                                ->placeholder('Not available'),

                            TextEntry::make('user.mobile')
                                ->label('Mobile Number')
                                ->copyable()
                                ->placeholder('Not available'),

                            TextEntry::make('user.email')
                                ->label('Email')
                                ->copyable()
                                ->placeholder('Not available'),
                        ]),
                ]),

            Section::make('Journey')
                ->icon('heroicon-o-map')
                ->schema([
                    Grid::make(4)
                        ->schema([
                            TextEntry::make('cityFrom')
                                ->label('Pickup')
                                ->placeholder('Not available'),

                            TextEntry::make('cityTo')
                                ->label('Drop / First Destination')
                                ->placeholder('Not available'),

                            TextEntry::make('date')
                                ->label('Pickup Date')
                                ->date('d M Y')
                                ->placeholder('Not available'),

                            TextEntry::make('time')
                                ->label('Pickup Time')
                                ->time('h:i A')
                                ->placeholder('Not available'),

                            TextEntry::make('dateTo')
                                ->label('Return Date')
                                ->date('d M Y')
                                ->placeholder('Not applicable'),

                            TextEntry::make('endTime')
                                ->label('Return Time')
                                ->time('h:i A')
                                ->placeholder('Not applicable'),

                            TextEntry::make('total_km')
                                ->label('Total / Billable KM')
                                ->suffix(' KM')
                                ->placeholder('Not available'),

                            TextEntry::make('plan')
                                ->label('Local Plan')
                                ->placeholder('Not applicable'),
                        ]),

                    TextEntry::make('route_preview')
                        ->label('Complete Route')
                        ->state(function (): string {
                            $extraOptions = $this->extraOptions();

                            $stops = array_values(array_filter(
                                Arr::wrap($extraOptions['route_stops'] ?? []),
                                static fn ($stop): bool => filled($stop)
                            ));

                            $route = [];

                            if (filled($this->record->cityFrom)) {
                                $route[] = $this->record->cityFrom;
                            }

                            if (filled($this->record->cityTo)) {
                                $route[] = $this->record->cityTo;
                            }

                            foreach ($stops as $stop) {
                                if (! in_array($stop, $route, true)) {
                                    $route[] = $stop;
                                }
                            }

                            if (
                                ($extraOptions['return_to_pickup'] ?? false)
                                && filled($this->record->cityFrom)
                                && end($route) !== $this->record->cityFrom
                            ) {
                                $route[] = $this->record->cityFrom;
                            }

                            return $route === []
                                ? 'Route not available'
                                : implode(' → ', $route);
                        })
                        ->columnSpanFull()
                        ->weight('bold'),
                ]),

            Section::make('Vehicle & Assignment')
                ->icon('heroicon-o-truck')
                ->schema([
                    Grid::make(4)
                        ->schema([
                            TextEntry::make('productName')
                                ->label('Vehicle / Package')
                                ->placeholder('Not assigned'),

                            TextEntry::make('taxi_type')
                                ->label('Vehicle Category')
                                ->formatStateUsing(
                                    fn ($state): string =>
                                    filled($state)
                                        ? Str::headline((string) $state)
                                        : 'Not assigned'
                                ),

                            TextEntry::make('transporter.name')
                                ->label('Transporter')
                                ->placeholder('Not assigned'),

                            TextEntry::make('driver.name')
                                ->label('Driver')
                                ->placeholder('Not assigned'),

                            TextEntry::make('vehicle.vehicle_number')
                                ->label('Vehicle Number')
                                ->placeholder('Not assigned'),
                        ]),
                ]),

            Section::make('Fare Summary')
                ->icon('heroicon-o-banknotes')
                ->schema([
                    Grid::make(4)
                        ->schema([
                            TextEntry::make('fare_type')
                                ->label('Fare Type')
                                ->state(function (): string {
                                    $fareType = (string) (
                                        $this->extraOptions()['fare_type']
                                        ?? 'normal'
                                    );

                                    return $fareType === 'all_inclusive'
                                        ? 'All Inclusive'
                                        : 'Normal Fare';
                                })
                                ->badge()
                                ->color(function (): string {
                                    return (
                                        $this->extraOptions()['fare_type']
                                        ?? 'normal'
                                    ) === 'all_inclusive'
                                        ? 'success'
                                        : 'info';
                                }),

                            TextEntry::make('calculated_amount')
                                ->label('Calculated Fare')
                                ->state(fn (): float =>
                                    $this->money(
                                        $this->extraOptions()[
                                            'calculated_amount'
                                        ] ?? $this->record->grand_total
                                    )
                                )
                                ->money('INR'),

                            TextEntry::make('manual_discount')
                                ->label('Discount')
                                ->state(fn (): float =>
                                    $this->money(
                                        $this->extraOptions()[
                                            'manual_discount'
                                        ] ?? $this->record->coupon_value
                                    )
                                )
                                ->money('INR'),

                            TextEntry::make('grand_total')
                                ->label('Final Amount')
                                ->money('INR')
                                ->weight('bold')
                                ->color('success'),
                        ]),

                    TextEntry::make('fare_override_reason')
                        ->label('Discount / Fare Change Reason')
                        ->state(fn (): string =>
                            (string) (
                                $this->extraOptions()[
                                    'fare_override_reason'
                                ] ?? 'No manual change'
                            )
                        )
                        ->columnSpanFull(),

                    TextEntry::make('parking_policy')
                        ->label('Parking Policy')
                        ->state(function (): string {
                            return (
                                $this->extraOptions()['parking_policy']
                                ?? null
                            ) === 'pay_direct_as_actual'
                                ? 'Parking charges are payable directly as actual.'
                                : 'Not specified';
                        })
                        ->columnSpanFull(),
                ]),

            Section::make('Payment')
                ->icon('heroicon-o-credit-card')
                ->schema([
                    Grid::make(4)
                        ->schema([
                            TextEntry::make('payment_method')
                                ->label('Method')
                                ->badge()
                                ->formatStateUsing(
                                    fn ($state): string =>
                                    OrderResource::paymentMethods()[$state]
                                        ?? Str::headline((string) $state)
                                ),

                            TextEntry::make('payment_status')
                                ->label('Payment Status')
                                ->badge()
                                ->formatStateUsing(
                                    fn ($state): string =>
                                    OrderResource::paymentStatuses()[$state]
                                        ?? Str::headline((string) $state)
                                )
                                ->color(fn ($state): string => match ($state) {
                                    'paid' => 'success',
                                    'failed' => 'danger',
                                    'refunded' => 'info',
                                    default => 'warning',
                                }),

                            TextEntry::make('payment_request_type')
                                ->label('Payment Request')
                                ->state(function (): string {
                                    $request = (array) (
                                        $this->extraOptions()[
                                            'payment_request'
                                        ] ?? []
                                    );

                                    return Str::headline(
                                        (string) ($request['type'] ?? 'Not set')
                                    );
                                }),

                            TextEntry::make('payment_request_amount')
                                ->label('Requested Amount')
                                ->state(function (): float {
                                    $request = (array) (
                                        $this->extraOptions()[
                                            'payment_request'
                                        ] ?? []
                                    );

                                    return $this->money(
                                        $request['amount'] ?? 0
                                    );
                                })
                                ->money('INR'),
                        ]),
                ]),

            Section::make('Notes')
                ->icon('heroicon-o-document-text')
                ->schema([
                    TextEntry::make('notes')
                        ->label('Booking Notes')
                        ->placeholder('No notes added')
                        ->columnSpanFull(),
                ])
                ->collapsed(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit Booking'),

            Actions\Action::make('back_to_bookings')
                ->label('All Bookings')
                ->icon('heroicon-o-arrow-left')
                ->url(OrderResource::getUrl('index'))
                ->color('gray'),
        ];
    }

    public function getTitle(): string
    {
        return 'Booking ' . OrderResource::bookingNumber($this->record);
    }

    /**
     * @return array<string, mixed>
     */
    private function extraOptions(): array
    {
        $extraOptions = $this->record->extraOptions;

        if (is_string($extraOptions)) {
            $extraOptions = json_decode($extraOptions, true);
        }

        return is_array($extraOptions) ? $extraOptions : [];
    }

    private function money(mixed $value): float
    {
        return round(max(0, (float) $value), 2);
    }
}