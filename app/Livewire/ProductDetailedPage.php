<?php

namespace App\Livewire;

use App\Helpers\CartManagement;
use App\Livewire\Partials\Navbar;
use App\Models\Brand;
use App\Models\Product;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Ride Detail - Duracabs')]
class ProductDetailedPage extends Component
{

    use LivewireAlert;
   
    public $slug;
    public $hours = false;
    public $tab = false;
    public $date = false;
    public $time = false;

    public $endDate = false;
    public $endTime = false;
    public $plan = '4 Hour / 40 Km';
    public $cars = false;

    public $price = false;
    public $name = false;
    public $categoryName = false;
     public $toll = false;
    public $newVehical = false;
    public $petFrindly = false;
    public $roof_career = false;
    public $security = false;

    public $quantity = 1;
    public function mount($slug){
        $this->slug = $slug;
    }

    public function test()
{
    $this->quantity++;
    
}

public function addToCartOneWay($product_id){

    $total_count = CartManagement::addItemsToCartOneWay($product_id);

    $this->dispatch('update-cart-count', total_count: $total_count)->to(Navbar::class);

    $this->alert('success', 'Package Added to the cart successfully', [
        'position' => 'center-end',
        'timer' => 3000,
        'toast' => true,
       ]);

    redirect(route('checkout'));
}

public function addToCartLocal($product_id){
    $total_count = CartManagement::addItemsToCartLocal($product_id);

    $this->dispatch('update-cart-count', total_count: $total_count)->to(Navbar::class);

    $this->alert('success', 'Package Added to the cart successfully', [
        'position' => 'center-end',
        'timer' => 3000,
        'toast' => true,
       ]);

    redirect(route('checkout'));
}

public function addToCartSelfDrive($product_id){
    $total_count = CartManagement::addItemsToCartSelfDrive($product_id);

    $this->dispatch('update-cart-count', total_count: $total_count)->to(Navbar::class);

    $this->alert('success', 'Package Added to the cart successfully', [
        'position' => 'center-end',
        'timer' => 3000,
        'toast' => true,
       ]);

    redirect(route('checkout'));
}

public function submitOneWay($product_id){
  
   

    $this->validate([
        'date' => 'required',
        'time' => 'required',
    ]);

    $this->addToCartOneWay($product_id);    

}

public function submitLocal($product_id){

  
    $this->validate([
        'date' => 'required',
        'time' => 'required',
        'plan' => 'required',
    ]);

    $this->addToCartLocal($product_id);    

}

public function submitSelfDrive($product_id){

  
    $this->validate([
        'date' => 'required',
        'time' => 'required',
        'endDate' => 'required',
        'endTime' => 'required',
        
    ]);

      

    $this->addToCartSelfDrive($product_id);    

}

public function updatedEndDate() {
    $date1 = sprintf("%s %s", $this->date, $this->time);

    $date2 = sprintf("%s %s", $this->endDate, $this->endTime);

    $diff = abs(strtotime($date2) - strtotime($date1));

    $years = floor($diff / (365 * 60 * 60 * 24));
    $months = floor(($diff - $years * 365 * 60 * 60 * 24) / (30 * 60 * 60 * 24));
    $days = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24) / (60 * 60 * 24));

    $hours = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24 - $days * 60 * 60 * 24) / (60 * 60));

    $minuts = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24 - $days * 60 * 60 * 24 - $hours * 60 * 60) / 60);

    $seconds = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24 - $days * 60 * 60 * 24 - $hours * 60 * 60 - $minuts * 60));

    //dd($years, $months, $days, $hours, $minuts);

    $calculatehours =  $days * 24 + $hours;

    $hours = 24 < $calculatehours ?  $calculatehours : 24;

    $this->hours = $hours;
}

public function updatedEndTime() {
    $date1 = sprintf("%s %s", $this->date, $this->time);

    $date2 = sprintf("%s %s", $this->endDate, $this->endTime);

    $diff = abs(strtotime($date2) - strtotime($date1));

    $years = floor($diff / (365 * 60 * 60 * 24));
    $months = floor(($diff - $years * 365 * 60 * 60 * 24) / (30 * 60 * 60 * 24));
    $days = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24) / (60 * 60 * 24));

    $hours = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24 - $days * 60 * 60 * 24) / (60 * 60));

    $minuts = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24 - $days * 60 * 60 * 24 - $hours * 60 * 60) / 60);

    $seconds = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24 - $days * 60 * 60 * 24 - $hours * 60 * 60 - $minuts * 60));

    //dd($years, $months, $days, $hours, $minuts);

    $calculatehours =  $days * 24 + $hours;

    $hours = 24 < $calculatehours ?  $calculatehours : 24;

    $this->hours = $hours;
}

