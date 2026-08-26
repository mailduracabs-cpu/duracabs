<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Jobs\SendReviewRequestWhatsApp;
use App\Mail\OrderUpdated;
use App\Models\Address;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\FinalBillingService;
use App\Services\WhatsAppService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    /**
     * Fill virtual admin-booking fields from extraOptions when editing.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $extraOptions = $this->extraOptions();

        /*
         * Restore virtual select fields used by OrderResource.
         * These fields are dehydrated(false), so their IDs are not stored
         * in the orders table. Rebuild them from the actual saved values.
         */
        $cityFrom = trim((string) ($data['cityFrom'] ?? ''));
        $cityTo = trim((string) ($data['cityTo'] ?? ''));
        $productName = trim((string) ($data['productName'] ?? ''));
        $taxiType = trim((string) ($data['taxi_type'] ?? ''));
        $rideType = (string) ($data['ride_type'] ?? 'one_way');

        $pickupBrand = null;

        if ($cityFrom !== '') {
            $pickupBrand = \App\Models\Brand::query()
                ->where('name', $cityFrom)
                ->first();

            if (! $pickupBrand) {
                $shortCity = trim(explode(',', $cityFrom)[0] ?? '');

                if ($shortCity !== '') {
                    $pickupBrand = \App\Models\Brand::query()
                        ->where(function ($query) use ($shortCity) {
                            $query
                                ->where('name', $shortCity)
                                ->orWhere('name', 'like', $shortCity . ',%');
                        })
                        ->first();
                }
            }
        }

        $data['pickup_brand_id'] = $pickupBrand?->id;

        $dropBrand = null;

        if ($cityTo !== '') {
            $dropBrand = \App\Models\Brand::query()
                ->where('name', $cityTo)
                ->first();

            if (! $dropBrand) {
                $shortCityTo = trim(explode(',', $cityTo)[0] ?? '');

                if ($shortCityTo !== '') {
                    $dropBrand = \App\Models\Brand::query()
                        ->where(function ($query) use ($shortCityTo) {
                            $query
                                ->where('name', $shortCityTo)
                                ->orWhere('name', 'like', $shortCityTo . ',%');
                        })
                        ->first();
                }
            }
        }

        $data['drop_brand_id'] = $dropBrand?->id;

        $product = null;

        if ($productName !== '') {
            $rideTypes = $rideType === 'return'
                ? ['return', 'round_trip']
                : [$rideType];

            $product = \App\Models\Product::query()
                ->when(
                    $pickupBrand,
                    fn ($query) => $query->where('brand_id', $pickupBrand->id)
                )
                ->whereIn('ride_type', $rideTypes)
                ->where('name', $productName)
                ->first();

            if (! $product) {
                $product = \App\Models\Product::query()
                    ->where('name', $productName)
                    ->first();
            }
        }

        $data['selected_product_id'] = $product?->id;

        $price = null;

        if ($product && $taxiType !== '') {
            $price = \App\Models\Price::query()
                ->with('category')
                ->where('product_id', $product->id)
                ->whereHas('category', function ($query) use ($taxiType) {
                    $query->where('slug', $taxiType);
                })
                ->orderBy('price')
                ->first();
        }

        if (! $price && $product) {
            $productPrices = \App\Models\Price::query()
                ->with('category')
                ->where('product_id', $product->id)
                ->orderBy('price')
                ->get();

            if ($productPrices->count() === 1) {
                $price = $productPrices->first();
            }
        }

        $data['selected_price_id'] = $price?->id;

        $data['route_stops'] = array_values(array_filter(
            Arr::wrap($extraOptions['route_stops'] ?? []),
            static fn ($stop): bool => filled($stop)
        ));

        $fareType = $extraOptions['fare_type'] ?? 'normal';

        $data['fare_type'] = in_array(
            $fareType,
            ['normal', 'all_inclusive'],
            true
        )
            ? $fareType
            : 'normal';

        $storedCalculatedAmount = $this->money(
            $extraOptions['calculated_amount'] ?? 0
        );

        $data['calculated_amount'] = $storedCalculatedAmount > 0
            ? $storedCalculatedAmount
            : $this->money(
                $price?->price
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

        $requestedFareType = $data['fare_type']
            ?? $currentExtraOptions['fare_type']
            ?? 'normal';

        $fareType = in_array(
            $requestedFareType,
            ['normal', 'all_inclusive'],
            true
        )
            ? $requestedFareType
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
        $this->sendWhatsAppUpdates();

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

    private function sendWhatsAppUpdates(): void
    {
        /*
         * Capture dirty-change information before refresh. Refreshing the model
         * first would clear wasChanged() and could suppress notifications.
         */
        $statusChanged = $this->record->wasChanged('status');
        $driverChanged = $this->record->wasChanged('driver_id');
        $vehicleChanged = $this->record->wasChanged('vehicle_id');
        $paymentStatusChanged = $this->record->wasChanged('payment_status');
        $extraOptionsChanged = $this->record->wasChanged('extraOptions');
        $currentStatus = (string) $this->record->status;
        $currentPaymentStatus = strtolower(trim((string) (
            $this->record->payment_status ?? 'pending'
        )));

        try {
            $this->record->loadMissing([
                'user',
                'address',
                'items.product',
            ]);

            $customerMobile = $this->customerMobile();

            if ($customerMobile === '') {
                Log::warning(
                    'Order WhatsApp update skipped because customer mobile is missing.',
                    ['order_id' => $this->record->id]
                );

                return;
            }

            if ($statusChanged && $currentStatus === 'confirm') {
                $this->sendBookingConfirmationWhatsApp(
                    $customerMobile
                );
            }

            if ($driverChanged || $vehicleChanged) {
                $this->sendDriverAssignedWhatsApp(
                    $customerMobile
                );
            }

            if ($statusChanged && $currentStatus === 'start') {
                $this->sendTripStartedWhatsApp(
                    $customerMobile
                );
            }

            if ($statusChanged && $currentStatus === 'closed') {
                $this->sendTripCompletedWhatsApp(
                    $customerMobile
                );

                $this->scheduleReviewRequestWhatsApp();
            }

            if ($statusChanged && $currentStatus === 'refund') {
                $this->sendRefundProcessedWhatsApp(
                    $customerMobile
                );
            }

            if (
                $currentPaymentStatus === 'pending'
                && ($paymentStatusChanged || $extraOptionsChanged)
                && $this->remainingPaymentAmount() > 0
            ) {
                $this->sendPaymentReminderWhatsApp(
                    $customerMobile
                );
            }

            /*
             * Send invoice once when the trip is newly completed or payment
             * becomes paid. If both change in one save, this single condition
             * still sends only one invoice message.
             */
            if (
                ($statusChanged && $currentStatus === 'closed')
                || (
                    $paymentStatusChanged
                    && in_array(
                        $currentPaymentStatus,
                        ['paid', 'success', 'successful', 'captured'],
                        true
                    )
                )
            ) {
                $this->sendInvoiceReadyWhatsApp(
                    $customerMobile
                );
            }
        } catch (\Throwable $exception) {
            Log::error(
                'Order WhatsApp update failed.',
                [
                    'order_id' => $this->record->id,
                    'error' => $exception->getMessage(),
                ]
            );
        }
    }

    private function sendBookingConfirmationWhatsApp(
        string $customerMobile
    ): void {
        try {
            $sent = WhatsAppService::bookingConfirmation(
                $customerMobile,
                [
                    'customer_name' => $this->customerName(),
                    'booking_id' => OrderResource::bookingNumber(
                        $this->record
                    ),
                    'pickup' => $this->pickupLabel(),
                    'drop' => $this->dropLabel(),
                    'date' => $this->travelDateLabel(),
                    'time' => $this->travelTimeLabel(),
                    'amount' => number_format(
                        $this->money($this->record->grand_total),
                        2,
                        '.',
                        ''
                    ),
                ]
            );

            if (! $sent) {
                Log::warning(
                    'Booking confirmation WhatsApp was not accepted.',
                    ['order_id' => $this->record->id]
                );
            }
        } catch (\Throwable $exception) {
            Log::error(
                'Booking confirmation WhatsApp failed.',
                [
                    'order_id' => $this->record->id,
                    'error' => $exception->getMessage(),
                ]
            );
        }
    }

    private function sendDriverAssignedWhatsApp(
        string $customerMobile
    ): void {
        $driver = $this->driverRecord();
        $vehicle = $this->vehicleRecord();

        if (! $driver || ! $vehicle) {
            Log::info(
                'Driver assigned WhatsApp skipped until both driver and vehicle are selected.',
                [
                    'order_id' => $this->record->id,
                    'driver_id' => $this->record->driver_id,
                    'vehicle_id' => $this->record->vehicle_id,
                ]
            );

            return;
        }

        $driverMobile = trim((string) (
            $driver->mobile
            ?? $driver->phone
            ?? ''
        ));

        try {
            $result = WhatsAppService::sendTemplate(
                number: $customerMobile,
                templateName: (string) config(
                    'services.whatsapp.templates.driver_assigned',
                    'driver_assigned_v1'
                ),
                languageCode: (string) config(
                    'services.whatsapp.default_language',
                    'en'
                ),
                bodyParameters: [
                    $this->customerName(),
                    OrderResource::bookingNumber($this->record),
                    trim((string) ($driver->name ?: 'Driver')),
                    $driverMobile !== '' ? $driverMobile : 'N/A',
                    $this->vehicleName($vehicle),
                    $this->vehicleNumber($vehicle),
                    $this->travelTimeLabel(),
                ]
            );

            if (! (bool) (
                $result['status']
                ?? $result['success']
                ?? false
            )) {
                Log::warning(
                    'Driver assigned WhatsApp was not accepted.',
                    [
                        'order_id' => $this->record->id,
                        'result' => $result,
                    ]
                );
            }
        } catch (\Throwable $exception) {
            Log::error(
                'Driver assigned WhatsApp failed.',
                [
                    'order_id' => $this->record->id,
                    'error' => $exception->getMessage(),
                ]
            );
        }
    }

    private function sendTripStartedWhatsApp(
        string $customerMobile
    ): void {
        $driver = $this->driverRecord();
        $vehicle = $this->vehicleRecord();

        try {
            $sent = WhatsAppService::tripStarted(
                $customerMobile,
                [
                    'customer_name' => $this->customerName(),
                    'booking_id' => OrderResource::bookingNumber(
                        $this->record
                    ),
                    'route' => $this->routeLabel(),
                    'driver_name' => trim((string) (
                        $driver?->name ?: 'Driver'
                    )),
                    'vehicle_name' => $vehicle
                        ? $this->vehicleName($vehicle)
                        : trim((string) (
                            $this->record->productName
                            ?: 'Assigned Vehicle'
                        )),
                ]
            );

            if (! $sent) {
                Log::warning(
                    'Trip started WhatsApp was not accepted.',
                    ['order_id' => $this->record->id]
                );
            }
        } catch (\Throwable $exception) {
            Log::error(
                'Trip started WhatsApp failed.',
                [
                    'order_id' => $this->record->id,
                    'error' => $exception->getMessage(),
                ]
            );
        }
    }

    private function sendTripCompletedWhatsApp(
        string $customerMobile
    ): void {
        try {
            $sent = WhatsAppService::tripCompleted(
                $customerMobile,
                [
                    'customer_name' => $this->customerName(),
                    'booking_id' => OrderResource::bookingNumber(
                        $this->record
                    ),
                    'route' => $this->routeLabel(),
                    'total_amount' => number_format(
                        $this->money($this->record->grand_total),
                        2,
                        '.',
                        ''
                    ),
                    'payment_status' => $this->paymentStatusLabel(),
                ]
            );

            if (! $sent) {
                Log::warning(
                    'Trip completed WhatsApp was not accepted.',
                    ['order_id' => $this->record->id]
                );
            }
        } catch (\Throwable $exception) {
            Log::error(
                'Trip completed WhatsApp failed.',
                [
                    'order_id' => $this->record->id,
                    'error' => $exception->getMessage(),
                ]
            );
        }
    }

    private function scheduleReviewRequestWhatsApp(): void
    {
        try {
            SendReviewRequestWhatsApp::dispatch(
                (int) $this->record->id
            )->delay(
                now()->addHours(
                    max(
                        1,
                        (int) config(
                            'services.whatsapp.review_request_delay_hours',
                            2
                        )
                    )
                )
            );

            Log::info(
                'Review request WhatsApp scheduled.',
                [
                    'order_id' => $this->record->id,
                    'delay_hours' => max(
                        1,
                        (int) config(
                            'services.whatsapp.review_request_delay_hours',
                            2
                        )
                    ),
                ]
            );
        } catch (\Throwable $exception) {
            Log::error(
                'Review request WhatsApp scheduling failed.',
                [
                    'order_id' => $this->record->id,
                    'error' => $exception->getMessage(),
                ]
            );
        }
    }

    private function sendInvoiceReadyWhatsApp(
        string $customerMobile
    ): void {
        if (! Route::has('invoice.shared')) {
            Log::warning(
                'Invoice WhatsApp skipped because invoice.shared route is missing.',
                ['order_id' => $this->record->id]
            );

            return;
        }

        try {
            $invoiceLink = URL::temporarySignedRoute(
                'invoice.shared',
                now()->addDays(
                    max(
                        1,
                        (int) config(
                            'services.whatsapp.invoice_link_days',
                            30
                        )
                    )
                ),
                [
                    'booking' => OrderResource::bookingNumber(
                        $this->record
                    ),
                ]
            );

            $result = WhatsAppService::sendTemplate(
                number: $customerMobile,
                templateName: (string) config(
                    'services.whatsapp.templates.invoice_ready',
                    'invoice_ready_v1'
                ),
                languageCode: (string) config(
                    'services.whatsapp.default_language',
                    'en'
                ),
                bodyParameters: [
                    $this->customerName(),
                    OrderResource::bookingNumber($this->record),
                    $invoiceLink,
                ]
            );

            if (! (bool) (
                $result['status']
                ?? $result['success']
                ?? false
            )) {
                Log::warning(
                    'Invoice ready WhatsApp was not accepted.',
                    [
                        'order_id' => $this->record->id,
                        'result' => $result,
                    ]
                );
            }
        } catch (\Throwable $exception) {
            Log::error(
                'Invoice ready WhatsApp failed.',
                [
                    'order_id' => $this->record->id,
                    'error' => $exception->getMessage(),
                ]
            );
        }
    }

    private function sendPaymentReminderWhatsApp(
        string $customerMobile
    ): void {
        $remainingAmount = $this->remainingPaymentAmount();

        if ($remainingAmount <= 0) {
            return;
        }

        try {
            $result = WhatsAppService::sendTemplate(
                number: $customerMobile,
                templateName: (string) config(
                    'services.whatsapp.templates.payment_reminder',
                    'payment_reminder_v1'
                ),
                languageCode: (string) config(
                    'services.whatsapp.default_language',
                    'en'
                ),
                bodyParameters: [
                    $this->customerName(),
                    OrderResource::bookingNumber($this->record),
                    number_format(
                        $remainingAmount,
                        2,
                        '.',
                        ''
                    ),
                    $this->paymentLink(),
                ]
            );

            if (! (bool) (
                $result['status']
                ?? $result['success']
                ?? false
            )) {
                Log::warning(
                    'Payment reminder WhatsApp was not accepted.',
                    [
                        'order_id' => $this->record->id,
                        'result' => $result,
                    ]
                );
            }
        } catch (\Throwable $exception) {
            Log::error(
                'Payment reminder WhatsApp failed.',
                [
                    'order_id' => $this->record->id,
                    'error' => $exception->getMessage(),
                ]
            );
        }
    }

    private function remainingPaymentAmount(): float
    {
        $extraOptions = $this->extraOptions();

        $remaining = $extraOptions['remaining_amount'] ?? null;

        if ($remaining !== null && $remaining !== '') {
            return $this->money($remaining);
        }

        $paidAmount = $this->money(
            $extraOptions['paid_amount'] ?? 0
        );

        return max(
            0,
            $this->money($this->record->grand_total) - $paidAmount
        );
    }

    private function paymentLink(): string
    {
        $bookingNumber = OrderResource::bookingNumber(
            $this->record
        );

        if (Route::has('razorpay')) {
            return route('razorpay', [
                'id' => $this->record->id,
            ]);
        }

        $baseUrl = rtrim(
            (string) config(
                'app.frontend_url',
                config('app.url')
            ),
            '/'
        );

        return $baseUrl . '/pay/' . rawurlencode($bookingNumber);
    }

    private function sendRefundProcessedWhatsApp(
        string $customerMobile
    ): void {
        $extraOptions = $this->extraOptions();

        $refundAmount = $this->money(
            $extraOptions['refund_amount']
            ?? $this->record->refund_amount
            ?? $this->record->grand_total
            ?? 0
        );

        $refundStatus = trim((string) (
            $extraOptions['refund_status']
            ?? $this->record->refund_status
            ?? 'Processed'
        )) ?: 'Processed';

        try {
            $result = WhatsAppService::sendTemplate(
                number: $customerMobile,
                templateName: (string) config(
                    'services.whatsapp.templates.refund_processed',
                    'refund_processed_v1'
                ),
                languageCode: (string) config(
                    'services.whatsapp.default_language',
                    'en'
                ),
                bodyParameters: [
                    $this->customerName(),
                    OrderResource::bookingNumber($this->record),
                    number_format(
                        $refundAmount,
                        2,
                        '.',
                        ''
                    ),
                    $refundStatus,
                ]
            );

            if (! (bool) (
                $result['status']
                ?? $result['success']
                ?? false
            )) {
                Log::warning(
                    'Refund processed WhatsApp was not accepted.',
                    [
                        'order_id' => $this->record->id,
                        'result' => $result,
                    ]
                );
            }
        } catch (\Throwable $exception) {
            Log::error(
                'Refund processed WhatsApp failed.',
                [
                    'order_id' => $this->record->id,
                    'error' => $exception->getMessage(),
                ]
            );
        }
    }

    private function driverRecord(): ?User
    {
        return $this->record->driver_id
            ? User::query()->find($this->record->driver_id)
            : null;
    }

    private function vehicleRecord(): ?Vehicle
    {
        return $this->record->vehicle_id
            ? Vehicle::query()->find($this->record->vehicle_id)
            : null;
    }

    private function vehicleName(Vehicle $vehicle): string
    {
        $name = trim(
            (string) ($vehicle->car_company_name ?? '')
            . ' '
            . (string) ($vehicle->model_name ?? '')
        );

        return $name !== ''
            ? $name
            : trim((string) (
                $this->record->productName
                ?: 'Assigned Vehicle'
            ));
    }

    private function vehicleNumber(Vehicle $vehicle): string
    {
        return trim((string) (
            $vehicle->vehicle_number
            ?? $this->record->vehicle_number
            ?? 'N/A'
        ));
    }

    private function customerName(): string
    {
        return trim((string) (
            $this->record->address?->full_name
            ?: $this->record->user?->name
            ?: 'Customer'
        ));
    }

    private function customerMobile(): string
    {
        return trim((string) (
            $this->record->address?->phone
            ?: $this->record->user?->mobile
            ?: ''
        ));
    }

    private function pickupLabel(): string
    {
        return trim((string) (
            $this->record->cityFrom
            ?: $this->record->booking_from
            ?: 'N/A'
        ));
    }

    private function dropLabel(): string
    {
        return trim((string) (
            $this->record->cityTo
            ?: $this->record->booking_to
            ?: $this->record->cityFrom
            ?: 'N/A'
        ));
    }

    private function routeLabel(): string
    {
        $pickup = $this->pickupLabel();
        $drop = $this->dropLabel();

        if (
            $pickup !== 'N/A'
            && $drop !== 'N/A'
            && strcasecmp($pickup, $drop) !== 0
        ) {
            return $pickup . ' to ' . $drop;
        }

        return $pickup !== 'N/A'
            ? $pickup
            : $drop;
    }

    private function travelDateLabel(): string
    {
        if (blank($this->record->date)) {
            return 'N/A';
        }

        try {
            return Carbon::parse(
                $this->record->date
            )->format('d M Y');
        } catch (\Throwable) {
            return trim((string) $this->record->date);
        }
    }

    private function travelTimeLabel(): string
    {
        if (blank($this->record->time)) {
            return 'N/A';
        }

        try {
            return Carbon::parse(
                $this->record->time
            )->format('h:i A');
        } catch (\Throwable) {
            return trim((string) $this->record->time);
        }
    }

    private function paymentStatusLabel(): string
    {
        $status = strtolower(trim((string) (
            $this->record->payment_status ?? 'pending'
        )));

        return match ($status) {
            'paid', 'success', 'successful', 'captured' => 'Paid',
            'failed' => 'Failed',
            'refunded' => 'Refunded',
            default => 'Pending',
        };
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