<?php

namespace App\Livewire;

use App\Models\Address;
use App\Models\Coupons;
use App\Models\Order;
use App\Models\Vehicle;
use App\Services\SelfDrivePricingService;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Title('Checkout')]
class CheckoutPage extends Component
{
    public $full_name;
    public $phone;
    public $phone2;
    public $email;
    public $pickup_address;
    public $drop_address;
    public $number_travellers;
    public $number_luggage;
    public $comments;
    public $coupon;
    public $couponData = false;
    public $couponName;
    public $couponValue = 0;
    public $payment_method;
    public string $payment_option = 'token';
    public float $reservationTokenAmount = 500.00;

    public $order_id;
    public $grandTotal = 0;
    public $tollTax = 0;
    public $security = 0;
    public $extraTotal = 0;
    public $taxAmount = 0;
    public $discountAmount = 0;
    public $isSubmitting = false;

    public array $bookingDraft = [];
    public array $pricingBreakdown = [];
    public ?string $pricingError = null;

    public $extraAmountArr = [
        ['is_checked' => false, 'type' => 'newWehical', 'title' => 'New Vehicle', 'price' => 0],
        ['is_checked' => false, 'type' => 'petFrindly', 'title' => 'Pet Friendly', 'price' => 0],
        ['is_checked' => false, 'type' => 'roofCareer', 'title' => 'Roof Carrier', 'price' => 0],
    ];

    public function mount()
    {
        $this->bookingDraft = $this->getBookingDraft();

        if ($this->bookingDraft === []) {
            session()->flash('error', 'Booking details expired. Please select your ride again.');

            return redirect('/');
        }

        $this->prefillCustomerDetails();
        $this->configureExtraOptions();
        $this->recalculateTotals();
    }

    public function newWehicalValueFun($key): void
    {
        if (! isset($this->extraAmountArr[$key])) {
            return;
        }

        // Refundable security is compulsory for self-drive bookings.
        if ($this->isSelfDriveBooking()
            && ($this->extraAmountArr[$key]['type'] ?? '') === 'security') {
            return;
        }

        $this->extraAmountArr[$key]['is_checked'] = ! $this->extraAmountArr[$key]['is_checked'];
        $this->recalculateTotals();
    }

    /**
     * Optional extras only. Refundable security is intentionally excluded.
     */
    public function getTotalPriceProperty(): float
    {
        return (float) collect($this->extraAmountArr)
            ->where('is_checked', true)
            ->reject(fn (array $option): bool => ($option['type'] ?? '') === 'security')
            ->sum(fn (array $option): float => (float) ($option['price'] ?? 0));
    }

