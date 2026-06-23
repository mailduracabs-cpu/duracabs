<?php

namespace App\Livewire;

use App\Helpers\CartManagement;
use App\Livewire\Partials\Navbar;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use DateTime;
use GuzzleHttp\Client;



#[Title('Rides - Duracabs')]
class RidesPage extends Component
{
    use WithPagination;
    use LivewireAlert;

   
    #[Url(history: true)]
    public $selected_categories = [];
    
    #[Url(history: true)]
    public $selected_brands = [];

    #[Url(history: true)]
    public $cityFrom ;
    

    #[Url(history: true)]
    public $cityTo ;

    #[Url(history: true)]
    public $nameTo ;

    #[Url(history: true)]
    public $tab = false ;

    #[Url(history: true)]
    public $days ;

    #[Url(history: true)]
    public $nameFrom ;

    #[Url(history: true)]
    public $date ;

 

    #[Url(history: true)]
    public $dateto ;

    #[Url(history: true)]
    public $plan ;

    #[Url(history: true)]
    public $cars ;

   

    public $query;

    #[Url(history: true)]
    public $price_range = 30000;

   

    #[Url]
    public $sort = 'latest';

    #[Url(history: true)]
    public $km ;

    #[Url(history: true)]
    public $kmValue ;

    #[Url(history: true)]
    public $time ;

    #[Url(history: true)]
    public $endTime ;

    #[Url(history: true)]
    public $timeValue ;

    public $query2 = null;
    public $brandsData = [];

    // Edit Query Popup Properties
    public $showEditModal = false;
    public $edit_ride_type = 'one_way';
    public $isLoading = false;
    public $edit_query_search = '';
    public $edit_query2_search = '';
    public $edit_queryLocal = '';
    public $edit_querySelfDrive = '';
    public $edit_queryFrom_search = '';
    public $edit_queryTo_search = '';
    public $edit_date = '';
    public $edit_dateto = '';
    public $edit_time = '';
    public $edit_endTime = '';
    public $edit_plan = '4 Hour / 40 Km';
    public $edit_cars = 1;
    public $edit_days = '';
    public $edit_km = '';
    public $edit_kmValue = '';
    public $edit_timeValue = '';
    
    // Auto-complete data for popup
    public $edit_cities_from = [];
    public $edit_cities_to = [];
    public $edit_dataFrom = [];
    public $edit_dataTo = [];
    
    // City IDs for edit modal
    public $edit_cityFrom_id = null;
    public $edit_cityTo_id = null;
    
    public $apiKey = 'AIzaSyCLWE-Pcf8ykbr4xIvgikDV1vc1hkhjm0A';

    

    

    // add product to cart method

    public function updatedQuery(){
        $this-> query = Product::query()->where('is_active',1);
    }

