<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\SmartHomeBlock;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class SmartBannerService
{
    private const CACHE_KEY = 'smart_home.sections';

    private const CACHE_TTL_SECONDS = 300;

    /**
     * Return all Smart Home sections grouped by block type.
     */
    public function getSections(): array
    {
        return Cache::remember(
            self::CACHE_KEY,
            self::CACHE_TTL_SECONDS,
            fn (): array => $this->buildSections()
        );
    }

    /**
     * Return blocks of a particular homepage section.
     */
    public function getSection(string $blockType): array
    {
        $sections = $this->getSections();

        return $sections[$this->normalizeBlockType($blockType)] ?? [];
    }

    /**
     * Clear Smart Home cached data.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Build all homepage sections from active CMS blocks.
     */
    private function buildSections(): array
    {
        try {
            $blocks = SmartHomeBlock::query()
                ->where('is_active', true)
                ->where(function ($query): void {
                    $query
                        ->whereNull('starts_at')
                        ->orWhere('starts_at', '<=', now());
                })
                ->where(function ($query): void {
                    $query
                        ->whereNull('ends_at')
                        ->orWhere('ends_at', '>=', now());
                })
                ->orderBy('priority')
                ->orderByDesc('id')
                ->get();

            if ($blocks->isEmpty()) {
                return $this->emptySections();
            }

            $cityNames = $this->loadCityNames($blocks);

            $resolvedBlocks = $blocks
                ->map(
                    fn (SmartHomeBlock $block): array => $this->resolveBlock(
                        $block,
                        $cityNames
                    )
                )
                ->filter(fn (array $block): bool => ! empty($block))
                ->values();

            $grouped = $resolvedBlocks
                ->groupBy('block_type')
                ->map(fn (Collection $items): array => $items->values()->all())
                ->all();

            return array_merge($this->emptySections(), $grouped);
        } catch (Throwable $exception) {
            report($exception);

            return $this->emptySections();
        }
    }

    /**
     * Convert a database block into homepage-ready data.
     */
    private function resolveBlock(
        SmartHomeBlock $block,
        Collection $cityNames
    ): array {
        $serviceType = $this->normalizeServiceType(
            (string) $block->service_type
        );

        $blockType = $this->normalizeBlockType(
            (string) $block->block_type
        );

        $fromCity = $this->cityName(
            $block->from_city_id,
            $cityNames
        );

        $toCity = $this->cityName(
            $block->to_city_id,
            $cityNames
        );

        $routeProduct = $this->findRouteProduct(
            $serviceType,
            $block->from_city_id,
            $block->to_city_id
        );

        $selfDriveVehicle = $serviceType === Vehicle::SERVICE_SELF_DRIVE
            ? $this->findSelfDriveVehicle($fromCity)
            : null;

        $fareData = $selfDriveVehicle
            ? $this->resolveSelfDriveVehicleData($selfDriveVehicle)
            : $this->resolveFareData($routeProduct);

        $theme = $this->themeFor($serviceType);

        return [
            'id' => $block->id,
            'block_type' => $blockType,
            'service_type' => $serviceType,

            'title' => $this->resolveTitle(
                $block,
                $fromCity,
                $toCity
            ),

            'subtitle' => $this->resolveSubtitle(
                $block,
                $fromCity,
                $toCity
            ),

            'route' => [
                'product_id' => $routeProduct?->getKey(),
                'vehicle_id' => $selfDriveVehicle?->getKey(),
                'slug' => $routeProduct?->slug,
                'from_city_id' => $block->from_city_id
                    ? (int) $block->from_city_id
                    : null,
                'from_city' => $fromCity,
                'to_city_id' => $block->to_city_id
                    ? (int) $block->to_city_id
                    : null,
                'to_city' => $toCity,
                'label' => $this->routeLabel($fromCity, $toCity),
            ],

            // Starting price is taken from the route product's vehicle prices.
            'fare' => $fareData['fare'],
            'formatted_fare' => $fareData['formatted_fare'],
            'vehicle' => $fareData['vehicle'],
            'vehicle_image' => $fareData['vehicle_image'],

            'theme' => $theme,
            'priority' => (int) $block->priority,
            'is_dynamic' => (bool) $block->is_dynamic,

            'action' => $selfDriveVehicle
                ? $this->actionForSelfDriveVehicle(
                    $selfDriveVehicle,
                    $block->from_city_id
                )
                : $this->actionFor(
                    $routeProduct,
                    $serviceType,
                    $block->from_city_id,
                    $block->to_city_id
                ),
        ];
    }

    /**
     * Find the newest bookable self-drive car for the selected city.
     *
     * Smart Home blocks store a brand/city ID, while vehicles inherit their
     * city from the linked transporter profile. The resolved city name is
     * therefore matched against fleet_transporter_profiles.city.
     */
    private function findSelfDriveVehicle(?string $city): ?Vehicle
    {
        $baseQuery = Vehicle::query()
            ->with(['frontMedia', 'transporter'])
            ->availableForRental()
            ->selfDrive()
            ->cars()
            ->where('daily_price', '>', 0);

        if (filled($city)) {
            $normalizedCity = strtolower(trim((string) $city));

            $cityVehicle = (clone $baseQuery)
                ->whereHas('transporter', function (Builder $query) use ($normalizedCity): void {
                    $query->whereRaw(
                        'LOWER(TRIM(city)) = ?',
                        [$normalizedCity]
                    );
                })
                ->latest('id')
                ->first();

            if ($cityVehicle) {
                return $cityVehicle;
            }
        }

        return $baseQuery
            ->latest('id')
            ->first();
    }

    /**
     * Convert an actual rental vehicle into the same normalized payload used
     * by route-product banners.
     */
    private function resolveSelfDriveVehicleData(Vehicle $vehicle): array
    {
        $fare = round($vehicle->getDailyRate(), 2);

        if ($fare <= 0) {
            return $this->emptyFareData();
        }

        return [
            'fare' => $fare,
            'formatted_fare' => 'Starting from ₹'
                . $this->formatAmount($fare)
                . ' / 24 Hours',
            'vehicle' => $vehicle->display_name,
            'vehicle_image' => $vehicle->front_image_url,
        ];
    }

    /**
     * Find the exact route product represented by a Smart Home block.
     */
    private function findRouteProduct(
        string $serviceType,
        mixed $fromCityId,
        mixed $toCityId
    ): ?Product {
        if (empty($fromCityId)) {
            return null;
        }

        $query = Product::query()
            ->with(['prices.category', 'brand'])
            ->where('is_active', 1)
            ->where('brand_id', (int) $fromCityId);

        $this->applyRideTypeFilter($query, $serviceType);

        if ($this->requiresDestination($serviceType)) {
            if (empty($toCityId)) {
                return null;
            }

            $query->where('booking_to', (int) $toCityId);
        }

        return $query
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Keep compatibility with the ride_type values already used by the website.
     */
    private function applyRideTypeFilter(
        Builder $query,
        string $serviceType
    ): void {
        $rideTypes = match ($serviceType) {
            'round_trip' => ['round_trip', 'return'],
            'self_drive' => ['self_drive', 'selfdrive'],
            'bike_rental' => ['bike_rental', 'bike'],
            default => [$serviceType],
        };

        $query->whereIn('ride_type', $rideTypes);
    }

    private function requiresDestination(string $serviceType): bool
    {
        return in_array(
            $serviceType,
            ['one_way', 'round_trip', 'airport'],
            true
        );
    }

    /**
     * Resolve the lowest active vehicle/category fare for the route.
     */
    private function resolveFareData(?Product $routeProduct): array
    {
        if (! $routeProduct) {
            return $this->emptyFareData();
        }

        $lowestPriceRow = collect($routeProduct->prices ?? [])
            ->filter(
                fn ($price): bool => is_numeric($price->price ?? null)
                    && (float) $price->price > 0
            )
            ->sortBy(fn ($price): float => (float) $price->price)
            ->first();

        $fare = $lowestPriceRow
            ? round((float) $lowestPriceRow->price, 2)
            : $this->fallbackProductFare($routeProduct);

        if ($fare === null || $fare <= 0) {
            return $this->emptyFareData();
        }

        $category = $lowestPriceRow?->category;

        return [
            'fare' => $fare,
            'formatted_fare' => 'Starting from ₹' . $this->formatAmount($fare),
            'vehicle' => filled($category?->name)
                ? trim((string) $category->name)
                : null,
            'vehicle_image' => $this->resolveVehicleImage(
                $category,
                $routeProduct
            ),
        ];
    }

    private function fallbackProductFare(Product $routeProduct): ?float
    {
        $price = (float) ($routeProduct->price ?? 0);

        return $price > 0 ? round($price, 2) : null;
    }

    private function formatAmount(float $amount): string
    {
        $decimals = floor($amount) === $amount ? 0 : 2;

        return number_format($amount, $decimals, '.', ',');
    }

    private function resolveVehicleImage(
        mixed $category,
        Product $routeProduct
    ): ?string {
        foreach ([
            $category?->image_url ?? null,
            $category?->image ?? null,
            $routeProduct->image ?? null,
            $this->firstProductImage($routeProduct->images ?? null),
        ] as $image) {
            if (filled($image)) {
                return $this->normalizeImageUrl((string) $image);
            }
        }

        return null;
    }

    private function firstProductImage(mixed $images): ?string
    {
        if (is_string($images)) {
            $decoded = json_decode($images, true);

            if (is_array($decoded)) {
                $images = $decoded;
            } elseif (trim($images) !== '') {
                return trim($images);
            }
        }

        if (! is_array($images)) {
            return null;
        }

        $image = collect($images)->filter()->first();

        if (is_array($image)) {
            $image = $image['url']
                ?? $image['path']
                ?? $image['image']
                ?? null;
        }

        return is_string($image) && trim($image) !== ''
            ? trim($image)
            : null;
    }

    private function normalizeImageUrl(string $image): string
    {
        $image = trim($image);

        if (Str::startsWith($image, ['http://', 'https://'])) {
            return $image;
        }

        if (Str::startsWith($image, ['/storage/', 'storage/'])) {
            return url('/' . ltrim($image, '/'));
        }

        return url('/storage/' . ltrim($image, '/'));
    }

    private function emptyFareData(): array
    {
        return [
            'fare' => null,
            'formatted_fare' => null,
            'vehicle' => null,
            'vehicle_image' => null,
        ];
    }

    /**
     * Load all required city names from the existing brands table.
     */
    private function loadCityNames(Collection $blocks): Collection
    {
        $cityIds = $blocks
            ->flatMap(
                fn (SmartHomeBlock $block): array => [
                    $block->from_city_id,
                    $block->to_city_id,
                ]
            )
            ->filter()
            ->unique()
            ->values();

        if ($cityIds->isEmpty()) {
            return collect();
        }

        return DB::table('brands')
            ->whereIn('id', $cityIds->all())
            ->pluck('name', 'id');
    }

    /**
     * Return clean city name without state suffix.
     *
     * Example: "Agra,Uttar Pradesh" becomes "Agra".
     */
    private function cityName(
        mixed $cityId,
        Collection $cityNames
    ): ?string {
        if (empty($cityId)) {
            return null;
        }

        $fullName = $cityNames->get((int) $cityId);

        if (! is_string($fullName) || trim($fullName) === '') {
            return null;
        }

        return trim(explode(',', $fullName, 2)[0]);
    }

    private function resolveTitle(
        SmartHomeBlock $block,
        ?string $fromCity,
        ?string $toCity
    ): string {
        if (! empty($block->title)) {
            return trim((string) $block->title);
        }

        $serviceName = $this->serviceLabel(
            (string) $block->service_type
        );

        if ($fromCity && $toCity) {
            return "{$fromCity} to {$toCity} {$serviceName}";
        }

        if ($fromCity) {
            return "{$serviceName} in {$fromCity}";
        }

        return $serviceName;
    }

    private function resolveSubtitle(
        SmartHomeBlock $block,
        ?string $fromCity,
        ?string $toCity
    ): ?string {
        if (! empty($block->subtitle)) {
            return trim((string) $block->subtitle);
        }

        if ($fromCity && $toCity) {
            return "Book your ride from {$fromCity} to {$toCity}";
        }

        return null;
    }

    private function routeLabel(
        ?string $fromCity,
        ?string $toCity
    ): ?string {
        if ($fromCity && $toCity) {
            return "{$fromCity} → {$toCity}";
        }

        return $fromCity ?? $toCity;
    }

    private function normalizeBlockType(string $blockType): string
    {
        $normalized = strtolower(
            trim(str_replace(['-', ' '], '_', $blockType))
        );

        return match ($normalized) {
            'hero', 'banner', 'hero_banner' => 'hero_banners',
            'popular_route', 'routes', 'popular_routes' => 'popular_routes',
            'featured_vehicle', 'vehicles', 'featured_vehicles' => 'featured_vehicles',
            'offer', 'offers' => 'offers',
            'selfdrive', 'self_drive' => 'self_drive',
            'bike', 'bike_rental' => 'bike_rental',
            default => $normalized !== '' ? $normalized : 'other',
        };
    }

    private function normalizeServiceType(string $serviceType): string
    {
        $normalized = strtolower(
            trim(str_replace(['-', ' '], '_', $serviceType))
        );

        return match ($normalized) {
            'oneway', 'one_way_trip' => 'one_way',
            'roundtrip', 'round_trip_taxi', 'return' => 'round_trip',
            'selfdrive', 'without_driver' => 'self_drive',
            'bike', 'bike_rent' => 'bike_rental',
            default => $normalized,
        };
    }

    private function serviceLabel(string $serviceType): string
    {
        return match ($this->normalizeServiceType($serviceType)) {
            'one_way' => 'One Way Taxi',
            'round_trip' => 'Round Trip Taxi',
            'local' => 'Local Taxi',
            'airport' => 'Airport Taxi',
            'self_drive' => 'Self Drive Car',
            'bike_rental' => 'Bike Rental',
            default => 'Taxi Service',
        };
    }

    private function themeFor(string $serviceType): array
    {
        return match ($serviceType) {
            'one_way' => [
                'key' => 'one_way',
                'primary' => '#1565C0',
                'background' => '#EAF3FF',
                'text' => '#0D47A1',
            ],
            'round_trip' => [
                'key' => 'round_trip',
                'primary' => '#2E7D32',
                'background' => '#EAF7EC',
                'text' => '#1B5E20',
            ],
            'local' => [
                'key' => 'local',
                'primary' => '#EF6C00',
                'background' => '#FFF3E6',
                'text' => '#E65100',
            ],
            'airport' => [
                'key' => 'airport',
                'primary' => '#7B1FA2',
                'background' => '#F5EAFE',
                'text' => '#4A148C',
            ],
            'self_drive' => [
                'key' => 'self_drive',
                'primary' => '#212121',
                'background' => '#F1F1F1',
                'text' => '#111111',
            ],
            'bike_rental' => [
                'key' => 'bike_rental',
                'primary' => '#C62828',
                'background' => '#FDECEC',
                'text' => '#8E0000',
            ],
            default => [
                'key' => 'default',
                'primary' => '#1565C0',
                'background' => '#F5F7FA',
                'text' => '#212121',
            ],
        };
    }

    /**
     * Open the self-drive search with the exact vehicle already selected.
     */
    private function actionForSelfDriveVehicle(
        Vehicle $vehicle,
        mixed $fromCityId
    ): array {
        $parameters = array_filter([
            'tab' => 'self_drive',
            'service_type' => 'self_drive',
            'vehicle_id' => (int) $vehicle->getKey(),
            'cityFrom' => $fromCityId ? (int) $fromCityId : null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        return [
            'type' => 'open_vehicle',
            'service_type' => 'self_drive',
            'vehicle_id' => (int) $vehicle->getKey(),
            'url' => route('rides') . '?' . http_build_query($parameters),
            'route_name' => 'rides',
            'parameters' => $parameters,
        ];
    }

    /**
     * Open the existing route detail page whenever an exact route exists.
     * The generic rides URL remains only as a safe fallback.
     */
    private function actionFor(
        ?Product $routeProduct,
        string $serviceType,
        mixed $fromCityId,
        mixed $toCityId
    ): array {
        $fallbackParameters = array_filter([
            'cityFrom' => $fromCityId ? (int) $fromCityId : null,
            'cityTo' => $toCityId ? (int) $toCityId : null,
            'tab' => $this->ridesTab($serviceType),
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        if ($routeProduct && filled($routeProduct->slug)) {
            return [
                'type' => 'open_route',
                'service_type' => $serviceType,
                'url' => route('route.show', [
                    'slug' => $routeProduct->slug,
                ]),
                'route_name' => 'route.show',
                'slug' => (string) $routeProduct->slug,
                'parameters' => [
                    'slug' => (string) $routeProduct->slug,
                ],
                'fallback_url' => route('rides')
                    . '?'
                    . http_build_query($fallbackParameters),
            ];
        }

        return [
            'type' => 'open_service',
            'service_type' => $serviceType,
            'url' => route('rides')
                . '?'
                . http_build_query($fallbackParameters),
            'route_name' => 'rides',
            'parameters' => $fallbackParameters,
        ];
    }

    private function ridesTab(string $serviceType): string
    {
        return $serviceType === 'round_trip'
            ? 'return'
            : $serviceType;
    }

    private function emptySections(): array
    {
        return [
            'hero_banners' => [],
            'popular_routes' => [],
            'featured_vehicles' => [],
            'offers' => [],
            'self_drive' => [],
            'bike_rental' => [],
        ];
    }
}