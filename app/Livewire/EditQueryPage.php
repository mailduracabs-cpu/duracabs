<?php

namespace App\Livewire;

use App\Models\Brand;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

#[Title('Edit Query - Duracabs')]
class EditQueryPage extends Component
{
    // URL parameters for maintaining state
    #[Url(history: true)]
    public $ride_type = 'one_way';
    
    #[Url(history: true)]
    public $cityFrom;
    
    #[Url(history: true)]
    public $cityTo;
    
    #[Url(history: true)]
    public $nameTo;
    
    #[Url(history: true)]
    public $nameFrom;
    
    #[Url(history: true)]
    public $date;
    
    #[Url(history: true)]
    public $dateto;
    
    #[Url(history: true)]
    public $time;
    
    #[Url(history: true)]
    public $endTime;
    
    #[Url(history: true)]
    public $plan = '4 Hour / 40 Km';
    
    #[Url(history: true)]
    public $cars = 1;
    
    #[Url(history: true)]
    public $days;
    
    #[Url(history: true)]
    public $km;
    
    #[Url(history: true)]
    public $kmValue;
    
    #[Url(history: true)]
    public $timeValue;

    // Search properties
    public $query_search;
    public $query_id;
    public $query2_search;
    public $query2_id;
    public $queryLocal;
    public $querySelfDrive;
    public $queryFrom_search;
    public $queryTo_search;
    public $queryFrom;
    public $queryTo;

    // Auto-complete data
    public $cities_from = [];
    public $cities_to = [];
    public $dataFrom = [];
    public $dataTo = [];


    public function mount()
    {
        // Handle tab parameter from URL (maps to ride_type)
        if (request()->has('tab')) {
            $this->ride_type = request()->get('tab');
        }
        
        // Pre-populate search fields with existing values
        if ($this->ride_type == 'one_way') {
            $this->query_search = $this->nameTo;
            $this->query2_search = $this->nameFrom;
        } elseif ($this->ride_type == 'return') {
            $this->queryFrom_search = $this->nameTo;
            $this->queryTo_search = $this->nameFrom;
        } elseif ($this->ride_type == 'local') {
            $this->queryLocal = $this->nameTo;
        } elseif ($this->ride_type == 'self_drive') {
            $this->querySelfDrive = $this->nameTo;
        }
    }

    // One Way search handlers
    public function updatedQuerySearch($query)
    {
        if (strlen($this->query_search) >= 3) {
            $term = trim((string) $this->query_search);
            $this->cities_from = Brand::query()
                ->select(['id', 'name'])
                ->where('name', 'like', '%' . addcslashes($term, '%_') . '%')
                ->where('is_active', 1)
                ->orderBy('name')
                ->limit(10)
                ->get()
                ->all();
        }
    }

    public function updatedQuery2Search($query2)
    {
        if (strlen($this->query2_search) >= 3) {
            $term = trim((string) $this->query2_search);
            $this->cities_to = Brand::query()
                ->select(['id', 'name'])
                ->where('name', 'like', '%' . addcslashes($term, '%_') . '%')
                ->where('is_active', 1)
                ->orderBy('name')
                ->limit(10)
                ->get()
                ->all();
        }
    }

    // Local search handler
    public function updatedQueryLocal($queryLocal)
    {
        $term = trim((string) $this->queryLocal);
        if (mb_strlen($term) < 2) {
            $this->cities_from = [];
            return;
        }

        $this->cities_from = Brand::query()
            ->select(['id', 'name'])
            ->where('name', 'like', '%' . addcslashes($term, '%_') . '%')
            ->where('is_active', 1)
            ->where('is_local', 1)
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->all();
    }

    // Self Drive search handler
    public function updatedQuerySelfDrive($querySelfDrive)
    {
        $term = trim((string) $this->querySelfDrive);
        if (mb_strlen($term) < 2) {
            $this->cities_from = [];
            return;
        }

        $this->cities_from = Brand::query()
            ->select(['id', 'name'])
            ->where('name', 'like', '%' . addcslashes($term, '%_') . '%')
            ->where('is_active', 1)
            ->where('is_selfdrive', 1)
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->all();
    }

    // Return trip Google Places API handlers
    public function updatedQueryFromSearch($queryFrom)
    {
        $this->dataFrom = $this->googlePlaceSuggestions((string) $queryFrom);
    }

    public function updatedQueryToSearch($queryTo)
    {
        $this->dataTo = $this->googlePlaceSuggestions((string) $queryTo);
    }


