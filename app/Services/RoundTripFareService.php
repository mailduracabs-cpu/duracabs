<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RoundTripFareService
{
    private const MIN_KM_PER_DAY = 300;

    private static function apiKey(): ?string
    {
        return env('GOOGLE_MAPS_API_KEY')
            ?: env('GOOGLE_MAP_KEY')
            ?: config('services.google.maps_key');
    }

    public static function estimate(array $data): array
    {
        try {
            self::validatePeriod($data['start_date'] ?? null, $data['start_time'] ?? null, $data['end_date'] ?? null, $data['end_time'] ?? null);
            $days = self::tripDays($data['start_date'] ?? null, $data['end_date'] ?? null);
            $route = self::routeInfo((array) ($data['locations'] ?? []));
            $fareType = $data['fare_type'] ?? 'all_inclusive';
            $vehicles = self::returnTripCategories()->map(fn ($category) => self::categoryQuote(
                $category, (float) $route['total_km'], $days, $fareType, (int) ($data['quantity'] ?? 1)
            ))->values();
            if ($vehicles->isEmpty()) throw new \InvalidArgumentException('No active round-trip category is available.');
            return ['status' => true, 'message' => 'Round trip fare calculated successfully.', 'data' => array_merge($route, [
                'locations' => $data['locations'], 'trip_days' => $days,
                'fare_type' => $fareType, 'vehicles' => $vehicles,
            ])];
        } catch (\InvalidArgumentException $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /** Arithmetic shared by API, website cards, checkout and booking. */
    public static function categoryQuote($category, float $totalKm, int $days, string $fareType = 'all_inclusive', int $quantity = 1): array
    {
        if (! in_array($fareType, ['normal', 'all_inclusive'], true) || $days < 1 || $quantity < 1 || $quantity > 20
            || ! is_finite($totalKm) || $totalKm <= 0 || (float) ($category->km_charge ?? 0) <= 0) {
            throw new \InvalidArgumentException('Invalid round-trip fare selection.');
        }
        $perDay = (float) ($category->range ?? 0);
        $perDay = $perDay > 0 ? $perDay : self::MIN_KM_PER_DAY;
        $minimum = $perDay * $days;
        $billable = max($totalKm, $minimum);
        $base = round($billable * (float) $category->km_charge, 2);
        $driver = round(max(0, (float) ($category->driver_charge ?? 0)) * $days, 2);
        $inclusive = round($billable * 3, 2);
        $normal = round(($base + $driver) * $quantity, 2);
        $all = round(($base + $inclusive) * $quantity, 2);
        $total = $fareType === 'all_inclusive' ? $all : $normal;
        $breakup = [
            'base_fare' => round($base * $quantity, 2), 'vehicle_fare' => round($base * $quantity, 2),
            'driver_total' => $fareType === 'normal' ? round($driver * $quantity, 2) : 0,
            'driver_allowance' => round($driver * $quantity, 2),
            'all_inclusive_charge' => $fareType === 'all_inclusive' ? round($inclusive * $quantity, 2) : 0,
            'all_inclusive_rate' => 3, 'fare_type' => $fareType,
            'normal_total' => $normal, 'all_inclusive_total' => $all, 'grand_total' => $total,
            'total_km' => $totalKm, 'minimum_km' => $minimum, 'billable_km' => $billable,
            'minimum_km_per_day' => $perDay, 'trip_days' => $days, 'quantity' => $quantity,
            'km_charge' => (float) $category->km_charge, 'driver_charge_per_day' => (float) ($category->driver_charge ?? 0),
            'gst_included' => true, 'gst_amount' => 0, 'parking_policy' => 'extra_as_actual',
            'toll_included' => $fareType === 'all_inclusive', 'state_tax_included' => $fareType === 'all_inclusive',
        ];
        return array_merge($breakup, [
            'id' => $category->id, 'category_id' => $category->id,
            'name' => $category->name, 'vehicle_name' => $category->name,
            'slug' => $category->slug ?? null, 'model' => $category->model ?? null,
            'passenger_capacity' => $category->passanger_capacity ?? null,
            'luggage_capacity' => $category->luggage_capacity ?? null, 'image' => self::categoryImage($category),
            'price' => $total, 'amount' => $total, 'currency' => 'INR', 'currency_symbol' => '₹',
            'booking_options' => CategoryBookingOptions::available($category), 'fare_breakup' => $breakup,
        ]);
    }

    /** Recompute from server route distance, not a client amount/billable-km field. */
    public static function bookingQuote(array $data): array
    {
        self::validatePeriod($data['start_date'] ?? null, $data['start_time'] ?? null, $data['end_date'] ?? null, $data['end_time'] ?? null);
        $category = self::returnTripCategories()->firstWhere('id', (int) ($data['category_id'] ?? 0));
        if (! $category) throw new \InvalidArgumentException('Selected round-trip category is unavailable.');
        $route = self::routeInfo((array) ($data['locations'] ?? []));
        return array_merge(self::categoryQuote($category, (float) $route['total_km'],
            self::tripDays($data['start_date'], $data['end_date']), $data['fare_type'] ?? 'all_inclusive', (int) ($data['quantity'] ?? 1)),
            ['locations' => $data['locations'], 'duration_text' => $route['duration_text'], 'duration_seconds' => $route['duration_seconds']]);
    }

    public static function routeInfo(array $locations): array
    {
        if (count($locations) < 3 || count($locations) > 22) throw new \InvalidArgumentException('Pickup, destination and return city are required.');
        $texts = array_map(fn ($location) => self::locationText($location), $locations);
        if (in_array('', $texts, true)) throw new \InvalidArgumentException('Invalid route location.');
        $key = 'round_trip_route_v2_' . hash('sha256', json_encode($texts));
        $route = Cache::get($key);
        if (! is_array($route)) {
            $route = self::calculateGoogleRoute($locations);
            if (! ($route['status'] ?? false) || (float) ($route['total_km'] ?? 0) <= 0) {
                throw new \InvalidArgumentException($route['message'] ?? 'Unable to verify route distance.');
            }
            Cache::put($key, $route, now()->addMinutes(10));
        }
        return $route;
    }

    public static function validatePeriod(?string $startDate, ?string $startTime, ?string $endDate, ?string $endTime): void
    {
        self::tripDays($startDate, $endDate);
        foreach ([$startTime, $endTime] as $time) {
            if (! is_string($time) || ! preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D', $time)) {
                throw new \InvalidArgumentException('Select pickup and end time.');
            }
        }
        if (strtotime($endDate . ' ' . $endTime) <= strtotime($startDate . ' ' . $startTime)) {
            throw new \InvalidArgumentException('End date/time must be after pickup.');
        }
    }

    private static function calculateGoogleRoute(array $locations): array
    {
        $key = self::apiKey();

        if (!$key) {
            return [
                'status' => false,
                'message' => 'Google Maps API key missing.',
            ];
        }

        $totalMeters = 0;
        $totalSeconds = 0;

        for ($i = 0; $i < count($locations) - 1; $i++) {
            $origin = self::locationText($locations[$i]);
            $destination = self::locationText($locations[$i + 1]);

            if ($origin === '' || $destination === '') {
                return [
                    'status' => false,
                    'message' => 'Invalid route location.',
                ];
            }

            try {
                $response = Http::timeout(25)->get(
                    'https://maps.googleapis.com/maps/api/distancematrix/json',
                    [
                        'origins' => $origin,
                        'destinations' => $destination,
                        'units' => 'metric',
                        'key' => $key,
                    ]
                );

                $json = $response->json();

                if (($json['status'] ?? '') !== 'OK') {
                    return [
                        'status' => false,
                        'message' => $json['error_message'] ?? ($json['status'] ?? 'Google distance error.'),
                        'data' => $json,
                    ];
                }

                $element = $json['rows'][0]['elements'][0] ?? null;

                if (!$element || ($element['status'] ?? '') !== 'OK') {
                    return [
                        'status' => false,
                        'message' => $element['status'] ?? 'Route distance not found.',
                        'data' => $json,
                    ];
                }

                $totalMeters += (int) ($element['distance']['value'] ?? 0);
                $totalSeconds += (int) ($element['duration']['value'] ?? 0);
            } catch (\Throwable $e) {
                Log::error('Round Trip Google Distance Error', [
                    'error_type' => get_class($e),
                    'origin' => $origin,
                    'destination' => $destination,
                ]);

                return [
                    'status' => false,
                    'message' => 'Unable to verify route distance. Please retry.',
                ];
            }
        }

        return [
            'status' => true,
            'total_km' => round($totalMeters / 1000, 2),
            'duration_seconds' => $totalSeconds,
            'duration_text' => self::formatDuration($totalSeconds),
        ];
    }

    public static function returnTripCategories()
    {
        return Category::query()
            ->where('is_active', 1)
            ->where('in_return', 1)
            ->when(\Illuminate\Support\Facades\Schema::hasColumn('categories', 'service_group'), fn ($query) => $query->where('service_group', 'with_driver'))
            ->where('km_charge', '>', 0)
            ->orderBy('id')
            ->get();
    }

    public static function tripDays(?string $startDate, ?string $endDate): int
    {
        foreach ([$startDate, $endDate] as $date) {
            $parsed = is_string($date) ? \DateTimeImmutable::createFromFormat('!Y-m-d', $date) : false;
            if (! $parsed || $parsed->format('Y-m-d') !== $date) throw new \InvalidArgumentException('Select valid pickup and end dates.');
        }
        $start = new \DateTimeImmutable($startDate);
        $end = new \DateTimeImmutable($endDate);
        if ($end < $start) throw new \InvalidArgumentException('End date cannot be before pickup.');
        return (int) $start->diff($end)->days + 1;
    }

    private static function locationText($location): string
    {
        if (is_array($location)) {
            return trim((string) (
                $location['name']
                ?? $location['description']
                ?? $location['address']
                ?? ''
            ));
        }

        return trim((string) $location);
    }

    private static function formatDuration(int $seconds): string
    {
        if ($seconds <= 0) {
            return '0 Hr';
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($hours > 0 && $minutes > 0) {
            return "{$hours} Hr {$minutes} Min";
        }

        if ($hours > 0) {
            return "{$hours} Hr";
        }

        return "{$minutes} Min";
    }

    private static function categoryImage($category): ?string
    {
        $image = null;

        foreach (['image', 'thumbnail', 'icon'] as $field) {
            if (!empty($category->{$field})) {
                $image = $category->{$field};
                break;
            }
        }

        if (!$image && !empty($category->images)) {
            $images = $category->images;

            if (is_string($images)) {
                $decoded = json_decode($images, true);
                $images = is_array($decoded) ? $decoded : [];
            }

            if (is_array($images) && count($images) > 0) {
                $image = $images[0];
            }
        }

        return $image ? url('storage/' . ltrim($image, '/')) : null;
    }
}