<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Stripe\Checkout\Session;
use Stripe\Stripe;

#[Title('Success - Duracabs')]
class SuccessPage extends Component
{
    #[Url]
    public $session_id;

    #[Url(history: true)]
    public $id;

    public int $days = 0;

    public function createInvoice()
    {
        $order = $this->getSafeOrder();

        abort_unless($order, 404, 'Booking not found.');

        return redirect()->route(
            'orders.invoice',
            [
                'booking' => filled($order->booking_no ?? null)
                    ? (string) $order->booking_no
                    : (string) $order->id,
            ]
        );
    }

    public function render()
    {
        $latestOrder = $this->getSafeOrder();

        if (! $latestOrder) {
            abort(404, 'Booking not found.');
        }

        if ((float) ($latestOrder->grand_total ?? 0) <= 0) {
            abort(403, 'Invalid booking amount.');
        }

        if ($latestOrder->payment_method === 'RazorPay') {
            if (! in_array($latestOrder->payment_status, ['paid', 'partial'], true)
                || $latestOrder->status !== 'confirmed') {
                return redirect()->route('cancel');
            }
        }

        if ($this->session_id) {
            $stripeResult = $this->confirmStripePayment($latestOrder);

            if ($stripeResult !== null) {
                return $stripeResult;
            }
        }

        return view('livewire.success-page', $this->buildViewData($latestOrder));
    }

    private function buildViewData(Order $order): array
    {
        $isSelfDrive = $order->ride_type === 'self_drive';
        $extraOptions = $this->normaliseExtraOptions($order->extraOptions ?? []);
        $pricingBreakdown = $this->normaliseArray(
            Arr::get($extraOptions, 'pricing_breakdown', [])
        );

        $selectedOptions = Arr::get($extraOptions, 'selected_options');

        // Backward compatibility for orders created before the structured
        // extraOptions payload was introduced.
        if (! is_array($selectedOptions)) {
            $selectedOptions = $this->legacySelectedOptions($extraOptions);
        }

        $category = null;

        $orderItems = $order->items;
        $product = optional($orderItems->first())->product;

        /*
         * Taxi category pricing is authoritative for one-way category charges.
         * Resolve the exact selected category from the booking snapshot first,
         * then fall back to orders.taxi_type and finally the Product category.
         */
        if (! $isSelfDrive) {
            $bookingDraft = $this->normaliseArray(
                Arr::get($extraOptions, 'booking_draft', [])
            );

            $snapshotCategoryId = Arr::get($bookingDraft, 'product.category_id')
                ?? Arr::get($bookingDraft, 'fare.category_id')
                ?? Arr::get($pricingBreakdown, 'category.id');

            $snapshotCategoryName = trim((string) (
                Arr::get($bookingDraft, 'product.category_name')
                ?? $order->taxi_type
                ?? ''
            ));

            $categoryQuery = Category::query()
                ->select([
                    'id',
                    'name',
                    'driver_charge',
                    'km_charge',
                    'extra_km_charge',
                    'extra_hr_charge',
                    'pet_friendly_charge',
                    'roof_carrier_charge',
                    'extra_pickup_charge',
                    'extra_drop_charge',
                ])
                ->where('is_active', 1);

            if (is_numeric($snapshotCategoryId) && (int) $snapshotCategoryId > 0) {
                $category = (clone $categoryQuery)
                    ->whereKey((int) $snapshotCategoryId)
                    ->first();
            }

            if (! $category && $snapshotCategoryName !== '') {
                $category = (clone $categoryQuery)
                    ->where('name', $snapshotCategoryName)
                    ->first();
            }

            if (! $category && $product && ! empty($product->category_id)) {
                $category = (clone $categoryQuery)
                    ->whereKey((int) $product->category_id)
                    ->first();
            }
        }

        if (! $product) {
            $product = (object) [
                'ride_type' => $order->ride_type,
                'name' => $order->productName,
                'km_limit' => 0,
                'hr_limit' => 0,
            ];
        }

        $bookingDraft = $this->normaliseArray(
            Arr::get($extraOptions, 'booking_draft', [])
        );
        $draftTrip = $this->normaliseArray(
            Arr::get($bookingDraft, 'trip', [])
        );

        $actualDistanceKm = null;
        foreach (['distance_km', 'actual_distance_km'] as $distanceKey) {
            $value = Arr::get($draftTrip, $distanceKey);
            if (is_numeric($value) && (float) $value > 0) {
                $actualDistanceKm = round((float) $value, 2);
                break;
            }
        }

        if ($actualDistanceKm === null && is_numeric($order->total_km ?? null) && (float) $order->total_km > 0) {
            $actualDistanceKm = round((float) $order->total_km, 2);
        }

        $actualDurationHr = null;
        foreach (['duration_hr', 'duration_hours', 'actual_duration_hr'] as $durationKey) {
            $value = Arr::get($draftTrip, $durationKey);
            if (is_numeric($value) && (float) $value > 0) {
                $actualDurationHr = round((float) $value, 2);
                break;
            }
        }

        $includedKmLimit = max(0, (float) ($product->km_limit ?? 0));
        $includedHrLimit = max(0, (float) ($product->hr_limit ?? 0));

        /*
         * Route inclusion / payable-charge information for one-way bookings.
         * Prefer the booking snapshot when available, then fall back to the
         * current Product record for older bookings.
         */
        $routeSnapshot = $this->normaliseArray(
            Arr::get($bookingDraft, 'fare.route_charges', [])
        );

        $tollIncluded = (bool) (
            Arr::get($routeSnapshot, 'toll_included')
            ?? ($product->toll_included ?? false)
        );
        $parkingIncluded = (bool) (
            Arr::get($routeSnapshot, 'parking_included')
            ?? ($product->parking_included ?? false)
        );
        $stateTaxIncluded = (bool) (
            Arr::get($routeSnapshot, 'state_tax_included')
            ?? ($product->state_tax_included ?? false)
        );

        $configuredTollTax = $this->money(
            Arr::get($routeSnapshot, 'toll_tax', $product->toll_tax ?? 0)
        );
        $configuredStateTax = $this->money(
            Arr::get($routeSnapshot, 'state_tax', $product->border_tax ?? 0)
        );
        $configuredDriverAllowance = $this->money(
            Arr::get($routeSnapshot, 'driver_allowance', $product->driver_allowances ?? 0)
        );
        $configuredNightCharge = $this->money(
            Arr::get($routeSnapshot, 'night_charge', $product->night_charge ?? 0)
        );

        $invoiceSum = (float) $order->invoices->sum(
            static fn ($invoice): float => (float) ($invoice->ammount ?? 0)
        );

        $this->days = $this->dateDiffInDays($order->date, $order->dateTo);

        return [
            'order' => $order,
            'product' => $product,
            'invoices' => $order->invoices,
            'InvoiceSum' => $invoiceSum,
            'driverCharge' => $category?->driver_charge ?? 0,
            'perKm' => $category?->km_charge ?? 0,
            'extraKmCharge' => $category?->extra_km_charge ?? 0,
            'extraHrCharge' => $category?->extra_hr_charge ?? 0,
            'selectedCategoryName' => $category?->name ?? ($order->taxi_type ?? null),
            'actualDistanceKm' => $actualDistanceKm,
            'actualDurationHr' => $actualDurationHr,
            'includedKmLimit' => $includedKmLimit,
            'includedHrLimit' => $includedHrLimit,
            'tollIncluded' => $tollIncluded,
            'parkingIncluded' => $parkingIncluded,
            'stateTaxIncluded' => $stateTaxIncluded,
            'configuredTollTax' => $configuredTollTax,
            'configuredStateTax' => $configuredStateTax,
            'configuredDriverAllowance' => $configuredDriverAllowance,
            'configuredNightCharge' => $configuredNightCharge,
            'isSelfDrive' => $isSelfDrive,
            'pricingBreakdown' => $pricingBreakdown,
            'selectedOptions' => $selectedOptions,
            'storedBaseFare' => $this->money(
                Arr::get($extraOptions, 'base_fare', $order->grand_total)
            ),
            'storedTollTax' => $this->money(
                Arr::get($extraOptions, 'toll_tax', 0)
            ),
            'storedSecurityDeposit' => $this->money(
                Arr::get(
                    $pricingBreakdown,
                    'security_deposit',
                    Arr::get($extraOptions, 'security_deposit', 0)
                )
            ),
            'storedTaxAmount' => $this->money(
                Arr::get($pricingBreakdown, 'gst_amount', $order->tax ?? 0)
            ),
            'storedDiscountAmount' => $this->money(
                Arr::get(
                    $pricingBreakdown,
                    'coupon_discount',
                    $order->coupon_value ?? 0
                )
            ),
        ];
    }