    private function googlePlaceSuggestions(string $query): array
    {
        $query = trim($query);

        if (mb_strlen($query) < 3) {
            return [];
        }

        $apiKey = (string) config('services.google.maps_api_key', env('GOOGLE_MAPS_API_KEY'));

        if ($apiKey === '') {
            return [];
        }

        return Cache::remember(
            'google.places.query.' . sha1(mb_strtolower($query)),
            now()->addMinutes(5),
            function () use ($query, $apiKey): array {
                try {
                    $response = Http::timeout(6)
                        ->connectTimeout(3)
                        ->retry(1, 150)
                        ->get('https://maps.googleapis.com/maps/api/place/queryautocomplete/json', [
                            'input' => $query,
                            'key' => $apiKey,
                        ]);

                    if (!$response->successful()) {
                        return [];
                    }

                    return array_slice($response->json('predictions', []), 0, 8);
                } catch (\Throwable $e) {
                    report($e);
                    return [];
                }
            }
        );
    }

    // City selection methods
    public function updateCityFrom($name)
    {
        $this->queryFrom = $name;
        $this->queryFrom_search = $name;
        $this->dataFrom = [];
    }

    public function updateCityTo($name)
    {
        $this->queryTo = $name;
        $this->queryTo_search = $name;
        $this->dataTo = [];
    }

    public function update1($name, $id)
    {
        $this->nameTo = $name;
        $this->query_search = $name;
        $this->cityFrom = $id;
        $this->cities_from = [];
    }

    public function update2($name, $id)
    {
        $this->nameFrom = $name;
        $this->query2_search = $name;
        $this->cityTo = $id;
        $this->cities_to = [];
    }

    public function update3($name, $id)
    {
        $this->nameTo = $name;
        $this->queryLocal = $name;
        $this->cityFrom = $id;
        $this->cities_from = [];
    }

    public function update4($name, $id)
    {
        $this->nameTo = $name;
        $this->querySelfDrive = $name;
        $this->cityFrom = $id;
        $this->cities_from = [];
    }

    public function changeTab($value)
    {
        $this->ride_type = $value;
        // Clear search fields when changing tabs
        $this->resetSearchFields();
    }

    private function resetSearchFields()
    {
        $this->query_search = '';
        $this->query2_search = '';
        $this->queryLocal = '';
        $this->querySelfDrive = '';
        $this->queryFrom_search = '';
        $this->queryTo_search = '';
        $this->cities_from = [];
        $this->cities_to = [];
        $this->dataFrom = [];
        $this->dataTo = [];
    }

    public function updateQuery()
    {
        // Validation based on ride type
        $this->validate($this->getValidationRules());

        // Build the redirect URL with updated parameters
        $params = $this->buildRedirectParams();
        
        return redirect()->to(route('rides') . '?' . http_build_query($params));
    }

    private function getValidationRules()
    {
        $baseRules = [
            'date' => 'required|date|after_or_equal:today',
            'time' => 'required',
        ];

        switch ($this->ride_type) {
            case 'one_way':
                return array_merge($baseRules, [
                    'query_search' => 'required',
                    'query2_search' => 'required',
                ]);
            case 'return':
                return array_merge($baseRules, [
                    'queryFrom_search' => 'required',
                    'queryTo_search' => 'required',
                    'dateto' => 'required|date|after:date',
                ]);
            case 'local':
                return array_merge($baseRules, [
                    'queryLocal' => 'required',
                    'plan' => 'required',
                    'cars' => 'required|integer|min:1',
                ]);
            case 'self_drive':
                return array_merge($baseRules, [
                    'querySelfDrive' => 'required',
                    'dateto' => 'required|date|after_or_equal:date',
                    'endTime' => 'required',
                ]);
            default:
                return $baseRules;
        }
    }

    private function buildRedirectParams()
    {
        $params = [
            'tab' => $this->ride_type,
            'date' => $this->date,
            'time' => $this->time,
        ];

        switch ($this->ride_type) {
            case 'one_way':
                $params['cityFrom'] = $this->cityFrom;
                $params['cityTo'] = $this->cityTo;
                $params['nameTo'] = $this->nameTo;
                $params['nameFrom'] = $this->nameFrom;
                break;
            case 'return':
                $params['nameTo'] = $this->queryFrom;
                $params['cityFrom'] = $this->queryTo;
                $params['dateto'] = $this->dateto;
                $params['km'] = $this->km;
                $params['kmValue'] = $this->kmValue;
                $params['timeValue'] = $this->timeValue;
                $params['days'] = $this->days;
                break;
            case 'local':
                $params['cityFrom'] = $this->cityFrom;
                $params['nameTo'] = $this->nameTo;
                $params['plan'] = $this->plan;
                $params['cars'] = $this->cars;
                break;
            case 'self_drive':
                $params['cityFrom'] = $this->cityFrom;
                $params['nameTo'] = $this->nameTo;
                $params['dateto'] = $this->dateto;
                $params['endTime'] = $this->endTime;
                $params['days'] = $this->days;
                break;
        }

        return $params;
    }

    public function render()
    {
        return view('livewire.edit-query-page');
    }
}
