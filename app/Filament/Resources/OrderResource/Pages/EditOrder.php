<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Mail\OrderUpdated;
use App\Models\Address;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\FinalBillingService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    /**
     * Fill virtual admin-booking fields from extraOptions when editing.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $extraOptions = $this->extraOptions();

        $data['route_stops'] = array_values(array_filter(
            Arr::wrap($extraOptions['route_stops'] ?? []),
            static fn ($stop): bool => filled($stop)
        ));

        $data['fare_type'] = in_array(
            $extraOptions['fare_type'] ?? 'normal',
            ['normal', 'all_inclusive'],
            true
        )
            ? $extraOptions['fare_type']
            : 'normal';

        $data['calculated_amount'] = $this->money(
            $extraOptions['calculated_amount']
                ?? $data['grand_total']
                ?? 0
        );

        $data['fare_override_reason'] = (string) (
            $extraOptions['fare_override_reason']
            ?? ''
        );

        $paymentRequest = is_array(
            $extraOptions['payment_request'] ?? null
        )
            ? $extraOptions['payment_request']
            : [];

        $data['payment_request_type'] = in_array(
            $paymentRequest['type'] ?? 'token',
            ['token', 'full', 'custom'],
            true
        )
            ? $paymentRequest['type']
            : 'token';

        $data['payment_request_amount'] = $this->money(
            $paymentRequest['amount'] ?? 500
        );

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $currentExtraOptions = $this->extraOptions();

        $routeStops = array_values(array_filter(
            Arr::wrap($data['route_stops'] ?? []),
            static fn ($stop): bool => filled($stop)
        ));

        $fareType = in_array(
            $data['fare_type'] ?? 'normal',
            ['normal', 'all_inclusive'],
            true
        )
            ? $data['fare_type']
            : 'normal';

        $calculatedAmount = $this->money(
            $data['calculated_amount']
                ?? $currentExtraOptions['calculated_amount']
                ?? $data['grand_total']
                ?? $this->record->grand_total
                ?? 0
        );

        $manualDiscount = $this->money(
            $data['coupon_value']
                ?? $this->record->coupon_value
                ?? 0
        );

        $requestedFinalAmount = $this->money(
            $data['grand_total']
                ?? max(0, $calculatedAmount - $manualDiscount)
        );

        $paymentRequestType = in_array(
            $data['payment_request_type'] ?? 'token',
            ['token', 'full', 'custom'],
            true
        )
            ? $data['payment_request_type']
            : 'token';

        $paymentRequestAmount = $this->resolvePaymentRequestAmount(
            $paymentRequestType,
            $data['payment_request_amount'] ?? 500,
            $requestedFinalAmount
        );

        $fareOverrideReason = trim(
            (string) ($data['fare_override_reason'] ?? '')
        );

        /*
         * Remove fields that exist only in the Filament form and are not
         * guaranteed to be columns in the orders table.
         */
        unset(
            $data['route_stops'],
            $data['fare_type'],
            $data['calculated_amount'],
            $data['fare_override_reason'],
            $data['payment_request_type'],
            $data['payment_request_amount']
        );

        $billing = app(FinalBillingService::class)->calculate([
            'service_type' => 'with_driver',
            'base_fare' => $calculatedAmount,
            'special_request_total' =>
                $currentExtraOptions['special_request_total'] ?? 0,
            'extra_hour_amount' =>
                $currentExtraOptions['extra_hour_amount'] ?? 0,
            'extra_km_amount' =>
                $currentExtraOptions['extra_km_amount'] ?? 0,
            'toll_amount' =>
                $currentExtraOptions['toll_amount'] ?? 0,
            'parking_amount' =>
                $currentExtraOptions['parking_amount'] ?? 0,
            'government_tax_amount' =>
                $currentExtraOptions['government_tax_amount']
                ?? $currentExtraOptions['tax_amount']
                ?? 0,
            'coupon_discount' => $manualDiscount,
            'payment_method' =>
                $data['payment_method']
                ?? $this->record->payment_method
                ?? 'cash',
            'paid_amount' =>
                $currentExtraOptions['paid_amount']
                ?? (
                    ($data['payment_status']
                        ?? $this->record->payment_status)
                    === 'paid'
                        ? $requestedFinalAmount
                        : 0
                ),
        ]);

        /*
         * Admin-entered final fare remains authoritative. Billing metadata is
         * still refreshed so invoices and payment balances remain consistent.
         */
        $finalAmount = $requestedFinalAmount > 0
            ? $requestedFinalAmount
            : $this->money($billing['grand_total'] ?? 0);

        $extraOptions = array_merge(
            $currentExtraOptions,
            [
                'booking_source' =>
                    $currentExtraOptions['booking_source'] ?? 'admin',
                'last_updated_by_admin_id' => auth()->id(),
                'last_updated_at_from_admin' => now()->toIso8601String(),
                'route_stops' => $routeStops,
                'return_to_pickup' =>
                    ($data['ride_type'] ?? $this->record->ride_type)
                    === 'return',
                'fare_type' => $fareType,
                'all_inclusive' => $fareType === 'all_inclusive',
                'calculated_amount' => $calculatedAmount,
                'manual_discount' => $manualDiscount,
                'fare_override_reason' => $fareOverrideReason !== ''
                    ? $fareOverrideReason
                    : (
                        $currentExtraOptions['fare_override_reason']
                        ?? null
                    ),
                'final_amount' => $finalAmount,
                'payment_request' => array_merge(
                    is_array(
                        $currentExtraOptions['payment_request'] ?? null
                    )
                        ? $currentExtraOptions['payment_request']
                        : [],
                    [
                        'type' => $paymentRequestType,
                        'amount' => $paymentRequestAmount,
                    ]
                ),
                'parking_policy' =>
                    ($data['ride_type'] ?? $this->record->ride_type)
                    === 'return'
                        ? 'pay_direct_as_actual'
                        : (
                            $currentExtraOptions['parking_policy']
                            ?? null
                        ),
                'base_fare' => $billing['base_fare'],
                'special_request_total' =>
                    $billing['special_request_total'],
                'extra_hour_amount' =>
                    $billing['extra_hour_amount'],
                'extra_km_amount' =>
                    $billing['extra_km_amount'],
                'toll_amount' => $billing['toll_amount'],
                'parking_amount' => $billing['parking_amount'],
                'government_tax_amount' =>
                    $billing['government_tax_amount'],
                'taxable_amount' => $billing['taxable_amount'],
                'non_taxable_amount' => $billing['non_taxable_amount'],
                'gst_percent' => $billing['gst_percent'],
                'gst_amount' => $billing['gst_amount'],
                'online_payment_charge_percent' =>
                    $billing['online_payment_charge_percent'],
                'online_payment_charge' =>
                    $billing['online_payment_charge'],
                'coupon_discount' => $billing['coupon_discount'],
                'grand_total' => $finalAmount,
                'paid_amount' => $billing['paid_amount'],
                'remaining_amount' => max(
                    0,
                    $finalAmount - $this->money(
                        $billing['paid_amount'] ?? 0
                    )
                ),
                'refund_amount' => $billing['refund_amount'],
            ]
        );

        $data['grand_total'] = $finalAmount;
        $data['tax'] = $this->money($billing['gst_amount'] ?? 0);
        $data['coupon_value'] = $manualDiscount;

        if (Schema::hasColumn('orders', 'extraOptions')) {
            $data['extraOptions'] = $extraOptions;
        } else {
            unset($data['extraOptions']);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $this->sendStatusEmail();

        Notification::make()
            ->title('Booking updated')
            ->body(
                OrderResource::bookingNumber($this->record)
                . ' has been updated successfully.'
            )
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('View Booking'),

            Actions\Action::make('invoice_preview')
                ->label('Invoice Preview')
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->visible(fn (): bool => Route::has('invoice.preview'))
                ->url(fn (): string => route(
                    'invoice.preview',
                    [
                        'booking' =>
                            OrderResource::bookingNumber($this->record),
                    ]
                ))
                ->openUrlInNewTab(),

            Actions\Action::make('invoice_pdf')
                ->label('Invoice PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->visible(fn (): bool => Route::has('orders.invoice'))
                ->url(fn (): string => route(
                    'orders.invoice',
                    [
                        'booking' =>
                            OrderResource::bookingNumber($this->record),
                    ]
                ))
                ->openUrlInNewTab(),

            Actions\DeleteAction::make(),
        ];
    }

    private function sendStatusEmail(): void
    {
        if (! $this->record->wasChanged('status')) {
            return;
        }

        try {
            $this->record->refresh();
            $this->record->load([
                'user',
                'address',
                'items',
            ]);

            $user = $this->record->user_id
                ? User::query()->find($this->record->user_id)
                : null;

            $driver = $this->record->driver_id
                ? User::query()->find($this->record->driver_id)
                : null;

            $vehicle = $this->record->vehicle_id
                ? Vehicle::query()->find($this->record->vehicle_id)
                : null;

            $addressRecord = $this->record->address
                ?? Address::query()
                    ->where('order_id', $this->record->id)
                    ->first();

            if (! $addressRecord) {
                Log::warning(
                    'Order status email skipped: address not found.',
                    ['order_id' => $this->record->id]
                );

                return;
            }

            $orderData = $this->record->toArray();
            $mailData = [$addressRecord, $orderData];

            if (
                $this->record->status === 'start'
                && $driver
                && $vehicle
            ) {
                $mailData = [
                    $addressRecord,
                    $orderData,
                    $driver,
                    $vehicle,
                ];
            }

            $recipients = collect([
                $user?->email,
                $addressRecord->email,
            ])
                ->filter()
                ->unique()
                ->values();

            foreach ($recipients as $recipient) {
                Mail::to($recipient)->send(
                    new OrderUpdated($mailData)
                );
            }
        } catch (\Throwable $exception) {
            Log::error(
                'Order status email failed.',
                [
                    'order_id' => $this->record->id,
                    'error' => $exception->getMessage(),
                ]
            );
        }
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

    private function resolvePaymentRequestAmount(
        string $type,
        mixed $requestedAmount,
        float $grandTotal
    ): float {
        return match ($type) {
            'full' => $grandTotal,
            'custom' => min(
                $grandTotal,
                $this->money($requestedAmount)
            ),
            default => min(
                $grandTotal,
                max(0, $this->money($requestedAmount ?: 500))
            ),
        };
    }

    private function money(mixed $value): float
    {
        return round(max(0, (float) $value), 2);
    }
}