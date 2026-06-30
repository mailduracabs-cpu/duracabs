<?php

namespace App\Livewire;

use App\Helpers\CartManagement;
use App\Models\address;
use App\Models\Coupons;
use App\Models\Order;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;

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

    public $order_id;
    public $grandTotal = 0;
    public $tollTax = 0;
    public $security = 0;
    public $extraTotal = 0;
    public $isSubmitting = false;

    public $extraAmountArr = [
        ["is_checked" => false, "type" => "newWehical", "title" => "New Vehical", "price" => 0],
        ["is_checked" => false, "type" => "petFrindly", "title" => "Pet Friendly", "price" => 0],
        ["is_checked" => false, "type" => "roofCareer", "title" => "Roof Career", "price" => 0],
    ];

    public function mount()
    {
        $cart_items = CartManagement::getCartItemsFromCookie();

        if (empty($cart_items)) {
            return redirect('/');
        }

        $this->security = $cart_items[0]['security'] ?? 0;
        $type = $cart_items[0]['type'] ?? '';

        if ($type === 'self_drive') {
            $this->extraAmountArr = [
                [
                    "is_checked" => true,
                    "description" => "Refundable security deposit",
                    "title" => "Security",
                    "price" => (float) $this->security,
                ],
            ];
            $this->extraTotal = (float) $this->security;
        } else {
            $this->extraAmountArr = [
                [
                    "is_checked" => false,
                    "description" => "Car with roof carrier for adjusting extra luggage",
                    "title" => "Roof Career",
                    "price" => (float) ($cart_items[0]['roof_career'] ?? 0),
                ],
                [
                    "is_checked" => false,
                    "description" => "Promised new car with 2023 or newer model",
                    "title" => "New Vehical",
                    "price" => (float) ($cart_items[0]['new_vehicle'] ?? 0),
                ],
                [
                    "is_checked" => false,
                    "description" => "Choose your pet friendly car for a smoother ride",
                    "title" => "Pet Friendly",
                    "price" => (float) ($cart_items[0]['pet_friendly'] ?? 0),
                ],
            ];
        }

        $this->recalculateTotals();
    }

    public function newWehicalValueFun($key)
    {
        if (!isset($this->extraAmountArr[$key])) {
            return;
        }

        $this->extraAmountArr[$key]['is_checked'] = !$this->extraAmountArr[$key]['is_checked'];
        $this->extraTotal = $this->totalPrice;
        $this->recalculateTotals();
    }

    public function getTotalPriceProperty()
    {
        return collect($this->extraAmountArr)
            ->where('is_checked', true)
            ->sum('price');
    }

    public function updatedCoupon()
    {
        if (empty($this->coupon)) {
            $this->couponData = false;
            $this->couponName = '';
            $this->couponValue = 0;
            $this->recalculateTotals();
            return;
        }

        $coupon = Coupons::where('name', $this->coupon)
            ->where('status', 'active')
            ->whereDate('from_date', '<=', now())
            ->whereDate('to_date', '>=', now())
            ->first();

        if ($coupon) {
            $this->couponData = (float) $coupon->value;
            $this->couponName = $coupon->name;
            $this->couponValue = (float) $coupon->value;
        } else {
            $this->couponData = false;
            $this->couponName = '';
            $this->couponValue = 0;
        }

        $this->recalculateTotals();
    }

    public function applyCoupon($couponName)
    {
        $this->coupon = $couponName;
        $this->updatedCoupon();
    }

    private function recalculateTotals(): float
    {
        $cart_items = CartManagement::getCartItemsFromCookie();

        if (empty($cart_items)) {
            $this->grandTotal = 0;
            return 0;
        }

        $gtotal = (float) CartManagement::calculateGrandTotal($cart_items);
        $this->tollTax = (float) ($cart_items[0]['toll'] ?? 0);
        $this->extraTotal = (float) $this->totalPrice;

        $taxAmount = round((5 / 100) * ($gtotal + $this->tollTax + $this->extraTotal), 2);

        $beforeDiscount = $gtotal + $this->tollTax + $this->extraTotal + $taxAmount;

        $discountAmount = 0;
        if ($this->couponData) {
            $discountAmount = round(($this->couponData / 100) * $beforeDiscount, 2);
        }

        $this->grandTotal = max(0, round($beforeDiscount - $discountAmount, 2));

        return $this->grandTotal;
    }

    public function sendSMS($orderId)
    {
        $mobileval = '7088873332,7088873331,7017364693';
        $templateid = '1507166123259919760';

        $message = 'Dear Admin, You have received a new booking number ' . $orderId . ' please log in your account and check the booking status From DURACABS';

        $api_url = "http://manage.sambsms.com/app/smsapi/index.php?key=3627633B7AC9C6&entity=1701165124480381903&tempid=" . $templateid . "&campaign=0&routeid=7&type=text&contacts=" . $mobileval . "&senderid=DURACB&msg=" . urlencode($message);

        try {
            $client = new Client();
            $client->get($api_url, ['timeout' => 10]);
        } catch (\Exception $e) {
            report($e);
        }
    }

    public function placeOrder()
    {
        if ($this->isSubmitting) {
            return;
        }

        $this->isSubmitting = true;

        $this->validate([
            'full_name' => 'required|string|max:150',
            'phone' => 'required|digits:10',
            'phone2' => 'nullable|digits:10',
            'email' => 'required|email|max:150',
            'pickup_address' => 'required|string|max:500',
            'drop_address' => 'nullable|string|max:500',
            'payment_method' => 'required|in:cash,RazorPay',
        ]);

        $cart_items = CartManagement::getCartItemsFromCookie();

        if (empty($cart_items)) {
            $this->isSubmitting = false;
            session()->flash('error', 'Cart is empty.');
            return redirect('/cart');
        }

        $grandTotal = $this->recalculateTotals();

        if ($grandTotal <= 0) {
            $this->isSubmitting = false;
            session()->flash('error', 'Invalid booking amount. Please try again.');
            return;
        }

        $lockKey = 'checkout_lock_' . auth()->id();

        if (!Cache::add($lockKey, true, now()->addSeconds(30))) {
            $this->isSubmitting = false;
            session()->flash('error', 'Please wait. Your booking is already processing.');
            return;
        }

        try {
            $order = DB::transaction(function () use ($cart_items, $grandTotal) {
                $gtotal = (float) CartManagement::calculateGrandTotal($cart_items);
                $taxAmount = round((5 / 100) * ($gtotal + (float) $this->tollTax + (float) $this->extraTotal), 2);

                $order = new Order();
                $order->user_id = auth()->user()->id;
                $order->grand_total = $grandTotal;
                $order->tax = $taxAmount;
                $order->payment_method = $this->payment_method;
                $order->payment_status = 'pending';

                if ($this->payment_method === 'RazorPay') {
                    $order->status = 'pending_payment';
                } else {
                    $order->status = 'confirmed';
                }

                $order->currency = 'inr';
                $order->shipping_ammount = 0;
                $order->notes = 'order placed by ' . auth()->user()->name;

                $type = $cart_items[0]['type'] ?? '';

                if ($type === 'one_way') {
                    $order->cityTo = $cart_items[0]['cityTo'] ?? null;
                    $order->productName = $cart_items[0]['cabModel'] ?? null;
                    $order->date = $cart_items[0]['date'] ?? null;
                    $order->time = $cart_items[0]['time'] ?? null;
                    $order->ride_type = $type;
                    $order->extraOptions = $this->extraAmountArr;
                }

                if ($type === 'local') {
                    $order->cityTo = $cart_items[0]['cityTo'] ?? null;
                    $order->productName = $cart_items[0]['cabModel'] ?? null;
                    $order->date = $cart_items[0]['date'] ?? null;
                    $order->time = $cart_items[0]['time'] ?? null;
                    $order->ride_type = $type;
                    $order->plan = $cart_items[0]['plan'] ?? null;
                    $order->extraOptions = $this->extraAmountArr;
                }

                if ($type === 'self_drive') {
                    $order->cityTo = $cart_items[0]['cityTo'] ?? null;
                    $order->cityFrom = $cart_items[0]['cityFrom'] ?? null;
                    $order->productName = $cart_items[0]['cabModel'] ?? null;
                    $order->date = $cart_items[0]['date'] ?? null;
                    $order->dateTo = $cart_items[0]['dateTo'] ?? null;
                    $order->time = $cart_items[0]['time'] ?? null;
                    $order->endTime = $cart_items[0]['endTime'] ?? null;
                    $order->ride_type = $type;
                    $order->extraOptions = $this->extraAmountArr;
                }

                if ($type === 'return') {
                    $order->cityTo = $cart_items[0]['cityTo'] ?? null;
                    $order->cityFrom = $cart_items[0]['cityFrom'] ?? null;
                    $order->productName = $cart_items[0]['cabModel'] ?? null;
                    $order->image = $cart_items[0]['image'] ?? null;
                    $order->date = $cart_items[0]['date'] ?? null;
                    $order->dateTo = $cart_items[0]['dateTo'] ?? null;
                    $order->ride_type = $type;
                    $order->time = $cart_items[0]['time'] ?? null;
                    $order->total_km = $cart_items[0]['TotalKm'] ?? null;
                    $order->extraOptions = $this->extraAmountArr;
                }

                $order->coupon_value = $this->couponData ? round(($this->couponData / 100) * $gtotal, 2) : 0;
                $order->coupon_name = $this->coupon;
                $order->save();

                $address = new address();
                $address->order_id = $order->id;
                $address->full_name = $this->full_name;
                $address->email = $this->email;
                $address->phone = $this->phone;
                $address->phone2 = $this->phone2;
                $address->pickup_address = $this->pickup_address;
                $address->drop_address = $this->drop_address;
                $address->number_travellers = $this->number_travellers;
                $address->number_luggage = $this->number_luggage;
                $address->comments = $this->comments;
                $address->save();

                $order->items()->createMany($cart_items);

                return $order;
            });

            if ($this->payment_method === 'RazorPay') {
                return redirect(
                    route('razorpay') .
                    '?amount=' . $this->grandTotal .
                    '&name=' . urlencode($this->full_name) .
                    '&email=' . urlencode($this->email) .
                    '&phone=' . urlencode($this->phone) .
                    '&id=' . $order->id
                );
            }

            $this->sendSMS($order->id);
            CartManagement::clearCartItems($cart_items);

            return redirect(route('success') . '?id=' . $order->id);

        } catch (\Throwable $e) {
            report($e);
            $this->isSubmitting = false;
            Cache::forget($lockKey);
            session()->flash('error', 'Unable to place order. Please try again.');
            return;
        }
    }

    public function render()
    {
        $cart_items = CartManagement::getCartItemsFromCookie();

        if (empty($cart_items)) {
            return view('livewire.checkout-page', [
                'cart_items' => [],
                'grand_total' => 0,
                'availableCoupons' => collect(),
            ]);
        }

        $grand_total = CartManagement::calculateGrandTotal($cart_items);
        $this->tollTax = $cart_items[0]['toll'] ?? 0;

        $availableCoupons = Coupons::where('status', 'active')
            ->whereDate('from_date', '<=', now())
            ->whereDate('to_date', '>=', now())
            ->orderBy('value', 'desc')
            ->get();

        $this->recalculateTotals();

        return view('livewire.checkout-page', [
            'cart_items' => $cart_items,
            'grand_total' => $grand_total,
            'availableCoupons' => $availableCoupons,
        ]);
    }
}
