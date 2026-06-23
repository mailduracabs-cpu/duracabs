<?php
  
namespace App\Http\Controllers;
  
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Exception;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
  
class RazorpayPaymentController extends Controller
{
    /**
     * Write code on Method
     *
     * @return response()
     */
    public function index(): View
    {        
        return view('razorpay');
    }
  
    /**
     * Write code on Method
     *
     * @return response()
     */
    public function store(Request $request): RedirectResponse
    {
        $input = $request->all();
  
        $api = new Api(env('RAZORPAY_API_KEY'), env('RAZORPAY_API_SECRET'));
  
        $payment = $api->payment->fetch($input['razorpay_payment_id']);
        $id = $payment->description;
      
        $updateOrder = [
        'payment_status' => 'paid'
        ];

        

        if(!empty($input['razorpay_payment_id'])) {

            try {
                $response = $api->payment->fetch($input['razorpay_payment_id'])
                                         ->capture(['amount'=>$payment['amount']]); 
  
            } catch (Exception $e) {
                return redirect()->back()
                                 ->with('error', $e->getMessage());
            }
            
        }
        Order::find( $id )->update($updateOrder);

        return redirect(route('success')); 
       
    }
}