    public function updatedCoupon(): void
    {
        $couponCode = trim((string) $this->coupon);
        $this->coupon = $couponCode;

        if ($couponCode === '') {
            $this->resetCoupon();
            $this->recalculateTotals();

            return;
        }

        $coupon = Coupons::query()
            ->select(['name', 'value'])
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($couponCode)])
            ->where('status', 'active')
            ->whereDate('from_date', '<=', today())
            ->whereDate('to_date', '>=', today())
            ->first();

        if ($coupon) {
            $this->couponData = (float) $coupon->value;
            $this->couponName = (string) $coupon->name;
            $this->couponValue = (float) $coupon->value;
            $this->coupon = (string) $coupon->name;
        } else {
            $this->resetCoupon(false);
        }

        $this->recalculateTotals();
    }

    private function resetCoupon(bool $clearCode = true): void
    {
        if ($clearCode) {
            $this->coupon = '';
        }

        $this->couponData = false;
        $this->couponName = '';
        $this->couponValue = 0;
        $this->discountAmount = 0;
    }

    public function applyCoupon($couponName): void
    {
        $this->coupon = $couponName;
        $this->updatedCoupon();
    }

    private function recalculateTotals(): float
    {
        $this->bookingDraft = $this->getBookingDraft();
        $this->pricingError = null;

        if ($this->bookingDraft === []) {
            $this->resetCalculatedAmounts();

            return 0;
        }

        if ($this->isSelfDriveBooking()) {
            return $this->recalculateSelfDriveTotals();
        }

        return $this->recalculateTaxiTotals();
    }

    /**
     * Existing taxi calculation is kept unchanged to protect the live taxi flow.
     */
    private function recalculateTaxiTotals(): float
    {
        $this->pricingBreakdown = [];
        $this->security = 0;

        $baseFare = $this->getLegacyBaseFare();
        $this->tollTax = (float) Arr::get($this->bookingDraft, 'fare.toll', 0);
        $this->extraTotal = $this->totalPrice;

        $taxableAmount = $baseFare + $this->tollTax + $this->extraTotal;
        $this->taxAmount = round(0.05 * $taxableAmount, 2);
        $beforeDiscount = $taxableAmount + $this->taxAmount;

        $this->discountAmount = $this->couponData
            ? round(((float) $this->couponData / 100) * $beforeDiscount, 2)
            : 0;

        $this->grandTotal = max(0, round($beforeDiscount - $this->discountAmount, 2));

        return (float) $this->grandTotal;
    }

    /**
     * Self-drive prices come only from SelfDrivePricingService.
     */
    private function recalculateSelfDriveTotals(): float
    {
        try {
            $vehicle = $this->resolveSelfDriveVehicle();

            if (! $vehicle) {
                throw new \RuntimeException('Selected self-drive vehicle is no longer available.');
            }

            [$startAt, $endAt] = $this->selfDriveDateTimes();

            $fare = (array) ($this->bookingDraft['fare'] ?? []);
            $trip = (array) ($this->bookingDraft['trip'] ?? []);

            $this->tollTax = $this->money($fare['toll'] ?? 0);
            $this->extraTotal = $this->totalPrice;

            $securityDeposit = $this->money(
                $fare['security']
                    ?? $fare['security_deposit']
                    ?? $vehicle->security_deposit
                    ?? 0
            );

            $options = [
                'rental_mode' => $trip['rental_mode']
                    ?? $trip['plan_type']
                    ?? $fare['rental_mode']
                    ?? $fare['mode']
                    ?? null,
                'special_request_total' => $this->extraTotal,
                'toll_amount' => $this->tollTax,
                'gst_percent' => 5,
                'security_deposit' => $securityDeposit,
                'coupon_discount' => 0,
            ];

            // First quote establishes the coupon-eligible amount before GST.
            $quoteBeforeCoupon = $this->pricingService()->calculate(
                $vehicle,
                $startAt,
                $endAt,
                $options
            );

            $couponDiscount = $this->couponData
                ? round(
                    (($this->money($this->couponData)) / 100)
                    * ($this->money($quoteBeforeCoupon['rent'] ?? 0)
                        + $this->money($quoteBeforeCoupon['extras_total'] ?? 0)),
                    2
                )
                : 0;

            $options['coupon_discount'] = $couponDiscount;

            $pricing = $this->pricingService()->calculate(
                $vehicle,
                $startAt,
                $endAt,
                $options
            );

            $this->pricingBreakdown = $pricing;
            $this->security = $this->money($pricing['security_deposit'] ?? 0);
            $this->taxAmount = $this->money($pricing['gst_amount'] ?? 0);
            $this->discountAmount = $this->money($pricing['coupon_discount'] ?? 0);
            $this->grandTotal = $this->money($pricing['payable_amount'] ?? 0);

            $this->syncSecurityOption();

            return (float) $this->grandTotal;
        } catch (Throwable $exception) {
            report($exception);
            $this->resetCalculatedAmounts();
            $this->pricingError = $exception->getMessage();

            return 0;
        }
    }

    public function updatedPaymentOption(): void
    {
        if (! in_array($this->payment_option, ['token', 'full'], true)) {
            $this->payment_option = 'token';
        }
    }

    public function getAmountPayableNowProperty(): float
    {
        if (! $this->isSelfDriveBooking()) {
            return $this->money($this->grandTotal);
        }

        if ($this->payment_option === 'full') {
            return $this->money($this->grandTotal);
        }

        return min(
            $this->money($this->reservationTokenAmount),
            $this->money($this->grandTotal)
        );
    }

    public function getBalanceAmountProperty(): float
    {
        return $this->isSelfDriveBooking()
            ? $this->money($this->grandTotal - $this->amountPayableNow)
            : 0.00;
    }

    public function placeOrder()
    {
        if ($this->isSubmitting) {
            return null;
        }

        $this->isSubmitting = true;

        try {
            $rules = [
                'full_name' => 'required|string|max:150',
                'phone' => 'required|digits:10',
                'email' => 'required|email|max:150',
            ];

            if ($this->isSelfDriveBooking()) {
                $rules['payment_option'] = 'required|in:token,full';
                $rules['payment_method'] = 'required|in:RazorPay';
            } else {
                $rules += [
                    'phone2' => 'nullable|digits:10',
                    'pickup_address' => 'required|string|max:500',
                    'drop_address' => 'nullable|string|max:500',
                    'payment_method' => 'required|in:cash,RazorPay',
                ];
            }

            $this->validate($rules);
        } catch (Throwable $exception) {
            $this->isSubmitting = false;
            throw $exception;
        }

        $this->bookingDraft = $this->getBookingDraft();

        if ($this->bookingDraft === []) {
            $this->isSubmitting = false;
            session()->flash('error', 'Booking details expired. Please select your ride again.');

            return redirect('/');
        }

        if (! auth()->check()) {
            $this->isSubmitting = false;
            session()->flash('error', 'Please login before confirming your booking.');

            return redirect('/login');
        }

        $grandTotal = $this->recalculateTotals();

        if ($this->pricingError) {
            $this->isSubmitting = false;
            session()->flash('error', $this->pricingError);

            return null;
        }

        if ($grandTotal <= 0) {
            $this->isSubmitting = false;
            session()->flash('error', 'Invalid booking amount. Please try again.');

            return null;
        }

        $lockKey = 'checkout_lock_' . auth()->id();

        if (! Cache::add($lockKey, true, now()->addSeconds(30))) {
            $this->isSubmitting = false;
            session()->flash('error', 'Please wait. Your booking is already processing.');

            return null;
        }

        try {
            $order = $this->createOrder($grandTotal);
            $this->order_id = $order->id;

            if ($this->payment_method === 'RazorPay') {
                return redirect(
                    route('razorpay')
                    . '?amount=' . $this->amountPayableNow
                    . '&name=' . urlencode((string) $this->full_name)
                    . '&email=' . urlencode((string) $this->email)
                    . '&phone=' . urlencode((string) $this->phone)
                    . '&id=' . $order->id
                );
            }

            $this->sendAdminBookingSms($order);
            session()->forget('booking_draft');

            return redirect(route('success') . '?id=' . $order->id);
        } catch (Throwable $exception) {
            report($exception);
            Cache::forget($lockKey);
            $this->isSubmitting = false;
            session()->flash(
                'error',
                app()->isLocal()
                    ? $exception->getMessage()
                    : 'We could not process your reservation. Please try again.'
            );

            return null;
        }
    }

    private function createOrder(float $grandTotal): Order
    {
        return DB::transaction(function () use ($grandTotal): Order {
            $draft = $this->bookingDraft;
            $trip = (array) ($draft['trip'] ?? []);
            $fare = (array) ($draft['fare'] ?? []);
            $product = (array) ($draft['product'] ?? []);
            $vehicle = (array) ($draft['vehicle'] ?? []);
            $rideType = (string) ($draft['type'] ?? $product['ride_type'] ?? 'one_way');
            $quantity = max(1, (int) ($trip['quantity'] ?? $fare['quantity'] ?? 1));
            $unitAmount = $this->isSelfDriveBooking()
                ? $this->money($this->pricingBreakdown['rate'] ?? 0)
                : (float) ($fare['unit_price'] ?? 0);
            $baseFare = $this->getBaseFare();

            $order = new Order();
            $order->user_id = auth()->id();
            $order->grand_total = $grandTotal;
            $order->tax = $this->taxAmount;
            $order->payment_method = $this->payment_method;
            $order->payment_status = 'pending';
            $order->status = 'new';
            $order->currency = 'inr';
            $order->shipping_ammount = 0;
            $order->notes = 'Order placed by ' . (auth()->user()?->name ?: $this->full_name);
            $order->coupon_value = $this->discountAmount;
            $order->coupon_name = $this->couponName ?: null;
            $order->ride_type = $rideType;
            $order->cityFrom = $trip['pickup_name'] ?? null;
            $order->cityTo = $trip['drop_name'] ?? ($trip['pickup_name'] ?? null);
            $order->date = $this->nullIfEmpty($trip['date'] ?? null);
            $order->dateTo = $this->nullIfEmpty($trip['end_date'] ?? null);
            $order->time = $this->nullIfEmpty($trip['time'] ?? null);
            $order->endTime = $this->nullIfEmpty($trip['end_time'] ?? null);
            $order->booking_from = $this->isSelfDriveBooking()
                ? 'Pending customer profile completion'
                : $this->pickup_address;
            $order->booking_to = $this->isSelfDriveBooking()
                ? null
                : ($this->drop_address ?: null);
            $order->productName = $product['name'] ?? $vehicle['name'] ?? 'Dura Cabs Booking';
            $order->taxi_type = $product['category_name'] ?? null;
            $order->total_km = $trip['km_value'] ?? $trip['km'] ?? null;
            $order->plan = $this->normalisePlan($trip['plan'] ?? null);

            $resolvedVehicle = null;

            if ($rideType === 'self_drive' && ! empty($draft['vehicle_id'])) {
                $resolvedVehicle = $this->resolveSelfDriveVehicle();

                $order->vehicle_id = (int) $draft['vehicle_id'];

                /*
                 * orders.transporter_id references the transporter User record.
                 * Vehicle ownership is linked through TransporterProfile, so the
                 * profile's user_id must be stored here—not transporter_profile_id.
                 */
                if ($resolvedVehicle) {
                    $resolvedVehicle->loadMissing('transporter.user');

                    $order->transporter_id = $resolvedVehicle->transporter?->user_id
                        ?? $resolvedVehicle->user_id
                        ?? null;
                }
            }

            if (Schema::hasColumn($order->getTable(), 'extraOptions')) {
                $order->extraOptions = [
                    'selected_options' => collect($this->extraAmountArr)
                        ->where('is_checked', true)
                        ->values()
                        ->all(),
                    'base_fare' => $baseFare,
                    'toll_tax' => $this->tollTax,
                    'security_deposit' => $this->security,
                    'tax_amount' => $this->taxAmount,
                    'discount_amount' => $this->discountAmount,
                    'pricing_breakdown' => $this->pricingBreakdown,
                    'booking_draft' => $draft,
                    'transporter_profile_id' => $resolvedVehicle?->transporter_profile_id,
                    'transporter_user_id' => $order->transporter_id,
                    'reservation' => $this->isSelfDriveBooking() ? [
                        'payment_option' => $this->payment_option,
                        'reservation_token_amount' => $this->money($this->reservationTokenAmount),
                        'amount_payable_now' => $this->amountPayableNow,
                        'balance_amount' => $this->balanceAmount,
                        'details_status' => 'pending',
                        'current_step' => 'quick_reservation',
                    ] : null,
                ];
            }

            $order->save();

            address::query()->create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'full_name' => $this->full_name,
                'email' => $this->email,
                'phone' => $this->phone,
                'phone2' => $this->isSelfDriveBooking() ? null : ($this->phone2 ?: null),
                'pickup_address' => $this->isSelfDriveBooking()
                    ? 'Pending customer profile completion'
                    : $this->pickup_address,
                'drop_address' => $this->isSelfDriveBooking() ? null : ($this->drop_address ?: null),
                'number_travellers' => $this->isSelfDriveBooking() ? null : ($this->number_travellers ?: null),
                'number_luggage' => $this->isSelfDriveBooking() ? null : ($this->number_luggage ?: null),
                'comments' => $this->isSelfDriveBooking() ? null : ($this->comments ?: null),
            ]);

            $productId = $draft['product_id'] ?? $product['id'] ?? null;

            if ($productId) {
                $order->items()->create([
                    'product_id' => (int) $productId,
                    'quantity' => $quantity,
                    'unit_ammount' => $unitAmount,
                    'total_ammount' => $baseFare,
                ]);
            }

            return $order->fresh(['address', 'items']);
        });
    }

    private function configureExtraOptions(): void
    {
        $type = (string) ($this->bookingDraft['type'] ?? '');
        $fare = (array) ($this->bookingDraft['fare'] ?? []);
        $product = (array) ($this->bookingDraft['product'] ?? []);

        $this->security = (float) ($fare['security'] ?? $fare['security_deposit'] ?? 0);

        if ($type === 'self_drive') {
            $vehicle = $this->resolveSelfDriveVehicle(false);

            if ($this->security <= 0 && $vehicle) {
                $this->security = $vehicle->getSecurityDepositAmount();
            }

            $this->extraAmountArr = [[
                'is_checked' => true,
                'type' => 'security',
                'description' => 'Refundable security deposit',
                'title' => 'Security',
                'price' => $this->security,
            ]];

            return;
        }

        $this->extraAmountArr = [
            [
                'is_checked' => false,
                'type' => 'roofCareer',
                'description' => 'Car with roof carrier for adjusting extra luggage',
                'title' => 'Roof Carrier',
                'price' => $this->optionPrice($product['roof_carrier'] ?? 0),
            ],
            [
                'is_checked' => false,
                'type' => 'newWehical',
                'description' => 'Promised new car with 2023 or newer model',
                'title' => 'New Vehicle',
                'price' => $this->optionPrice($product['new_vehicle'] ?? 0),
            ],
            [
                'is_checked' => false,
                'type' => 'petFrindly',
                'description' => 'Choose your pet friendly car for a smoother ride',
                'title' => 'Pet Friendly',
                'price' => $this->optionPrice($product['pet_friendly'] ?? 0),
            ],
        ];
    }

    private function prefillCustomerDetails(): void
    {
        $user = auth()->user();
        $draftMobile = Arr::get($this->bookingDraft, 'customer.mobile');

        $this->full_name = $this->full_name ?: ($user?->name ?? '');
        $this->email = $this->email ?: ($user?->email ?? '');
        $this->phone = $this->phone ?: ($user?->mobile ?? $draftMobile ?? '');
        $this->pickup_address = $this->pickup_address
            ?: (string) Arr::get($this->bookingDraft, 'trip.pickup_name', '');
        $this->drop_address = $this->drop_address
            ?: (string) Arr::get($this->bookingDraft, 'trip.drop_name', '');

        if ($this->isSelfDriveBooking()) {
            $this->payment_method = 'RazorPay';
            $this->payment_option = in_array($this->payment_option, ['token', 'full'], true)
                ? $this->payment_option
                : 'token';
        }
    }

    private function getBookingDraft(): array
    {
        $draft = session('booking_draft');

        return is_array($draft) ? $draft : [];
    }

    private function isSelfDriveBooking(): bool
    {
        return (string) ($this->bookingDraft['type'] ?? '') === 'self_drive';
    }

    private function resolveSelfDriveVehicle(bool $requireBookable = true): ?Vehicle
    {
        $vehicleId = (int) ($this->bookingDraft['vehicle_id'] ?? 0);

        if ($vehicleId <= 0) {
            return null;
        }

        $query = Vehicle::query()
            ->selfDrive()
            ->whereKey($vehicleId);

        if ($requireBookable) {
            $query->availableForRental();
        }

        return $query->first();
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function selfDriveDateTimes(): array
    {
        $trip = (array) ($this->bookingDraft['trip'] ?? []);

        $startValue = $trip['start_datetime'] ?? null;
        $endValue = $trip['end_datetime'] ?? null;

        if (blank($startValue)) {
            $startDate = $trip['date'] ?? null;
            $startTime = $trip['time'] ?? '00:00';

            if (blank($startDate)) {
                throw new \InvalidArgumentException('Self-drive start date is missing.');
            }

            $startValue = trim((string) $startDate . ' ' . (string) $startTime);
        }

        if (blank($endValue)) {
            $endDate = $trip['end_date'] ?? null;
            $endTime = $trip['end_time'] ?? '00:00';

            if (blank($endDate)) {
                throw new \InvalidArgumentException('Self-drive end date is missing.');
            }

            $endValue = trim((string) $endDate . ' ' . (string) $endTime);
        }

        return [Carbon::parse($startValue), Carbon::parse($endValue)];
    }

    private function pricingService(): SelfDrivePricingService
    {
        return app(SelfDrivePricingService::class);
    }

    private function syncSecurityOption(): void
    {
        foreach ($this->extraAmountArr as $key => $option) {
            if (($option['type'] ?? '') !== 'security') {
                continue;
            }

            $this->extraAmountArr[$key]['is_checked'] = true;
            $this->extraAmountArr[$key]['price'] = $this->security;

            return;
        }
    }

    private function resetCalculatedAmounts(): void
    {
        $this->grandTotal = 0;
        $this->tollTax = 0;
        $this->security = 0;
        $this->extraTotal = 0;
        $this->taxAmount = 0;
        $this->discountAmount = 0;
        $this->pricingBreakdown = [];
    }

    private function getBaseFare(): float
    {
        if ($this->isSelfDriveBooking() && $this->pricingBreakdown !== []) {
            return $this->money($this->pricingBreakdown['rent'] ?? 0);
        }

        return $this->getLegacyBaseFare();
    }

    private function getLegacyBaseFare(): float
    {
        $fare = (array) ($this->bookingDraft['fare'] ?? []);
        $quantity = max(1, (int) ($fare['quantity'] ?? Arr::get($this->bookingDraft, 'trip.quantity', 1)));
        $unitPrice = (float) ($fare['unit_price'] ?? 0);

        foreach (['total', 'subtotal'] as $key) {
            if (isset($fare[$key]) && is_numeric($fare[$key])) {
                return max(0, (float) $fare[$key]);
            }
        }

        return max(0, $unitPrice * $quantity);
    }

    private function optionPrice(mixed $value): float
    {
        return is_numeric($value) ? max(0, (float) $value) : 0;
    }

    private function money(mixed $value): float
    {
        return round(max(0, (float) $value), 2);
    }

    private function normalisePlan(mixed $plan): string
    {
        $allowed = ['none', '4 Hour / 40 Km', '8 Hour / 80 Km', '12 Hour / 120 Km'];
        $plan = trim((string) $plan);

        return in_array($plan, $allowed, true) ? $plan : 'none';
    }

    private function nullIfEmpty(mixed $value): mixed
    {
        return blank($value) ? null : $value;
    }

    private function sendAdminBookingSms(Order $order): void
    {
        $message = 'Dear Admin, You have received a new booking number '
            . $order->id
            . ' please log in your account and check the booking status From DURACABS';

        try {
            Http::timeout(8)
                ->connectTimeout(3)
                ->retry(1, 200)
                ->get('http://manage.sambsms.com/app/smsapi/index.php', [
                    'key' => '3627633B7AC9C6',
                    'entity' => '1701165124480381903',
                    'tempid' => '1507166123259919760',
                    'campaign' => 0,
                    'routeid' => 7,
                    'type' => 'text',
                    'contacts' => '7088873332,7088873331,7017364693',
                    'senderid' => 'DURACB',
                    'msg' => $message,
                ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function cartItemsForView(): array
    {
        if ($this->bookingDraft === []) {
            return [];
        }

        $trip = (array) ($this->bookingDraft['trip'] ?? []);
        $fare = (array) ($this->bookingDraft['fare'] ?? []);
        $product = (array) ($this->bookingDraft['product'] ?? []);
        $vehicle = (array) ($this->bookingDraft['vehicle'] ?? []);
        $quantity = max(1, (int) ($trip['quantity'] ?? $fare['quantity'] ?? 1));
        $resolvedVehicle = $this->isSelfDriveBooking()
            ? $this->resolveSelfDriveVehicle(false)
            : null;

        return [[
            'id' => $this->bookingDraft['product_id'] ?? $this->bookingDraft['vehicle_id'] ?? null,
            'product_id' => $this->bookingDraft['product_id'] ?? null,
            'vehicle_id' => $this->bookingDraft['vehicle_id'] ?? null,
            'type' => $this->bookingDraft['type'] ?? null,
            'name' => $product['name'] ?? $vehicle['name'] ?? 'Dura Cabs Booking',
            'cabModel' => $product['category_name'] ?? $vehicle['name'] ?? null,
            'image_url' => $resolvedVehicle?->front_image_url
                ?? ($product['image_url'] ?? $vehicle['image_url'] ?? null),
            'quantity' => $quantity,
            'unit_ammount' => $this->isSelfDriveBooking()
                ? $this->money($this->pricingBreakdown['rate'] ?? 0)
                : (float) ($fare['unit_price'] ?? 0),
            'total_ammount' => $this->getBaseFare(),
            'cityFrom' => $trip['pickup_name'] ?? null,
            'cityTo' => $trip['drop_name'] ?? null,
            'date' => $trip['date'] ?? null,
            'dateTo' => $trip['end_date'] ?? null,
            'time' => $trip['time'] ?? null,
            'endTime' => $trip['end_time'] ?? null,
            'plan' => $trip['plan'] ?? null,
            'rental_mode' => $this->pricingBreakdown['mode'] ?? ($trip['rental_mode'] ?? null),
            'selected_hours' => $this->pricingBreakdown['selected_hours'] ?? null,
            'chargeable_hours' => $this->pricingBreakdown['chargeable_hours'] ?? null,
            'TotalKm' => $trip['km_value'] ?? $trip['km'] ?? null,
            'toll' => $this->tollTax,
            'security' => $this->security,
        ]];
    }

    public function render()
    {
        $this->bookingDraft = $this->getBookingDraft();
        $availableCoupons = Cache::remember(
            'checkout.available_coupons.' . today()->toDateString(),
            now()->addMinutes(10),
            fn () => Coupons::query()
                ->select(['id', 'name', 'value', 'to_date'])
                ->where('status', 'active')
                ->whereDate('from_date', '<=', today())
                ->whereDate('to_date', '>=', today())
                ->orderByDesc('value')
                ->get()
        );

        if ($this->bookingDraft === []) {
            return view('livewire.checkout-page', [
                'cart_items' => [],
                'grand_total' => 0,
                'availableCoupons' => $availableCoupons,
                'isSelfDrive' => false,
                'amountPayableNow' => 0,
                'balanceAmount' => 0,
            ]);
        }

        $this->recalculateTotals();

        return view('livewire.checkout-page', [
            // Kept as cart_items so the existing Blade template does not need changing yet.
            'cart_items' => $this->cartItemsForView(),
            'grand_total' => $this->getBaseFare(),
            'availableCoupons' => $availableCoupons,
            'isSelfDrive' => $this->isSelfDriveBooking(),
            'amountPayableNow' => $this->amountPayableNow,
            'balanceAmount' => $this->balanceAmount,
        ]);
    }
}