public function updatedTime() {
    $date1 = sprintf("%s %s", $this->date, $this->time);

    $date2 = sprintf("%s %s", $this->endDate, $this->endTime);

    $diff = abs(strtotime($date2) - strtotime($date1));

    $years = floor($diff / (365 * 60 * 60 * 24));
    $months = floor(($diff - $years * 365 * 60 * 60 * 24) / (30 * 60 * 60 * 24));
    $days = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24) / (60 * 60 * 24));

    $hours = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24 - $days * 60 * 60 * 24) / (60 * 60));

    $minuts = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24 - $days * 60 * 60 * 24 - $hours * 60 * 60) / 60);

    $seconds = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24 - $days * 60 * 60 * 24 - $hours * 60 * 60 - $minuts * 60));

    //dd($years, $months, $days, $hours, $minuts);

    $calculatehours =  $days * 24 + $hours;

    $hours = 24 < $calculatehours ?  $calculatehours : 24;

    $this->hours = $hours;
}

public function updatedDate() {
    $date1 = sprintf("%s %s", $this->date, $this->time);

    $date2 = sprintf("%s %s", $this->endDate, $this->endTime);

    $diff = abs(strtotime($date2) - strtotime($date1));

    $years = floor($diff / (365 * 60 * 60 * 24));
    $months = floor(($diff - $years * 365 * 60 * 60 * 24) / (30 * 60 * 60 * 24));
    $days = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24) / (60 * 60 * 24));

    $hours = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24 - $days * 60 * 60 * 24) / (60 * 60));

    $minuts = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24 - $days * 60 * 60 * 24 - $hours * 60 * 60) / 60);

    $seconds = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24 - $days * 60 * 60 * 24 - $hours * 60 * 60 - $minuts * 60));

    //dd($years, $months, $days, $hours, $minuts);

    $calculatehours =  $days * 24 + $hours;

    $hours = 24 < $calculatehours ?  $calculatehours : 24;

    $this->hours = $hours;
}


public function tabValue($val){

    //  dd($val);
    $this->tab = $val[0];
    $this->price = $val[1];
    $this->name = $val[2];
    $this->categoryName = $val[3];

    if($this->tab == 'one_way' || $this->tab == 'local'){
        $this->toll = $val[4];
        $this->newVehical = $val[5];
        $this->petFrindly = $val[6];
        $this->roof_career = $val[7];
    }

    if($this->tab == 'self_drive'){
        $this->security = $val[4];
       
    }
   
}
    

    

    public function increaseQty(){
        $this->quantity++;
    }

    public function decreaseQty(){
       if($this->quantity > 1){
        $this->quantity--;
       }
    }

    public function addToCart($product_id){
        $total_count = CartManagement::addItemsToCart($product_id);

        $this->dispatch('update-cart-count', total_count: $total_count)->to(Navbar::class);

        $this->alert('success', 'Package Added to the cart successfully', [
            'position' => 'center-end',
            'timer' => 3000,
            'toast' => true,
           ]);
        redirect(route('checkout'));
    }

    public function render()
   
    {

        $ride = Product::where('slug', $this->slug)->firstOrFail();
        $brand = Brand::where('id', $ride->booking_to)->firstOrFail();

        $links = $ride->links;
        $prices = $ride->prices;

        

        $ridesQuery = Product::query()->where('is_active',1);

        if(!empty($ride->brand_id)){
            $ridesQuery->where('brand_id', $ride->brand_id);
        }

        if(!empty($ride->booking_to)){
            $ridesQuery->where('booking_to', $ride->booking_to);
        }

        if(!empty($ride->ride_type)){
            $ridesQuery->where('ride_type', $ride->ride_type);
        }

    
      $imageMeta = url('storage/') . '/' . $ride->images[0];



        return view('livewire.product-detailed-page',[
            'ride' => $ride,
            'rides'=> $ridesQuery->paginate(9),
            'cityTo' => $brand,
            'links' => $links,
            'prices' => $prices,
            'imageMeta' => $imageMeta 
            
        ]);
    }
}