    private function confirmStripePayment(Order $order)
    {
        Stripe::setApiKey((string) config('services.stripe.secret', env('STRIPE_SECRET')));
        $sessionInfo = Session::retrieve((string) $this->session_id);

        if ($sessionInfo->payment_status !== 'paid') {
            $order->payment_status = 'failed';
            $order->status = 'payment_failed';
            $order->save();

            return redirect()->route('cancel');
        }

        if ($order->payment_status !== 'paid' || $order->status !== 'confirmed') {
            $order->payment_status = 'paid';
            $order->status = 'confirmed';
            $order->save();
        }

        return null;
    }

    private function getSafeOrder(): ?Order
    {
        abort_unless(auth()->check(), 401);

        $query = Order::query()
            ->with([
                'address',
                'items.product',
                'invoices',
            ])
            ->where('user_id', auth()->id());

        if ($this->id) {
            return $query
                ->whereKey($this->id)
                ->first();
        }

        return $query->latest()->first();
    }

    private function dateDiffInDays(mixed $date1, mixed $date2): int
    {
        if (blank($date1) || blank($date2)) {
            return 0;
        }

        try {
            return Carbon::parse($date1)
                ->startOfDay()
                ->diffInDays(Carbon::parse($date2)->startOfDay());
        } catch (\Throwable) {
            return 0;
        }
    }

    private function normaliseExtraOptions(mixed $value): array
    {
        return $this->normaliseArray($value);
    }

    private function normaliseArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return json_decode(json_encode($value), true) ?: [];
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function legacySelectedOptions(array $extraOptions): array
    {
        if (! array_is_list($extraOptions)) {
            return [];
        }

        return collect($extraOptions)
            ->filter(static fn ($item): bool => is_array($item) && ($item['is_checked'] ?? false) === true)
            ->values()
            ->all();
    }

    private function money(mixed $value): float
    {
        return is_numeric($value) ? round(max(0, (float) $value), 2) : 0.0;
    }
}