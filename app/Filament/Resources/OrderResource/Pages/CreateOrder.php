<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Filament\Resources\SelfDriveBookingResource;
use App\Models\SelfDriveBooking;
use App\Models\Vehicle;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    /**
     * Temporary data collected from non-database form fields.
     *
     * @var array<string, mixed>
     */
    protected array $adminBookingMeta = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->adminBookingMeta = [
            'route_stops' => array_values(array_filter(
                Arr::wrap($data['route_stops'] ?? []),
                static fn ($stop): bool => filled($stop)
            )),
            'fare_type' => in_array(
                $data['fare_type'] ?? 'normal',
                ['normal', 'all_inclusive'],
                true
            )
                ? ($data['fare_type'] ?? 'normal')
                : 'normal',
            'calculated_amount' => $this->money(
                $data['calculated_amount'] ?? 0
            ),
            'manual_discount' => $this->money(
                $data['coupon_value'] ?? 0
            ),
            'fare_override_reason' => trim(
                (string) ($data['fare_override_reason'] ?? '')
            ),
            'payment_request_type' => in_array(
                $data['payment_request_type'] ?? 'token',
                ['token', 'full', 'custom'],
                true
            )
                ? ($data['payment_request_type'] ?? 'token')
                : 'token',
            'payment_request_amount' => $this->money(
                $data['payment_request_amount'] ?? 0
            ),
        ];

        /*
         * These fields are used only by the Filament form. They are removed
         * before the Order model is inserted to prevent unknown-column errors.
         */
        unset(
            $data['route_stops'],
            $data['fare_type'],
            $data['calculated_amount'],
            $data['fare_override_reason'],
            $data['payment_request_type'],
            $data['payment_request_amount'],
            $data['sd_pickup_location'],
            $data['sd_vehicle_id'],
            $data['sd_rental_plan'],
            $data['sd_booked_hours'],
            $data['sd_total_days'],
            $data['sd_rate'],
            $data['sd_security_deposit'],
            $data['sd_calculated_amount'],
            $data['sd_total_amount']
        );

        $data['ride_type'] = $this->normaliseRideType(
            $data['ride_type'] ?? 'one_way'
        );

        $data['grand_total'] = $this->money(
            $data['grand_total'] ?? 0
        );

        $data['coupon_value'] = $this->money(
            $data['coupon_value'] ?? 0
        );

        $data['tax'] = $this->money($data['tax'] ?? 0);
        $data['currency'] = strtoupper(
            (string) ($data['currency'] ?? 'INR')
        );

        $data['payment_status'] = in_array(
            $data['payment_status'] ?? 'pending',
            ['pending', 'paid', 'failed', 'refunded', 'partial'],
            true
        )
            ? ($data['payment_status'] ?? 'pending')
            : 'pending';

        $data['status'] = in_array(
            $data['status'] ?? 'new',
            array_keys(OrderResource::statusOptions()),
            true
        )
            ? ($data['status'] ?? 'new')
            : 'new';

        $data['notes'] = trim(
            (string) ($data['notes'] ?? '')
        );

        $admin = auth()->user();

        $existingExtraOptions = is_array($data['extraOptions'] ?? null)
            ? $data['extraOptions']
            : [];

        $extraOptions = array_merge(
            $existingExtraOptions,
            [
                'booking_source' => 'admin',
                'created_by_admin_id' => $admin?->id,
                'created_by_admin_name' => $admin?->name,
                'created_at_from_admin' => now()->toIso8601String(),
                'route_stops' => $this->adminBookingMeta['route_stops'],
                'return_to_pickup' => $data['ride_type'] === 'return',
                'fare_type' => $this->adminBookingMeta['fare_type'],
                'all_inclusive' =>
                    $this->adminBookingMeta['fare_type'] === 'all_inclusive',
                'calculated_amount' =>
                    $this->adminBookingMeta['calculated_amount'],
                'manual_discount' =>
                    $this->adminBookingMeta['manual_discount'],
                'fare_override_reason' =>
                    $this->adminBookingMeta['fare_override_reason'] ?: null,
                'final_amount' => $data['grand_total'],
                'payment_request' => [
                    'type' =>
                        $this->adminBookingMeta['payment_request_type'],
                    'amount' => $this->resolvePaymentRequestAmount(
                        $data['grand_total']
                    ),
                    'status' => 'not_sent',
                    'link' => null,
                    'sent_at' => null,
                ],
                'parking_policy' =>
                    $data['ride_type'] === 'return'
                        ? 'pay_direct_as_actual'
                        : null,
            ]
        );

        if (Schema::hasColumn('orders', 'extraOptions')) {
            $data['extraOptions'] = $extraOptions;
        } else {
            unset($data['extraOptions']);
        }

        if (Schema::hasColumn('orders', 'booking_source')) {
            $data['booking_source'] = 'admin';
        }

        if (Schema::hasColumn('orders', 'created_by_admin_id')) {
            $data['created_by_admin_id'] = $admin?->id;
        }

        if (
            Schema::hasColumn('orders', 'booking_no')
            && blank($data['booking_no'] ?? null)
        ) {
            $data['booking_no'] = $this->generateBookingNumber();
        }

        return $data;
    }

    protected function beforeCreate(): void
    {
        if (($this->data['ride_type'] ?? null) !== 'self_drive') {
            return;
        }

        $data = $this->data;
        $customerId = (int) ($data['user_id'] ?? 0);
        $vehicleId = (int) ($data['sd_vehicle_id'] ?? 0);
        $pickupLocation = trim((string) (
            $data['sd_pickup_location']
            ?? $data['cityFrom']
            ?? ''
        ));

        if ($customerId <= 0 || $vehicleId <= 0) {
            Notification::make()
                ->title('Self Drive booking incomplete')
                ->body('Customer and Self Drive Vehicle are required.')
                ->danger()
                ->send();

            $this->halt();
            return;
        }

        if (
            blank($data['date'] ?? null)
            || blank($data['time'] ?? null)
            || blank($data['dateTo'] ?? null)
            || blank($data['endTime'] ?? null)
        ) {
            Notification::make()
                ->title('Start and End time required')
                ->body('Please select Start Date, Start Time, End Date and End Time.')
                ->danger()
                ->send();

            $this->halt();
            return;
        }

        try {
    $startDate = Carbon::parse((string) $data['date']);
    $startTime = Carbon::parse((string) $data['time']);

    $endDate = Carbon::parse((string) $data['dateTo']);
    $endTime = Carbon::parse((string) $data['endTime']);

    $start = $startDate->copy()->setTime(
        $startTime->hour,
        $startTime->minute,
        0
    );

    $end = $endDate->copy()->setTime(
        $endTime->hour,
        $endTime->minute,
        0
    );
} catch (\Throwable $e) {
    Notification::make()
        ->title('Invalid Self Drive dates')
        ->body($e->getMessage())
        ->danger()
        ->persistent()
        ->send();

    $this->halt();
    return;
}

        if ($end->lte($start)) {
            Notification::make()
                ->title('Invalid Self Drive duration')
                ->body('End Date/Time must be after Start Date/Time.')
                ->danger()
                ->send();

            $this->halt();
            return;
        }

        $vehicle = Vehicle::query()->find($vehicleId);

        if (! $vehicle || ! (bool) $vehicle->is_active) {
            Notification::make()
                ->title('Vehicle unavailable')
                ->body('Selected Self Drive vehicle is not active.')
                ->danger()
                ->send();

            $this->halt();
            return;
        }

        $overlapExists = SelfDriveBooking::query()
            ->where('vehicle_id', $vehicleId)
            ->activeBooking()
            ->overlapping($start, $end)
            ->exists();

        if ($overlapExists) {
            Notification::make()
                ->title('Vehicle already booked')
                ->body('This vehicle has another active booking in the selected Start/End time window.')
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
            return;
        }

        $totalAmount = $this->money($data['sd_total_amount'] ?? 0);

        if ($totalAmount <= 0) {
            Notification::make()
                ->title('Invalid Self Drive fare')
                ->body('Please select vehicle, rental plan and valid Start/End time so fare can be calculated.')
                ->danger()
                ->send();

            $this->halt();
            return;
        }

        $requestType = (string) ($data['payment_request_type'] ?? 'token');
        $requestedAmount = match ($requestType) {
            'full' => $totalAmount,
            'custom' => min(
                $totalAmount,
                $this->money($data['payment_request_amount'] ?? 0)
            ),
            default => min(
                $totalAmount,
                max(0, $this->money($data['payment_request_amount'] ?? 500))
            ),
        };

        $booking = SelfDriveBooking::query()->create([
            'customer_id' => $customerId,
            'vehicle_id' => $vehicleId,
            'pickup_location' => $pickupLocation !== ''
                ? $pickupLocation
                : ((string) ($data['cityFrom'] ?? 'Pickup Location')),
            'start_datetime' => $start,
            'end_datetime' => $end,
            'booked_hours' => max(1, (int) ($data['sd_booked_hours'] ?? 1)),
            'total_days' => max(1, (int) ($data['sd_total_days'] ?? 1)),
            'hourly_price' => max(0, (float) ($vehicle->hourly_price ?? 0)),
            'price_per_day' => max(0, (float) ($vehicle->daily_price ?? 0)),
            'security_deposit' => $this->money(
                $data['sd_security_deposit']
                ?? $vehicle->security_deposit
                ?? 0
            ),
            'minimum_booking_hours' => max(
                1,
                (int) ($vehicle->minimum_booking_hours ?? 1)
            ),
            'total_amount' => $totalAmount,
            'final_amount' => $totalAmount,
            'payment_type' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => (string) ($data['payment_method'] ?? 'cash'),
            'advance_amount' => $requestedAmount,
            'paid_amount' => 0,
            'remaining_amount' => $totalAmount,
            'status' => SelfDriveBooking::STATUS_PENDING,
            'booking_status' => 'pending_vendor_confirmation',
            'vendor_confirmation_status' => 'pending',
            'document_status' => 'not_uploaded',
            'settlement_status' => SelfDriveBooking::SETTLEMENT_PENDING,
        ]);

        Notification::make()
            ->title('Self Drive booking created')
            ->body(
                $booking->booking_no
                . ' created successfully. Payment request: ₹'
                . number_format($requestedAmount, 2)
            )
            ->success()
            ->send();

        $this->redirect(
            SelfDriveBookingResource::getUrl('edit', [
                'record' => $booking,
            ])
        );

        $this->halt();
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->title('Booking created')
            ->body(
                OrderResource::bookingNumber($this->record)
                . ' has been created successfully.'
            )
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back_to_bookings')
                ->label('All Bookings')
                ->icon('heroicon-o-arrow-left')
                ->url(OrderResource::getUrl('index'))
                ->color('gray'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return OrderResource::getUrl('view', [
            'record' => $this->record,
        ]);
    }

    private function resolvePaymentRequestAmount(float $grandTotal): float
    {
        return match ($this->adminBookingMeta['payment_request_type']) {
            'full' => $grandTotal,
            'custom' => min(
                $grandTotal,
                max(
                    0,
                    (float) $this->adminBookingMeta[
                        'payment_request_amount'
                    ]
                )
            ),
            default => min(
                $grandTotal,
                max(
                    0,
                    (float) (
                        $this->adminBookingMeta[
                            'payment_request_amount'
                        ] ?: 500
                    )
                )
            ),
        };
    }

    private function normaliseRideType(mixed $rideType): string
    {
        $rideType = (string) $rideType;

        return match ($rideType) {
            'round_trip' => 'return',
            'one_way', 'local', 'return', 'airport', 'self_drive' =>
                $rideType,
            default => 'one_way',
        };
    }

    private function generateBookingNumber(): string
    {
        return 'DURA'
            . now()->format('ymdHis')
            . strtoupper(Str::random(3));
    }

    private function money(mixed $value): float
    {
        return round(max(0, (float) $value), 2);
    }
}