<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Mail\OrderUpdated;
use App\Models\address;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\FinalBillingService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $extraOptions = is_array($this->record->extraOptions)
            ? $this->record->extraOptions
            : [];

        $billing = app(FinalBillingService::class)->calculate([
            'service_type' => 'with_driver',
            'base_fare' => $extraOptions['base_fare']
                ?? $this->record->items()->sum('total_ammount')
                ?? 0,
            'special_request_total' =>
                $extraOptions['special_request_total'] ?? 0,
            'extra_hour_amount' =>
                $extraOptions['extra_hour_amount'] ?? 0,
            'extra_km_amount' =>
                $extraOptions['extra_km_amount'] ?? 0,
            'toll_amount' =>
                $extraOptions['toll_amount'] ?? 0,
            'parking_amount' =>
                $extraOptions['parking_amount'] ?? 0,
            'government_tax_amount' =>
                $extraOptions['government_tax_amount']
                ?? $extraOptions['tax_amount']
                ?? 0,
            'coupon_discount' =>
                $data['coupon_value']
                ?? $this->record->coupon_value
                ?? 0,
            'payment_method' =>
                $data['payment_method']
                ?? $this->record->payment_method
                ?? 'cash',
            'paid_amount' =>
                $extraOptions['paid_amount']
                ?? (
                    ($data['payment_status']
                        ?? $this->record->payment_status)
                    === 'paid'
                        ? ($data['grand_total']
                            ?? $this->record->grand_total
                            ?? 0)
                        : 0
                ),
        ]);

        $extraOptions = array_merge(
            $extraOptions,
            [
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
                'taxable_amount' =>
                    $billing['taxable_amount'],
                'non_taxable_amount' =>
                    $billing['non_taxable_amount'],
                'gst_percent' => $billing['gst_percent'],
                'gst_amount' => $billing['gst_amount'],
                'online_payment_charge_percent' =>
                    $billing['online_payment_charge_percent'],
                'online_payment_charge' =>
                    $billing['online_payment_charge'],
                'coupon_discount' =>
                    $billing['coupon_discount'],
                'grand_total' =>
                    $billing['grand_total'],
                'paid_amount' =>
                    $billing['paid_amount'],
                'remaining_amount' =>
                    $billing['remaining_amount'],
                'refund_amount' =>
                    $billing['refund_amount'],
            ]
        );

        $data['grand_total'] = $billing['grand_total'];
        $data['tax'] = $billing['gst_amount'];
        $data['extraOptions'] = $extraOptions;

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
                ->visible(
                    fn (): bool => Route::has('invoice.preview')
                )
                ->url(
                    fn (): string => route(
                        'invoice.preview',
                        [
                            'booking' => OrderResource::bookingNumber(
                                $this->record
                            ),
                        ]
                    )
                )
                ->openUrlInNewTab(),

            Actions\Action::make('invoice_pdf')
                ->label('Invoice PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->visible(
                    fn (): bool => Route::has('orders.invoice')
                )
                ->url(
                    fn (): string => route(
                        'orders.invoice',
                        [
                            'booking' => OrderResource::bookingNumber(
                                $this->record
                            ),
                        ]
                    )
                )
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
                ?? address::query()
                    ->where('order_id', $this->record->id)
                    ->first();

            if (! $addressRecord) {
                Log::warning(
                    'Order status email skipped: address not found.',
                    [
                        'order_id' => $this->record->id,
                    ]
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
        } catch (\Throwable $e) {
            Log::error(
                'Order status email failed.',
                [
                    'order_id' => $this->record->id,
                    'error' => $e->getMessage(),
                ]
            );
        }
    }
}