<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
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
                ? $data['fare_type']
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
                ? $data['payment_request_type']
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
            $data['payment_request_amount']
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
            ? $data['payment_status']
            : 'pending';

        $data['status'] = in_array(
            $data['status'] ?? 'new',
            array_keys(OrderResource::statusOptions()),
            true
        )
            ? $data['status']
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