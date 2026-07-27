<?php

namespace App\Filament\Resources\CustomerLeadResource\Pages;

use App\Filament\Resources\CustomerLeadResource;
use App\Filament\Resources\CustomerLeadResource\Widgets\CustomerActivityTimelineWidget;
use App\Models\CustomerSearchActivity;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomerLead extends ViewRecord
{
    protected static string $resource = CustomerLeadResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Lead Overview')
                    ->description('Customer intent, priority and current journey status.')
                    ->icon('heroicon-o-chart-bar-square')
                    ->columns([
                        'default' => 1,
                        'sm' => 2,
                        'xl' => 4,
                    ])
                    ->schema([
                        TextEntry::make('lead_status')
                            ->label('Lead Status')
                            ->badge()
                            ->formatStateUsing(
                                fn (?string $state): string =>
                                    str($state ?? 'new')
                                        ->replace('_', ' ')
                                        ->title()
                                        ->toString()
                            )
                            ->color(fn (?string $state): string => match ($state) {
                                CustomerSearchActivity::LEAD_CONVERTED => 'success',
                                CustomerSearchActivity::LEAD_LOST,
                                CustomerSearchActivity::LEAD_NOT_INTERESTED => 'danger',
                                CustomerSearchActivity::LEAD_FOLLOW_UP => 'warning',
                                CustomerSearchActivity::LEAD_CONTACTED => 'info',
                                default => 'gray',
                            }),

                        TextEntry::make('priority')
                            ->label('Priority')
                            ->badge()
                            ->formatStateUsing(
                                fn (?string $state): string =>
                                    ucfirst($state ?? 'low')
                            )
                            ->color(fn (?string $state): string => match ($state) {
                                CustomerSearchActivity::PRIORITY_URGENT => 'danger',
                                CustomerSearchActivity::PRIORITY_HIGH => 'warning',
                                CustomerSearchActivity::PRIORITY_MEDIUM => 'info',
                                default => 'gray',
                            }),

                        TextEntry::make('intent_score')
                            ->label('Intent Score')
                            ->formatStateUsing(
                                fn (?int $state): string =>
                                    ((int) ($state ?? 0)) . '/100'
                            )
                            ->badge()
                            ->color(fn (?int $state): string => match (true) {
                                ((int) $state) >= 80 => 'danger',
                                ((int) $state) >= 60 => 'warning',
                                ((int) $state) >= 30 => 'info',
                                default => 'gray',
                            }),

                        TextEntry::make('stage')
                            ->label('Journey Stage')
                            ->formatStateUsing(
                                fn (?string $state): string =>
                                    str($state ?? 'initiated')
                                        ->replace('_', ' ')
                                        ->title()
                                        ->toString()
                            )
                            ->badge()
                            ->color('primary'),
                    ]),

                Section::make('Customer Information')
                    ->icon('heroicon-o-user-circle')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ])
                    ->schema([
                        TextEntry::make('customer_display_name')
                            ->label('Customer Name')
                            ->weight('bold')
                            ->icon('heroicon-m-user'),

                        TextEntry::make('mobile')
                            ->label('Mobile Number')
                            ->copyable()
                            ->copyMessage('Mobile copied')
                            ->placeholder('Not available')
                            ->icon('heroicon-m-phone'),

                        TextEntry::make('customer_email')
                            ->label('Email')
                            ->copyable()
                            ->placeholder('Not available')
                            ->icon('heroicon-m-envelope'),

                        TextEntry::make('source')
                            ->label('Source')
                            ->formatStateUsing(
                                fn (?string $state): string =>
                                    str($state ?? 'unknown')
                                        ->replace('_', ' ')
                                        ->title()
                                        ->toString()
                            )
                            ->badge(),

                        TextEntry::make('platform')
                            ->label('Platform')
                            ->placeholder('Not available'),

                        TextEntry::make('app_version')
                            ->label('App Version')
                            ->placeholder('Not available'),

                        TextEntry::make('device_name')
                            ->label('Device')
                            ->placeholder('Not available'),

                        TextEntry::make('operating_system')
                            ->label('Operating System')
                            ->placeholder('Not available'),

                        TextEntry::make('session_id')
                            ->label('Session ID')
                            ->copyable()
                            ->limit(25)
                            ->placeholder('Not available'),
                    ]),

                Section::make('Trip Information')
                    ->icon('heroicon-o-map')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ])
                    ->schema([
                        TextEntry::make('service_label')
                            ->label('Service')
                            ->badge()
                            ->color('primary'),

                        TextEntry::make('route_summary')
                            ->label('Route')
                            ->columnSpan([
                                'default' => 1,
                                'md' => 2,
                            ])
                            ->weight('bold'),

                        TextEntry::make('pickup_location')
                            ->label('Pickup Location')
                            ->placeholder('Not available'),

                        TextEntry::make('drop_location')
                            ->label('Drop Location')
                            ->placeholder('Not available'),

                        TextEntry::make('start_datetime')
                            ->label('Start Date & Time')
                            ->dateTime('d M Y, h:i A')
                            ->placeholder('Not selected'),

                        TextEntry::make('end_datetime')
                            ->label('End Date & Time')
                            ->dateTime('d M Y, h:i A')
                            ->placeholder('Not selected'),

                        TextEntry::make('return_datetime')
                            ->label('Return Date & Time')
                            ->dateTime('d M Y, h:i A')
                            ->placeholder('Not applicable'),

                        TextEntry::make('vehicle_name')
                            ->label('Vehicle')
                            ->placeholder('Not selected'),

                        TextEntry::make('vehicle_category_name')
                            ->label('Vehicle Category')
                            ->placeholder('Not selected'),

                        TextEntry::make('estimated_distance_km')
                            ->label('Estimated Distance')
                            ->suffix(' km')
                            ->placeholder('Not available'),

                        TextEntry::make('estimated_duration_minutes')
                            ->label('Estimated Duration')
                            ->formatStateUsing(
                                fn (?int $state): string =>
                                    $this->formatDuration((int) ($state ?? 0))
                            )
                            ->placeholder('Not available'),

                        TextEntry::make('total_stops')
                            ->label('Stops')
                            ->placeholder('0'),
                    ]),

                Section::make('Fare & Payment')
                    ->icon('heroicon-o-currency-rupee')
                    ->columns([
                        'default' => 1,
                        'sm' => 2,
                        'xl' => 4,
                    ])
                    ->schema([
                        TextEntry::make('base_fare')
                            ->label('Base Fare')
                            ->money('INR')
                            ->placeholder('Not available'),

                        TextEntry::make('discount_amount')
                            ->label('Discount')
                            ->money('INR')
                            ->placeholder('₹0'),

                        TextEntry::make('coupon_discount')
                            ->label('Coupon Discount')
                            ->money('INR')
                            ->placeholder('₹0'),

                        TextEntry::make('driver_allowance')
                            ->label('Driver Allowance')
                            ->money('INR')
                            ->placeholder('₹0'),

                        TextEntry::make('toll_amount')
                            ->label('Toll')
                            ->money('INR')
                            ->placeholder('₹0'),

                        TextEntry::make('parking_amount')
                            ->label('Parking')
                            ->money('INR')
                            ->placeholder('₹0'),

                        TextEntry::make('tax_amount')
                            ->label('Tax')
                            ->money('INR')
                            ->placeholder('₹0'),

                        TextEntry::make('grand_total')
                            ->label('Grand Total')
                            ->money('INR')
                            ->weight('bold')
                            ->color('success')
                            ->placeholder('Not available'),

                        TextEntry::make('payment_status')
                            ->label('Payment Status')
                            ->badge()
                            ->formatStateUsing(
                                fn (?string $state): string =>
                                    str($state ?? 'not_started')
                                        ->replace('_', ' ')
                                        ->title()
                                        ->toString()
                            )
                            ->color(fn (?string $state): string => match ($state) {
                                CustomerSearchActivity::PAYMENT_SUCCESS => 'success',
                                CustomerSearchActivity::PAYMENT_FAILED,
                                CustomerSearchActivity::PAYMENT_CANCELLED => 'danger',
                                CustomerSearchActivity::PAYMENT_STARTED,
                                CustomerSearchActivity::PAYMENT_PENDING => 'warning',
                                default => 'gray',
                            }),

                        IconEntry::make('is_all_inclusive')
                            ->label('All Inclusive')
                            ->boolean(),

                        TextEntry::make('coupon_code')
                            ->label('Coupon Code')
                            ->badge()
                            ->placeholder('No coupon'),
                    ]),

                Section::make('Booking & Conversion')
                    ->icon('heroicon-o-check-badge')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 4,
                    ])
                    ->schema([
                        IconEntry::make('is_converted')
                            ->label('Converted')
                            ->boolean(),

                        TextEntry::make('booking_type')
                            ->label('Booking Type')
                            ->formatStateUsing(
                                fn (?string $state): string =>
                                    str($state ?? 'not_available')
                                        ->replace('_', ' ')
                                        ->title()
                                        ->toString()
                            )
                            ->placeholder('Not converted'),

                        TextEntry::make('booking_number')
                            ->label('Booking Number')
                            ->copyable()
                            ->placeholder('Not converted'),

                        TextEntry::make('converted_at')
                            ->label('Converted At')
                            ->dateTime('d M Y, h:i A')
                            ->placeholder('Not converted'),

                        TextEntry::make('checkout_status')
                            ->label('Checkout Status')
                            ->badge()
                            ->formatStateUsing(
                                fn (?string $state): string =>
                                    str($state ?? 'not_started')
                                        ->replace('_', ' ')
                                        ->title()
                                        ->toString()
                            ),

                        TextEntry::make('checkout_started_at')
                            ->label('Checkout Started')
                            ->dateTime('d M Y, h:i A')
                            ->placeholder('Not started'),

                        TextEntry::make('payment_started_at')
                            ->label('Payment Started')
                            ->dateTime('d M Y, h:i A')
                            ->placeholder('Not started'),

                        TextEntry::make('abandoned_at')
                            ->label('Abandoned At')
                            ->dateTime('d M Y, h:i A')
                            ->placeholder('Not abandoned'),
                    ]),

                Section::make('CRM Follow-up')
                    ->icon('heroicon-o-briefcase')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        TextEntry::make('assignedUser.name')
                            ->label('Assigned Executive')
                            ->placeholder('Unassigned')
                            ->icon('heroicon-m-user-plus'),

                        TextEntry::make('follow_up_at')
                            ->label('Next Follow-up')
                            ->dateTime('d M Y, h:i A')
                            ->placeholder('Not scheduled')
                            ->color(
                                fn (CustomerSearchActivity $record): string =>
                                    $record->requires_follow_up
                                        ? 'danger'
                                        : 'gray'
                            ),

                        TextEntry::make('lead_notes')
                            ->label('Internal Notes')
                            ->columnSpanFull()
                            ->markdown()
                            ->placeholder('No notes added'),

                        TextEntry::make('last_activity_at')
                            ->label('Last Activity')
                            ->dateTime('d M Y, h:i A')
                            ->since(),
                    ]),

                Section::make('Notification Status')
                    ->icon('heroicon-o-bell-alert')
                    ->columns([
                        'default' => 2,
                        'md' => 5,
                    ])
                    ->collapsed()
                    ->schema([
                        IconEntry::make('admin_notified')
                            ->label('Admin')
                            ->boolean(),

                        IconEntry::make('whatsapp_notified')
                            ->label('WhatsApp')
                            ->boolean(),

                        IconEntry::make('sms_notified')
                            ->label('SMS')
                            ->boolean(),

                        IconEntry::make('push_notified')
                            ->label('Push')
                            ->boolean(),

                        IconEntry::make('email_notified')
                            ->label('Email')
                            ->boolean(),
                    ]),

                Section::make('Technical Data')
                    ->icon('heroicon-o-code-bracket')
                    ->collapsed()
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        TextEntry::make('uuid')
                            ->label('Lead UUID')
                            ->copyable(),

                        TextEntry::make('ip_address')
                            ->label('IP Address')
                            ->placeholder('Not available'),

                        TextEntry::make('searched_at')
                            ->label('Search Created')
                            ->dateTime('d M Y, h:i A'),

                        TextEntry::make('expires_at')
                            ->label('Expires At')
                            ->dateTime('d M Y, h:i A')
                            ->placeholder('No expiry'),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('call')
                ->label('Call Customer')
                ->icon('heroicon-o-phone')
                ->color('success')
                ->url(
                    fn (): ?string =>
                        filled($this->record->mobile)
                            ? 'tel:' . $this->record->mobile
                            : null
                )
                ->visible(
                    fn (): bool => filled($this->record->mobile)
                ),

            Action::make('whatsapp')
                ->label('WhatsApp')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('success')
                ->url(fn (): ?string => $this->getWhatsAppUrl())
                ->openUrlInNewTab()
                ->visible(
                    fn (): bool => filled($this->record->mobile)
                ),

            Actions\EditAction::make()
                ->label('Manage Lead'),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            CustomerActivityTimelineWidget::class,
        ];
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return 1;
    }

    private function getWhatsAppUrl(): ?string
    {
        if (blank($this->record->mobile)) {
            return null;
        }

        $mobile = preg_replace(
            '/\D+/',
            '',
            (string) $this->record->mobile
        );

        if (strlen($mobile) === 10) {
            $mobile = '91' . $mobile;
        }

        $message = implode("\n", [
            'Hello ' . $this->record->customer_display_name . ',',
            '',
            'This is Dura Cabs regarding your '
                . $this->record->service_label
                . ' enquiry.',
            'Route: ' . $this->record->route_summary,
            'Estimated Fare: ' . $this->record->formatted_amount,
            '',
            'Please reply here if you need any assistance.',
        ]);

        return 'https://wa.me/'
            . $mobile
            . '?text='
            . rawurlencode($message);
    }

    private function formatDuration(int $minutes): string
    {
        if ($minutes <= 0) {
            return 'Not available';
        }

        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        if ($hours <= 0) {
            return $remainingMinutes . ' min';
        }

        if ($remainingMinutes <= 0) {
            return $hours . ' hr';
        }

        return $hours . ' hr ' . $remainingMinutes . ' min';
    }
}
