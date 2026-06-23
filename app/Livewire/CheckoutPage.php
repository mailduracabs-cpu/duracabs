<?php

namespace App\Livewire;

use App\Helpers\CartManagement;
use App\Mail\OrderPlaced;
use App\Models\address;
use App\Models\Coupons;
use App\Models\Order;
use App\Models\Product;
use DateTime;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Title;
use Livewire\Component;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use GuzzleHttp\Client;
use Razorpay\Api\Api;

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
    public $couponValue;
    public $payment_method;

    public $order_id;
    public $grandTotal;
    public $tollTax;
    public $security;
    public $extraAmountArr = array(
        array("is_checked" => false, "type" => "newWehical" ,"title" => "New Vehical", "price" => 0),
        array("is_checked" => false, "type" => "petFrindly" ,"title" => "Pet Friendly", "price" => 0),
        array("is_checked" => false, "type" => "roofCareer" ,"title" => "Roof Career", "price" => 0),
       
    );

    public $extraTotal = 0;


  public function mount()
    {

         $cart_items = CartManagement::getCartItemsFromCookie();

     

  

        $this->roofCareer = $cart_items[0]['roof_career'] ?? 0;
        $this->newWehical = $cart_items[0]['new_vehicle'] ?? 0;
        $this->petFrindly = $cart_items[0]['pet_friendly'] ?? 0;
        $this->security = $cart_items[0]['security'] ?? 0;
        $type = $cart_items[0]['type'] ?? 0;

        if($type === 'self_drive'){
            $this->extraAmountArr = array(
                array("is_checked" => false, "description" => "dsgfsdgsd sdfsdfsdf d" ,"title" => "Security", "price" => $this->security)
            );
        }else{
        $this->extraAmountArr = array(
            array("is_checked" => false, "description" => "Car with roof carrier for adjusting extra luggage" ,"title" => "Roof Career", "price" => $cart_items[0]['roof_career'] ?? 0),
            array("is_checked" => false, "description" => "Promised new car with 2023 or newer model" ,"title" => "New Vehical", "price" => $cart_items[0]['new_vehicle'] ?? 0),
            array("is_checked" => false, "description" => "Choose your pet friendly car for a smoother ride" ,"title" => "Pet Friendly", "price" => $cart_items[0]['pet_friendly'] ?? 0),
        );
    }
    }

    public function newWehicalValueFun($key)
    {
        $this->extraAmountArr[$key]['is_checked'] = ! $this->extraAmountArr[$key]['is_checked'];
        $this->extraTotal = $this->totalPrice;
    }

    public function getTotalPriceProperty()
{
    
    return collect($this->extraAmountArr)
        ->where('is_checked', operator: true)
        ->sum('price');
}


    public function updatedCoupon()
    {
        if (empty($this->coupon)) {
            $this->couponData = false;
            $this->couponName = '';
            $this->couponValue = 0;
            return;
        }

        $coupon = Coupons::where('name', $this->coupon)
            ->where('status', 'active')
            ->whereDate('from_date', '<=', now())
            ->whereDate('to_date', '>=', now())
            ->first();

        if ($coupon) {
            $this->couponData = $coupon->value;
            $this->couponName = $coupon->name;
            $this->couponValue = $coupon->value;
        } else {
            $this->couponData = false;
            $this->couponName = '';
            $this->couponValue = 0;
        }
    }

    public function applyCoupon($couponName)
    {
        $this->coupon = $couponName;
        $this->updatedCoupon();
    }


  

    public function sendSMS($orderId)
    {

        $mobileval = '7088873332,7088873331,7017364693';
        $templateid = '1507166123259919760';

        $message = ' Dear Admin, You have received a new booking number ' . $orderId . ' please log in your account and check the booking status From DURACABS ';



        $api_url = "http://manage.sambsms.com/app/smsapi/index.php?key=3627633B7AC9C6&entity=1701165124480381903&tempid=" . $templateid . "&campaign=0&routeid=7&type=text&contacts=" . $mobileval . "&senderid=DURACB&msg=" . $message;

        $client = new Client();

        try {
            // Make a GET request to the OpenWeather API
            $response = $client->get($api_url);
        } catch (\Exception $e) {
            // Handle any errors that occur during the API request
            return view('api_error', ['error' => $e->getMessage()]);
        }

    }
    public function placeOrder()
    {



        $this->validate([
            'full_name' => 'required',
            'phone' => 'required',
            'email' => 'required',

            'pickup_address' => 'required',

            'payment_method' => 'required',
        ]);

        $cart_items = CartManagement::getCartItemsFromCookie();


        $line_items = [];

        foreach ($cart_items as $item) {
            $line_items[] = [
                'price_data' => [
                    'currency' => 'inr',
                    'unit_amount' => $item['unit_ammount'] * 100,
                    'product_data' => [
                        'name' => $item['name'],
                    ]
                ],
                'quantity' => $item['quantity']
            ];
        }



        $gtotal = CartManagement::calculateGrandTotal($cart_items);

    

        $this->tollTax = $cart_items[0]['toll'];
        $this->roofCareer = $cart_items[0]['roof_career'] ?? 0;
        $this->newWehical = $cart_items[0]['new_vehicle'] ?? 0;
        $this->petFrindly = $cart_items[0]['pet_friendly'] ?? 0;

        $total = $gtotal - $this->couponData;


        $taxAmmount = (5 / 100) * ($gtotal +  (int)$this->tollTax + $this->extraTotal);

        $grandTotal = $this->couponData 
                    ? 
                    ($gtotal +  (5 / 100) * ($gtotal +  (int)$this->tollTax )+ $this->extraTotal) - ($this->couponData / 100) * ($gtotal +  (int)$this->tollTax + (5 / 100) * ($gtotal +  (int)$this->tollTax )+ $this->extraTotal)  
                    : $gtotal +  (5 / 100) * ($gtotal +  (int)$this->tollTax )+ $this->extraTotal ;

      

        if ($this->tollTax) {
            $grandTotal = $grandTotal + $this->tollTax;
        } else {
            $this->grandTotal = $grandTotal;
        }

       
       


        $order = new order();
        $order->user_id = auth()->user()->id;

        $order->grand_total = $grandTotal;
        $order->tax = $taxAmmount;
        
        $order->payment_method = $this->payment_method;
        $order->payment_status = 'pending';
        $order->status = 'new';
        $order->currency = 'inr';
        $order->shipping_ammount = 0;


        //$order->time = new DateTime('now');
        $order->notes = 'order placed by' . auth()->user()->name;

        if ($cart_items[0]['type'] === 'one_way') {
            $order->cityTo = $cart_items[0]['cityTo'];
            $order->productName = $cart_items[0]['cabModel'];
            $order->date = $cart_items[0]['date'];
            $order->time = $cart_items[0]['time'];
            $order->ride_type = $cart_items[0]['type'];
            $order->extraOptions = $this->extraAmountArr;
        }

        if ($cart_items[0]['type'] === 'local') {
            $order->cityTo = $cart_items[0]['cityTo'];
            $order->productName = $cart_items[0]['cabModel'];
            $order->date = $cart_items[0]['date'];
            $order->time = $cart_items[0]['time'];
            $order->ride_type = $cart_items[0]['type'];
            $order->plan = $cart_items[0]['plan'];
            $order->extraOptions = $this->extraAmountArr;
        }

        if ($cart_items[0]['type'] === 'self_drive') {
            $order->cityTo = $cart_items[0]['cityTo'];
            $order->cityFrom = $cart_items[0]['cityFrom'];
            $order->productName = $cart_items[0]['cabModel'];
            $order->date = $cart_items[0]['date'];
            $order->dateTo = $cart_items[0]['dateTo'];
            $order->time = $cart_items[0]['time'];
            $order->endTime = $cart_items[0]['endTime'];
            $order->ride_type = $cart_items[0]['type'];
            $order->extraOptions = $this->extraAmountArr;
            
        }

        if ($cart_items[0]['type'] === 'return') {
            $order->cityTo = $cart_items[0]['cityTo'];
            $order->cityFrom = $cart_items[0]['cityFrom'];
            $order->productName = $cart_items[0]['cabModel'];
            $order->image = $cart_items[0]['image'];
            $order->date = $cart_items[0]['date'];
            $order->dateTo = $cart_items[0]['dateTo'];
            $order->ride_type = $cart_items[0]['type'];
            $order->time = $cart_items[0]['time'];
            $order->total_km = $cart_items[0]['TotalKm'];
            $order->extraOptions = $this->extraAmountArr;
        }

        $order->coupon_value = ($this->couponData / 100) * $gtotal;
        $order->coupon_name = $this->coupon;

        $address = new address();
        $address->full_name = $this->full_name;
        $address->email = $this->email;
        $address->phone = $this->phone;
        $address->phone2 = $this->phone2;
        $address->pickup_address = $this->pickup_address;
        $address->drop_address = $this->drop_address;
        $address->number_travellers = $this->number_travellers;
        $address->number_luggage = $this->number_luggage;
        $address->comments = $this->comments;

        $redirect_url = '';


        $order->save();
        if ($this->payment_method == 'RazorPay') {


            $redirect_url = route('razorpay') . '?amount=' . $this->grandTotal . '&name=' . $this->full_name . '&email=' . $this->email . '&phone=' . $this->phone . '&id=' . $order->id;


        } else {
            $redirect_url = route('success');

        }
        $address->order_id = $order->id;
        $address->save();
        $order->items()->createMany($cart_items);
        $this->sendSMS($order->id);
        CartManagement::clearCartItems($cart_items);
        return redirect($redirect_url);
    }




    public function render()
    {
        $cart_items = CartManagement::getCartItemsFromCookie();
        
        if(empty($cart_items)){
             redirect()->route('homepage');
        }


        $grand_total = CartManagement::calculateGrandTotal($cart_items);

        $this->tollTax = $cart_items[0]['toll'] ?? 0;
        $this->roofCareer = $cart_items[0]['roof_career'] ?? 0;
        $this->newWehical = $cart_items[0]['new_vehicle'] ?? 0;
        $this->petFrindly = $cart_items[0]['pet_friendly'] ?? 0;

        // Get available Coupons
        $availableCoupons = Coupons::where('status', 'active')
            ->whereDate('from_date', '<=', now())
            ->whereDate('to_date', '>=', now())
            ->orderBy('value', 'desc')
            ->get();

        

        return view('livewire.checkout-page', [
            'cart_items' => $cart_items,
            'grand_total' => $grand_total,
            'availableCoupons' => $availableCoupons,
        ]);
    }
}
