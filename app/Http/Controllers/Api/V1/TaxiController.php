<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\FareEstimateRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Price;
use App\Services\FareService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TaxiController extends Controller
{
    private function success($data = null, string $message = 'Success', int $code = 200)
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    private function error(string $message = 'Something went wrong', int $code = 400, $errors = null)
    {
        $response = [
            'status' => false,
            'message' => $message,
        ];

        if (!is_null($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    public function home()
    {
        try {
            $categories = Category::query()
                ->where('is_active', 1)
                ->select([
                    'id',
                    'name',
                    'slug',
                    'image',
                    'model',
                    'passanger_capacity',
                    'luggage_capacity',
                    'km_charge',
                    'driver_charge',
                    'range',
                    'in_return',
                    'security',
                    'new_vehicle',
                    'roof_career',
                    'pet_friendly',
                ])
                ->orderBy('id')
                ->limit(12)
                ->get();

            $popularRoutes = Product::query()
                ->with('category:id,name,slug,model,passanger_capacity,luggage_capacity')
                ->where('is_active', 1)
                ->where('in_stock', 1)
                ->select([
                    'id',
                    'category_id',
                    'name',
                    'slug',
                    'ride_type',
                    'price',
                    'max_price',
                    'km_limit',
                    'hr_limit',
                    'extra_km_charge',
                    'extra_hr_charge',
                    'toll_tax',
                    'border_tax',
                    'driver_allowances',
                    'images',
                    'plan',
                ])
                ->latest('id')
                ->limit(12)
                ->get()
                ->map(fn ($route) => $this->formatRoute($route));

            return $this->success([
                'cities' => $this->buildCitiesFromRoutes(),
                'vehicle_categories' => $categories,
                'popular_routes' => $popularRoutes,
                'trip_types' => [
                    ['key' => 'one_way', 'name' => 'One Way'],
                    ['key' => 'round_trip', 'name' => 'Round Trip'],
                    ['key' => 'local', 'name' => 'Local Rental'],
                    ['key' => 'airport', 'name' => 'Airport Transfer'],
                ],
            ], 'Taxi home data loaded');
        } catch (\Throwable $e) {
            Log::error('Taxi home API error', ['error' => $e->getMessage(), 'line' => $e->getLine()]);
            return $this->error('Unable to load taxi data', 500);
        }
    }

    public function routes(Request $request)
    {
        try {
            $query = Product::query()
                ->with('category:id,name,slug,model,passanger_capacity,luggage_capacity')
                ->where('is_active', 1)
                ->where('in_stock', 1)
                ->select([
                    'id',
                    'category_id',
                    'name',
                    'slug',
                    'ride_type',
                    'price',
                    'max_price',
                    'km_limit',
                    'hr_limit',
                    'extra_km_charge',
                    'extra_hr_charge',
                    'toll_tax',
                    'border_tax',
                    'driver_allowances',
                    'images',
                    'plan',
                ]);

            if ($request->filled('ride_type')) {
                $query->where('ride_type', $request->ride_type);
            }

            if ($request->filled('from')) {
                $fromTerms = $this->extractSearchTerms($request->from);
                $query->where(function ($sub) use ($fromTerms) {
                    foreach ($fromTerms as $term) {
                        $sub->orWhere('name', 'LIKE', '%' . $term . '%')
                            ->orWhere('slug', 'LIKE', '%' . Str::slug($term) . '%');
                    }
                });
            }

            if ($request->filled('to')) {
                $toTerms = $this->extractSearchTerms($request->to);
                $query->where(function ($sub) use ($toTerms) {
                    foreach ($toTerms as $term) {
                        $sub->orWhere('name', 'LIKE', '%' . $term . '%')
                            ->orWhere('slug', 'LIKE', '%' . Str::slug($term) . '%');
                    }
                });
            }

            if ($request->filled('q')) {
                $q = trim((string) $request->q);
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'LIKE', "%{$q}%")
                        ->orWhere('slug', 'LIKE', '%' . Str::slug($q) . '%');
                });
            }

            $limit = min((int) $request->get('limit', 20), 100);
            $routes = $query->latest('id')->paginate($limit);

            $routes->getCollection()->transform(fn ($route) => $this->formatRoute($route));

            return $this->success($routes, 'Routes loaded');
        } catch (\Throwable $e) {
            Log::error('Routes API error', ['error' => $e->getMessage(), 'line' => $e->getLine()]);
            return $this->error('Unable to load routes', 500);
        }
    }

    public function search(Request $request)
    {
        try {
            $request->validate([
                'from' => 'nullable|string|max:255',
                'to' => 'nullable|string|max:255',
                'q' => 'nullable|string|max:255',
            ]);

            $from = trim((string) $request->get('from', ''));
            $to = trim((string) $request->get('to', ''));
            $q = trim((string) $request->get('q', ''));

            $fromTerms = $this->extractSearchTerms($from);
            $toTerms = $this->extractSearchTerms($to);
            $qTerms = $this->extractSearchTerms($q);

            $routes = Product::query()
                ->with('category:id,name,slug,model,passanger_capacity,luggage_capacity')
                ->where('is_active', 1)
                ->where('in_stock', 1)
                ->select([
                    'id',
                    'category_id',
                    'name',
                    'slug',
                    'ride_type',
                    'price',
                    'max_price',
                    'km_limit',
                    'hr_limit',
                    'extra_km_charge',
                    'extra_hr_charge',
                    'toll_tax',
                    'border_tax',
                    'driver_allowances',
                    'images',
                    'plan',
                ])
                ->orderBy('price')
                ->limit(1000)
                ->get();

            $matchedRoute = $routes->first(function ($route) use ($fromTerms, $toTerms, $qTerms) {
                return $this->routeMatches($route, $fromTerms, $toTerms, $qTerms);
            });

            if (!$matchedRoute) {
                return $this->success([], 'Route search completed');
            }

            $prices = Price::query()
                ->with('category:id,name,slug,model,passanger_capacity,luggage_capacity')
                ->where('product_id', $matchedRoute->id)
                ->orderBy('price')
                ->get();

            if ($prices->isEmpty()) {
                return $this->success([
                    $this->formatRoute($matchedRoute),
                ], 'Route search completed');
            }

            $vehicles = $prices->map(function ($price) use ($matchedRoute) {
                return $this->formatPriceVehicle($matchedRoute, $price);
            })->values();

            return $this->success($vehicles, 'Route search completed');
        } catch (\Throwable $e) {
            Log::error('Route search API error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'from' => $request->get('from'),
                'to' => $request->get('to'),
                'q' => $request->get('q'),
            ]);

            return $this->error('Unable to search routes', 500);
        }
    }

    public function fareEstimate(FareEstimateRequest $request, FareService $fareService)
    {
        try {
            $estimate = $fareService->estimate($request->validated());
            return $this->success($estimate, 'Fare estimated successfully');
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            Log::error('Fare estimate API error', ['error' => $e->getMessage(), 'line' => $e->getLine()]);
            return $this->error('Unable to estimate fare', 500);
        }
    }

    private function routeMatches($route, array $fromTerms, array $toTerms, array $qTerms = []): bool
    {
        [$routeFrom, $routeTo] = $this->splitRouteName($route->name);

        $nameText = $this->normalizeText(($route->name ?? '') . ' ' . ($route->slug ?? ''));
        $fromText = $this->normalizeText($routeFrom);
        $toText = $this->normalizeText($routeTo);

        $fromOk = empty($fromTerms) || $this->containsAnyTerm($fromText, $fromTerms) || $this->containsAnyTerm($nameText, $fromTerms);
        $toOk = empty($toTerms) || $this->containsAnyTerm($toText, $toTerms) || $this->containsAnyTerm($nameText, $toTerms);
        $qOk = empty($qTerms) || $this->containsAnyTerm($nameText, $qTerms);

        return $fromOk && $toOk && $qOk;
    }

    private function extractSearchTerms(?string $value): array
    {
        $value = trim((string) $value);
        if ($value === '') {
            return [];
        }

        $normalized = $this->normalizeText($value);
        $parts = preg_split('/[,|\-]+/', $value);
        $terms = [];

        $firstPart = trim((string) ($parts[0] ?? ''));
        if ($firstPart !== '') {
            $terms[] = $firstPart;
        }

        $wordsToCheck = [
            'agra', 'delhi', 'new delhi', 'noida', 'gurgaon', 'gurugram', 'jaipur', 'lucknow',
            'mathura', 'vrindavan', 'gwalior', 'haridwar', 'rishikesh', 'chandigarh', 'meerut',
            'faridabad', 'ghaziabad', 'airport', 'igi', 'terminal 1', 'terminal 2', 'terminal 3',
            't1', 't2', 't3'
        ];

        foreach ($wordsToCheck as $word) {
            if (str_contains($normalized, $this->normalizeText($word))) {
                $terms[] = $word;
            }
        }

        if (str_contains($normalized, 'igi') || str_contains($normalized, 'airport') || str_contains($normalized, 'terminal')) {
            $terms[] = 'Delhi';
            $terms[] = 'New Delhi';
            $terms[] = 'IGI Airport';
        }

        $terms[] = $value;

        $cleanTerms = [];
        foreach ($terms as $term) {
            $term = trim((string) $term);
            if ($term === '') {
                continue;
            }
            $term = preg_replace('/\s+/', ' ', $term);
            if (strlen($term) >= 2) {
                $cleanTerms[$this->normalizeText($term)] = $term;
            }
        }

        return array_values($cleanTerms);
    }

    private function containsAnyTerm(string $haystack, array $terms): bool
    {
        $haystack = $this->normalizeText($haystack);

        foreach ($terms as $term) {
            $term = $this->normalizeText($term);
            if ($term !== '' && str_contains($haystack, $term)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeText(?string $value): string
    {
        $value = strtolower((string) $value);
        $value = str_replace(['&', '/', '\\', '_'], ' ', $value);
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value);
        return trim(preg_replace('/\s+/', ' ', $value));
    }

    private function formatPriceVehicle($route, $price): array
    {
        [$fromCity, $toCity] = $this->splitRouteName($route->name);

        return [
            'id' => $route->id . '-' . $price->category_id,
            'product_id' => $route->id,
            'route_id' => $route->id,
            'price_id' => $price->id ?? null,
            'category_id' => $price->category_id,
            'category' => $price->category ? [
                'id' => $price->category->id,
                'name' => $price->category->name,
                'slug' => $price->category->slug,
                'model' => $price->category->model,
                'passenger_capacity' => $price->category->passanger_capacity,
                'luggage_capacity' => $price->category->luggage_capacity,
            ] : null,
            'name' => $route->name,
            'slug' => $route->slug,
            'from_city' => $fromCity,
            'to_city' => $toCity,
            'ride_type' => $route->ride_type,
            'price' => (float) $price->price,
            'max_price' => (float) $price->max_price,
            'km_limit' => (float) $route->km_limit,
            'hr_limit' => (float) $route->hr_limit,
            'extra_km_charge' => (float) $route->extra_km_charge,
            'extra_hr_charge' => (float) $route->extra_hr_charge,
            'toll_tax' => (float) $route->toll_tax,
            'border_tax' => (float) $route->border_tax,
            'driver_allowances' => (float) $route->driver_allowances,
            'plan' => $route->plan,
            'image' => $this->firstImage($route->images),
        ];
    }

    private function formatRoute($route): array
    {
        [$fromCity, $toCity] = $this->splitRouteName($route->name);

        return [
            'id' => $route->id,
            'product_id' => $route->id,
            'route_id' => $route->id,
            'category_id' => $route->category_id,
            'category' => $route->category ? [
                'id' => $route->category->id,
                'name' => $route->category->name,
                'slug' => $route->category->slug,
                'model' => $route->category->model,
                'passenger_capacity' => $route->category->passanger_capacity,
                'luggage_capacity' => $route->category->luggage_capacity,
            ] : null,
            'name' => $route->name,
            'slug' => $route->slug,
            'from_city' => $fromCity,
            'to_city' => $toCity,
            'ride_type' => $route->ride_type,
            'price' => (float) $route->price,
            'max_price' => (float) $route->max_price,
            'km_limit' => (float) $route->km_limit,
            'hr_limit' => (float) $route->hr_limit,
            'extra_km_charge' => (float) $route->extra_km_charge,
            'extra_hr_charge' => (float) $route->extra_hr_charge,
            'toll_tax' => (float) $route->toll_tax,
            'border_tax' => (float) $route->border_tax,
            'driver_allowances' => (float) $route->driver_allowances,
            'plan' => $route->plan,
            'image' => $this->firstImage($route->images),
        ];
    }

    private function splitRouteName(?string $name): array
    {
        $name = (string) $name;
        $parts = preg_split('/\s+To\s+/i', $name);

        return [
            trim($parts[0] ?? ''),
            trim($parts[1] ?? ''),
        ];
    }

    private function firstImage($images): ?string
    {
        if (is_string($images)) {
            $decoded = json_decode($images, true);
            $images = is_array($decoded) ? $decoded : [];
        }

        if (is_array($images) && count($images) > 0) {
            $image = $images[0];
            return $image ? url('storage/' . ltrim($image, '/')) : null;
        }

        return null;
    }

    private function buildCitiesFromRoutes(): array
    {
        $names = Product::query()
            ->where('is_active', 1)
            ->whereNotNull('name')
            ->limit(1000)
            ->pluck('name')
            ->toArray();

        $cities = [];

        foreach ($names as $name) {
            [$from, $to] = $this->splitRouteName($name);

            foreach ([$from, $to] as $city) {
                $city = trim($city);
                if ($city === '') {
                    continue;
                }

                $key = Str::slug($city);
                $cities[$key] = [
                    'id' => $key,
                    'name' => $city,
                    'slug' => $key,
                ];
            }
        }

        return array_values($cities);
    }
}
