<?php

namespace App\Livewire;


use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use \Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Spatie\Browsershot\Browsershot;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use DateTime;

//use Spatie\LaravelPdf\Facades\Pdf;
#[Title('Suucess - Duracabs')]
class SuccessPage extends Component
{
    #[Url]
    public $session_id;

    
    public $days;

    #[Url(history: true)]
    public $id ;
   
    public function createInvoice(){
        //Browsershot::url('http://127.0.0.1:8000/')->save('newhelloexample.pdf');

        $template = 0;

        $view = view('pdf.invoice')->toHtml();

      
      

        $pdf = Pdf::loadHTML($view);
    return  response()->streamDownload(function () use ($pdf) {
        echo $pdf->stream();
    }, 'invoice.pdf');

    }
    public function render()
    {
     
         $latest_order = null;

        $this->id == true ?   $latest_order =  Order::with('address','items','invoices')->where('id', $this->id)->latest()->first() : $latest_order =  Order::with('address','items','invoices')->where('user_id', auth()->user()->id)->latest()->first();


       
        
        $categories =  Category::where('is_active', 1)->where('name', $latest_order->productName)->get();


        $order_items =  OrderItem::with('product')->where('order_id', $latest_order->id)->get();

        $product = $order_items[0]->product;

        $InvoiceSum = 0;


       foreach ($latest_order->invoices as $item) {
            $InvoiceSum += $item['ammount'];
        }
        
       
        
        
        if($this->session_id){
            Stripe::setApiKey(env('STRIPE_SECRET'));
            $session_info = Session::retrieve($this->session_id);

            if($session_info->payment_status != 'paid'){
                $latest_order->payment_status = 'failed';
                $latest_order->save();
                return redirect()->route('cancel');
            } else if ( $session_info->payment_status == 'paid'){
                $latest_order->payment_status = 'paid';
                $latest_order->save();
            }

        }

       

        function dateDiffInDays($date1, $date2) {
  
            // Calculating the difference in timestamps
            $diff = strtotime($date2) - strtotime($date1);
        
            // 1 day = 24 hours
            // 24 * 60 * 60 = 86400 seconds
            return abs(round($diff / 86400));
        }
    

         $this->days = dateDiffInDays($latest_order->date,$latest_order->dateTo);

        

        return view('livewire.success-page', [
            'order' => $latest_order,
            'product' => $product,
            'invoices' => $latest_order->invoices,
            'InvoiceSum' => $InvoiceSum,
            'driverCharge' =>  $categories[0]->driver_charge,
            'perKm' =>  $categories[0]->km_charge,
            
        ]);
    }
}
