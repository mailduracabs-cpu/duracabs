<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Filament\Resources\SelfDriveBookingResource;
use App\Models\SelfDriveBooking;
use App\Models\CustomerSearchActivity;
use App\Models\Brand;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
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

    /**
     * Customer lead that opened this booking form. Public so Livewire keeps it
     * across validation / save requests.
     */
    public ?int $leadId = null;

    public function mount(): void
    {
        parent::mount();

        $leadId = (int) request()->query('lead', 0);

        if ($leadId <= 0) {
            return;
        }

        $lead = CustomerSearchActivity::query()->find($leadId);

        if (! $lead) {
            Notification::make()
                ->title('Customer lead not found')
                ->body('The booking form was opened without lead prefill.')
                ->warning()
                ->send();

            return;
        }

        if ($lead->is_converted) {
            Notification::make()
                ->title('Lead already converted')
                ->body($lead->booking_number
                    ? 'Booking: ' . $lead->booking_number
                    : 'This customer lead is already converted.')
                ->warning()
                ->send();

            return;
        }

        $this->leadId = (int) $lead->id;

        $this->form->fill(array_merge(
            is_array($this->data ?? null) ? $this->data : [],
            $this->leadPrefillData($lead)
        ));
    }

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
                'customer_lead_id' => $this->leadId,
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

        $this->markLeadConverted(
            bookingType: 'self_drive',
            bookingId: (int) $booking->id,
            bookingNumber: (string) ($booking->booking_no ?? ''),
            grandTotal: $totalAmount
        );

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
        $this->markLeadConverted(
            bookingType: (string) ($this->record->ride_type ?? 'booking'),
            bookingId: (int) $this->record->id,
            bookingNumber: OrderResource::bookingNumber($this->record),
            grandTotal: $this->money($this->record->grand_total ?? 0)
        );

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
            Actions\Action::make('back_to_lead')
                ->label('Back to Lead')
                ->icon('heroicon-o-user')
                ->url(fn (): ?string => $this->leadId
                    ? \App\Filament\Resources\CustomerLeadResource::getUrl('view', [
                        'record' => $this->leadId,
                    ])
                    : null)
                ->visible(fn (): bool => filled($this->leadId))
                ->color('gray'),

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

    /**
     * Build the safest possible booking-form prefill from a customer enquiry.
     * Fields that depend on database pricing/packages remain editable so admin
     * can verify them with the customer before creating the booking.
     *
     * @return array<string, mixed>
     */
    private function leadPrefillData(CustomerSearchActivity $lead): array
    {
        $rideType = $this->leadRideType($lead->service_type);
        $start = $lead->start_datetime;
        $end = $rideType === 'return'
            ? ($lead->return_datetime ?: $lead->end_datetime)
            : $lead->end_datetime;

        $amount = $this->money(
            $lead->grand_total
            ?? $lead->estimated_amount
            ?? $lead->minimum_result_price
            ?? 0
        );

        $userId = $this->resolveLeadUserId($lead);
        $pickupBrandId = $this->resolveBrandId(
            $lead->pickup_city ?: $lead->pickup_location
        );
        $dropBrandId = $this->resolveBrandId(
            $lead->drop_city ?: $lead->drop_location
        );

        $prefill = [
            'user_id' => $userId,
            'ride_type' => $rideType,
            'pickup_brand_id' => $pickupBrandId,
            'drop_brand_id' => $dropBrandId,
            'cityFrom' => $lead->pickup_city ?: $lead->pickup_location,
            'cityTo' => $lead->drop_city ?: $lead->drop_location,
            'date' => $start?->format('Y-m-d'),
            'time' => $start?->format('H:i'),
            'dateTo' => $end?->format('Y-m-d'),
            'endTime' => $end?->format('H:i'),
            'route_stops' => $this->leadStops($lead),
            'grand_total' => $amount,
            'calculated_amount' => $amount,
            'coupon_value' => 0,
            'fare_type' => $lead->is_all_inclusive
                ? 'all_inclusive'
                : 'normal',
            'currency' => strtoupper((string) ($lead->currency ?: 'INR')),
            'payment_status' => 'pending',
            'payment_request_type' => 'token',
            'payment_request_amount' => min(500, $amount),
            'notes' => trim(implode("\n", array_filter([
                $lead->lead_notes,
                'Created from Customer Lead #' . $lead->id,
            ]))),
        ];

        if ($rideType === 'self_drive') {
            $hours = 0;
            $days = 0;

            if ($start && $end && $end->gt($start)) {
                $hours = max(1, (int) ceil($start->diffInMinutes($end) / 60));
                $days = max(1, (int) ceil($hours / 24));
            }

            $vehicle = $lead->vehicle_id
                ? Vehicle::query()->find($lead->vehicle_id)
                : null;

            $plan = in_array(
                (string) $lead->plan_type,
                ['hourly', 'daily', 'weekly', 'monthly'],
                true
            )
                ? (string) $lead->plan_type
                : 'daily';

            $prefill = array_merge($prefill, [
                'sd_pickup_location' =>
                    $lead->pickup_location ?: $lead->pickup_city,
                'sd_vehicle_id' => $vehicle?->id,
                'sd_rental_plan' => $plan,
                'sd_booked_hours' => $hours,
                'sd_total_days' => $days,
                'sd_rate' => $this->leadSelfDriveRate($lead, $vehicle, $plan),
                'sd_security_deposit' => $this->money(
                    $lead->security_deposit
                    ?? $vehicle?->security_deposit
                    ?? 0
                ),
                'sd_calculated_amount' => $amount,
                'sd_total_amount' => $amount,
            ]);
        }

        return array_filter(
            $prefill,
            static fn (mixed $value): bool => $value !== null
        );
    }

    private function leadRideType(?string $serviceType): string
    {
        return match ((string) $serviceType) {
            CustomerSearchActivity::SERVICE_ROUND_TRIP,
            CustomerSearchActivity::SERVICE_TOUR => 'return',
            CustomerSearchActivity::SERVICE_LOCAL => 'local',
            CustomerSearchActivity::SERVICE_AIRPORT => 'airport',
            CustomerSearchActivity::SERVICE_SELF_DRIVE => 'self_drive',
            default => 'one_way',
        };
    }

    private function resolveLeadUserId(CustomerSearchActivity $lead): ?int
    {
        if ($lead->user_id && User::query()->whereKey($lead->user_id)->exists()) {
            return (int) $lead->user_id;
        }

        $mobile = CustomerSearchActivity::normalizeMobile($lead->mobile);

        if (blank($mobile)) {
            return null;
        }

        return User::query()
            ->where('mobile', $mobile)
            ->value('id');
    }

    private function resolveBrandId(?string $location): ?int
    {
        $location = trim((string) $location);

        if ($location === '') {
            return null;
        }

        $city = trim((string) Str::before($location, ','));

        return Brand::query()
            ->where(function ($query) use ($location, $city): void {
                $query->where('name', $location)
                    ->orWhere('name', $city)
                    ->orWhere('name', 'like', $city . ',%')
                    ->orWhere('name', 'like', $city . ' %');
            })
            ->value('id');
    }

    /** @return array<int, string> */
    private function leadStops(CustomerSearchActivity $lead): array
    {
        return collect(Arr::wrap($lead->via_locations))
            ->map(function (mixed $stop): ?string {
                if (is_string($stop)) {
                    return trim($stop) ?: null;
                }

                if (is_array($stop)) {
                    return trim((string) (
                        $stop['city']
                        ?? $stop['name']
                        ?? $stop['location']
                        ?? ''
                    )) ?: null;
                }

                return null;
            })
            ->filter()
            ->values()
            ->all();
    }

    private function leadSelfDriveRate(
        CustomerSearchActivity $lead,
        ?Vehicle $vehicle,
        string $plan
    ): float {
        $rate = match ($plan) {
            'hourly' => $lead->price_per_hour ?? $vehicle?->hourly_price,
            'weekly' => $lead->price_per_week,
            'monthly' => $lead->price_per_month,
            default => $lead->price_per_day ?? $vehicle?->daily_price,
        };

        return $this->money($rate ?? 0);
    }

    private function markLeadConverted(
        string $bookingType,
        int $bookingId,
        string $bookingNumber,
        float $grandTotal
    ): void {
        if (! $this->leadId) {
            return;
        }

        try {
            $lead = CustomerSearchActivity::query()->find($this->leadId);

            if (! $lead || $lead->is_converted) {
                return;
            }

            $lead->markConverted(
                bookingType: $bookingType,
                bookingId: $bookingId,
                bookingNumber: $bookingNumber !== '' ? $bookingNumber : null,
                grandTotal: $grandTotal
            );
        } catch (\Throwable $exception) {
            Log::error('Customer lead conversion update failed.', [
                'lead_id' => $this->leadId,
                'booking_type' => $bookingType,
                'booking_id' => $bookingId,
                'error' => $exception->getMessage(),
            ]);
        }
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