    public function updatedQuery2($query2)
    {
        $this->brandsData = Brand::where('name', 'like', '%' . $this->query2 . '%')->get();

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

    public function addToCartReturn($cat_id){
        $total_count = CartManagement::addItemsToCartReturn($cat_id);

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

    public function priceCalculate(){
       $day =  $this->days;
       $km =  $this->kmValue / 1000;

       return [];
    }

    // Edit Query Popup Methods
    public function showEditQueryModal() {
        $this->showEditModal = true;
        $this->edit_ride_type = $this->tab ?: 'one_way';
        
        // Pre-populate current values
        $this->edit_date = $this->date;
        $this->edit_dateto = $this->dateto;
        $this->edit_time = $this->time;
        $this->edit_endTime = $this->endTime;
        $this->edit_plan = $this->plan ?: '4 Hour / 40 Km';
        $this->edit_cars = $this->cars ?: 1;
        $this->edit_days = $this->days;
        $this->edit_km = $this->km;
        $this->edit_kmValue = $this->kmValue;
        $this->edit_timeValue = $this->timeValue;
        
        if ($this->edit_ride_type == 'one_way') {
            $this->edit_query_search = $this->nameTo;
            $this->edit_query2_search = $this->nameFrom;
            $this->edit_cityFrom_id = $this->cityFrom;
            $this->edit_cityTo_id = $this->cityTo;
        } elseif ($this->edit_ride_type == 'return') {
            $this->edit_queryFrom_search = $this->nameTo;
            $this->edit_queryTo_search = $this->nameFrom;
        } elseif ($this->edit_ride_type == 'local') {
            $this->edit_queryLocal = $this->nameTo;
            $this->edit_cityFrom_id = $this->cityFrom;
        } elseif ($this->edit_ride_type == 'self_drive') {
            $this->edit_querySelfDrive = $this->nameTo;
            $this->edit_cityFrom_id = $this->cityFrom;
        }
    }

    public function changeEditTab($value) {
        // Sync current values before switching
        $this->syncEditSearchValuesOnTabChange($value);
        $this->edit_ride_type = $value;
    }

    private function resetEditSearchFields() {
        // Clear dropdown data but keep search values
        $this->edit_cities_from = [];
        $this->edit_cities_to = [];
        $this->edit_dataFrom = [];
        $this->edit_dataTo = [];
    }

    /**
     * Sync search values when changing tabs in edit modal
     */
    private function syncEditSearchValuesOnTabChange($newTab) {
        $currentTab = $this->edit_ride_type;
        
        // Get current search values based on current tab
        $fromValue = $this->getCurrentEditFromValue($currentTab);
        $toValue = $this->getCurrentEditToValue($currentTab);
        $fromCityId = $this->getCurrentEditFromCityId($currentTab);
        $toCityId = $this->getCurrentEditToCityId($currentTab);
        
        // Set values for new tab if they exist and are compatible
        if ($fromValue) {
            if ($this->isValueCompatibleWithTab($fromValue, $newTab)) {
                $this->setEditFromValueForTab($newTab, $fromValue);
                $this->setEditFromSearchValueForTab($newTab, $fromValue);
            } else {
                // Clear the search field if the value won't work in the new tab
                $this->setEditFromSearchValueForTab($newTab, '');
            }
        }
        if ($toValue) {
            $this->setEditToValueForTab($newTab, $toValue);
            $this->setEditToSearchValueForTab($newTab, $toValue);
        }
        if ($fromCityId && $this->isValueCompatibleWithTab($fromValue, $newTab)) {
            $this->setEditFromCityIdForTab($newTab, $fromCityId);
        }
        if ($toCityId) {
            $this->setEditToCityIdForTab($newTab, $toCityId);
        }
    }

    /**
     * Get current edit "from" value based on tab
     */
    private function getCurrentEditFromValue($tab) {
        switch ($tab) {
            case 'one_way':
                return $this->edit_query_search;
            case 'return':
                return $this->edit_queryFrom_search;
            case 'local':
                return $this->edit_queryLocal;
            case 'self_drive':
                return $this->edit_querySelfDrive;
            default:
                return null;
        }
    }

    /**
     * Get current edit "to" value based on tab
     */
    private function getCurrentEditToValue($tab) {
        switch ($tab) {
            case 'one_way':
                return $this->edit_query2_search;
            case 'return':
                return $this->edit_queryTo_search;
            default:
                return null;
        }
    }

    /**
     * Set edit "from" value for specific tab
     */
    private function setEditFromValueForTab($tab, $value) {
        switch ($tab) {
            case 'one_way':
                $this->edit_query_search = $value;
                break;
            case 'return':
                $this->edit_queryFrom_search = $value;
                break;
            case 'local':
                $this->edit_queryLocal = $value;
                break;
            case 'self_drive':
                $this->edit_querySelfDrive = $value;
                break;
        }
    }

    /**
     * Set edit "to" value for specific tab
     */
    private function setEditToValueForTab($tab, $value) {
        switch ($tab) {
            case 'one_way':
                $this->edit_query2_search = $value;
                break;
            case 'return':
                $this->edit_queryTo_search = $value;
                break;
        }
    }

    /**
     * Get current edit "from" city ID based on tab
     */
    private function getCurrentEditFromCityId($tab) {
        switch ($tab) {
            case 'one_way':
                return $this->edit_cityFrom_id;
            case 'local':
                return $this->edit_cityFrom_id;
            case 'self_drive':
                return $this->edit_cityFrom_id;
            default:
                return null;
        }
    }

    /**
     * Get current edit "to" city ID based on tab
     */
    private function getCurrentEditToCityId($tab) {
        switch ($tab) {
            case 'one_way':
                return $this->edit_cityTo_id;
            default:
                return null;
        }
    }

    /**
     * Set edit "from" city ID for specific tab
     */
    private function setEditFromCityIdForTab($tab, $cityId) {
        switch ($tab) {
            case 'one_way':
                $this->edit_cityFrom_id = $cityId;
                break;
            case 'local':
                $this->edit_cityFrom_id = $cityId;
                break;
            case 'self_drive':
                $this->edit_cityFrom_id = $cityId;
                break;
        }
    }

    /**
     * Set edit "to" city ID for specific tab
     */
    private function setEditToCityIdForTab($tab, $cityId) {
        switch ($tab) {
            case 'one_way':
                $this->edit_cityTo_id = $cityId;
                break;
        }
    }

    /**
     * Set edit "from" search field value for specific tab (updates input field)
     */
    private function setEditFromSearchValueForTab($tab, $value) {
        switch ($tab) {
            case 'one_way':
                $this->edit_query_search = $value;
                break;
            case 'return':
                $this->edit_queryFrom_search = $value;
                break;
            case 'local':
                $this->edit_queryLocal = $value;
                break;
            case 'self_drive':
                $this->edit_querySelfDrive = $value;
                break;
        }
    }

    /**
     * Set edit "to" search field value for specific tab (updates input field)
     */
    private function setEditToSearchValueForTab($tab, $value) {
        switch ($tab) {
            case 'one_way':
                $this->edit_query2_search = $value;
                break;
            case 'return':
                $this->edit_queryTo_search = $value;
                break;
        }
    }

    /**
     * Check if a search value is compatible with a specific tab
     */
    private function isValueCompatibleWithTab($value, $tab) {
        if (empty($value)) {
            return true;
        }

        switch ($tab) {
            case 'local':
                // Check if there are local cities matching this value
                return Brand::where('name', 'like', '%' . $value . '%')
                    ->where('is_active', 1)
                    ->where('is_local', 1)
                    ->exists();
            
            case 'self_drive':
                // Check if there are self_drive cities matching this value
                return Brand::where('name', 'like', '%' . $value . '%')
                    ->where('is_active', 1)
                    ->where('is_selfdrive', 1)
                    ->exists();
            
            case 'one_way':
            case 'return':
            default:
                // One way and return accept all active cities
                return Brand::where('name', 'like', '%' . $value . '%')
                    ->where('is_active', 1)
                    ->exists();
        }
    }

    // One Way search handlers for popup
    public function updatedEditQuerySearch($query) {
        if (strlen($this->edit_query_search) >= 3) {
            $this->edit_cities_from = Brand::where('name', 'like', '%' . $this->edit_query_search . '%')
                ->where('is_active', 1)
                ->get();
        }
    }

    public function updatedEditQuery2Search($query2) {
        if (strlen($this->edit_query2_search) >= 3) {
            $this->edit_cities_to = Brand::where('name', 'like', '%' . $this->edit_query2_search . '%')
                ->where('is_active', 1)
                ->get();
        }
    }

    // Local search handler for popup
    public function updatedEditQueryLocal($queryLocal) {
        $this->edit_cities_from = Brand::where('name', 'like', '%' . $this->edit_queryLocal . '%')
            ->where('is_active', 1)
            ->where('is_local', 1)
            ->get();
    }

    // Self Drive search handler for popup
    public function updatedEditQuerySelfDrive($querySelfDrive) {
        $this->edit_cities_from = Brand::where('name', 'like', '%' . $this->edit_querySelfDrive . '%')
            ->where('is_active', 1)
            ->where('is_selfdrive', 1)
            ->get();
    }

    // Return trip Google Places API handlers for popup
    public function updatedEditQueryFromSearch($queryFrom) {
        $client = new Client();
        $apiUrl = "https://maps.googleapis.com/maps/api/place/queryautocomplete/json?input={$queryFrom}&key={$this->apiKey}";
        $response = $client->get($apiUrl);
        $data = json_decode($response->getBody(), true);
        $this->edit_dataFrom = $data['predictions'];
    }

    public function updatedEditQueryToSearch($queryTo) {
        $client = new Client();
        $apiUrl = "https://maps.googleapis.com/maps/api/place/queryautocomplete/json?input={$queryTo}&key={$this->apiKey}";
        $response = $client->get($apiUrl);
        $data = json_decode($response->getBody(), true);
        $this->edit_dataTo = $data['predictions'];
    }

    // City selection methods for popup
    public function editUpdateCityFrom($name) {
        $this->edit_queryFrom_search = $name;
        $this->edit_dataFrom = [];
    }

    public function editUpdateCityTo($name) {
        $this->edit_queryTo_search = $name;
        $this->edit_dataTo = [];
    }

    public function editUpdate1($name, $id) {
        $this->edit_query_search = $name;
        $this->edit_cityFrom_id = $id;
        $this->edit_cities_from = [];
    }

    public function editUpdate2($name, $id) {
        $this->edit_query2_search = $name;
        $this->edit_cityTo_id = $id;
        $this->edit_cities_to = [];
    }

    public function editUpdate3($name, $id) {
        $this->edit_queryLocal = $name;
        $this->edit_cityFrom_id = $id;
        $this->edit_cities_from = [];
    }

    public function editUpdate4($name, $id) {
        $this->edit_querySelfDrive = $name;
        $this->edit_cityFrom_id = $id;
        $this->edit_cities_from = [];
    }

    public function updateQuery() {
        // Set loading state
        $this->isLoading = true;
        
        try {
            // Validation based on ride type
            $this->validate($this->getEditValidationRules());

            // Build the redirect URL with updated parameters
            $params = $this->buildEditRedirectParams();
            
            // Add a small delay for better UX (optional)
            usleep(500000); // 0.5 seconds
            
            $this->showEditModal = false;
            $this->isLoading = false;
            
            return redirect()->to(route('rides') . '?' . http_build_query($params));
        } catch (\Exception $e) {
            $this->isLoading = false;
            throw $e;
        }
    }

    private function getEditValidationRules() {
        $baseRules = [
            'edit_date' => 'required|date|after_or_equal:today',
            'edit_time' => 'required',
        ];

        switch ($this->edit_ride_type) {
            case 'one_way':
                return array_merge($baseRules, [
                    'edit_query_search' => 'required',
                    'edit_query2_search' => 'required',
                ]);
            case 'return':
                return array_merge($baseRules, [
                    'edit_queryFrom_search' => 'required',
                    'edit_queryTo_search' => 'required',
                    'edit_dateto' => 'required|date|after:edit_date',
                ]);
            case 'local':
                return array_merge($baseRules, [
                    'edit_queryLocal' => 'required',
                    'edit_plan' => 'required',
                    'edit_cars' => 'required|integer|min:1',
                ]);
            case 'self_drive':
                return array_merge($baseRules, [
                    'edit_querySelfDrive' => 'required',
                    'edit_dateto' => 'required|date|after_or_equal:edit_date',
                    'edit_endTime' => 'required',
                ]);
            default:
                return $baseRules;
        }
    }

    private function buildEditRedirectParams() {
        $params = [
            'tab' => $this->edit_ride_type,
            'date' => $this->edit_date,
            'time' => $this->edit_time,
        ];

        switch ($this->edit_ride_type) {
            case 'one_way':
                $params['cityFrom'] = $this->edit_cityFrom_id;
                $params['cityTo'] = $this->edit_cityTo_id;
                $params['nameTo'] = $this->edit_query_search;
                $params['nameFrom'] = $this->edit_query2_search;
                break;
            case 'return':
                $params['nameTo'] = $this->edit_queryFrom_search;
                $params['cityFrom'] = $this->edit_queryTo_search;
                $params['dateto'] = $this->edit_dateto;
                $params['km'] = $this->edit_km;
                $params['kmValue'] = $this->edit_kmValue;
                $params['timeValue'] = $this->edit_timeValue;
                $params['days'] = $this->edit_days;
                break;
            case 'local':
                $params['cityFrom'] = $this->edit_cityFrom_id;
                $params['nameTo'] = $this->edit_queryLocal;
                $params['plan'] = $this->edit_plan;
                $params['cars'] = $this->edit_cars;
                break;
            case 'self_drive':
                $params['cityFrom'] = $this->edit_cityFrom_id;
                $params['nameTo'] = $this->edit_querySelfDrive;
                $params['dateto'] = $this->edit_dateto;
                $params['endTime'] = $this->edit_endTime;
                $params['days'] = $this->edit_days;
                break;
        }

        return $params;
    }

    public function render()
    {
      
        $ridesQuery = Product::query()->where('is_active',1);
       

        if(!empty($this->selected_categories)){
            $ridesQuery->where('category_id', $this->selected_categories);
        }

        if(!empty($this->selected_brands)){
            $ridesQuery->where('brand_id', $this->selected_brands)->orWhere('booking_to', $this->selected_brands);
        }   

        if(!empty($this->cityFrom)){
            $ridesQuery->where('brand_id', $this->cityFrom);
        }

        if(!empty($this->cityTo)){
            $ridesQuery->where('booking_to', $this->cityTo);
        }

      

        if($this->price_range){
            $ridesQuery->whereBetween('price', [0,$this->price_range]);
        }

        if($this->sort == 'latest'){
            $ridesQuery->latest();
        }

        if($this->sort == 'price'){
            $ridesQuery->orderBy('price');
        }

        if(!empty($this->tab)){
            $ridesQuery->where('ride_type', $this->tab);
        }
            
        if(!empty($this->tab == "local")){
             $ridesQuery->where('plan', $this->plan);
        }

        $brandsData = Brand::where('is_active',1)->get(['id','name','slug']);

        $brandFilter = $this->query2? $this->brandsData : $brandsData ;


        $ridesQuery->with(['prices']);

     
     

        return view('livewire.rides-page',[
            'rides'=> $ridesQuery->paginate(perPage: 9),
           
            'brands' =>  $brandFilter,
            'categories' => Category::where('is_active',1)->get(['id','name','slug','image','new_vehicle','pet_friendly','roof_career']),
            'categories2' => Category::where('in_return',1)->get(['id','name','slug','image','km_charge','driver_charge','range','new_vehicle','pet_friendly','roof_career']),
            'newTime'=> DateTime::createFromFormat('H:i', $this->time),
            'timeEnd'=> DateTime::createFromFormat('H:i', $this->endTime)
        ]);
    }
}
