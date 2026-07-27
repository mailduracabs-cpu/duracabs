<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\Http;
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
        $locations = $data['locations'] ?? [];

        if (!is_array($locations) || count($locations) < 3) {
            return [
                'status' => false,
                'message' => 'Minimum pickup, destination and return city required.',
            ];
        }

        $tripDays = self::tripDays(
            $data['start_date'] ?? null,
            $data['end_date'] ?? null
        );

        $routeResult = self::calculateGoogleRoute($locations);

        if (!($routeResult['status'] ?? false)) {
            return $routeResult;
        }

        $totalKm = (float) $routeResult['total_km'];
        $minimumKm = self::MIN_KM_PER_DAY * $tripDays;
        $billableKm = max($totalKm, $minimumKm);

        $categories = self::returnTripCategories();

        if ($categories->isEmpty()) {
            return [
                'status' => false,
                'message' => 'No round trip cab category found. Please enable In Return in Laravel admin.',
            ];
        }

        $vehicles = $categories->map(function ($category) use (
            $totalKm,
            $minimumKm,
            $billableKm,
            $tripDays
        ) {
            $kmCharge = (float) ($category->km_charge ?? 0);
            $driverCharge = (float) ($category->driver_charge ?? 0);

            $baseFare = round($billableKm * $kmCharge, 2);
            $driverTotal = round($driverCharge * $tripDays, 2);
            $grandTotal = round($baseFare + $driverTotal, 2);

            return [
                'id' => $category->id,
                'category_id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug ?? null,
                'model' => $category->model ?? null,
                'passenger_capacity' => $category->passanger_capacity ?? null,
                'luggage_capacity' => $category->luggage_capacity ?? null,
                'image' => self::categoryImage($category),

                'total_km' => $totalKm,
                'minimum_km' => $minimumKm,
                'billable_km' => $billableKm,
                'trip_days' => $tripDays,

                'km_charge' => $kmCharge,
                'driver_charge' => $driverCharge,
                'base_fare' => $baseFare,
                'driver_total' => $driverTotal,
                'price' => $grandTotal,
                'amount' => $grandTotal,
                'grand_total' => $grandTotal,
                'currency' => 'INR',
                'currency_symbol' => '₹',

                'fare_breakup' => [
                    'total_km' => $totalKm,
                    'minimum_km' => $minimumKm,
                    'billable_km' => $billableKm,
                    'trip_days' => $tripDays,
                    'km_charge' => $kmCharge,
                    'driver_charge_per_day' => $driverCharge,
                    'base_fare' => $baseFare,
                    'driver_total' => $driverTotal,
                    'grand_total' => $grandTotal,
                ],
            ];
        })->values();

        return [
            'status' => true,
            'message' => 'Round trip fare calculated successfully.',
            'data' => [
                'locations' => $locations,
                'total_km' => $totalKm,
                'duration_text' => $routeResult['duration_text'] ?? '',
                'duration_seconds' => $routeResult['duration_seconds'] ?? 0,
                'minimum_km' => $minimumKm,
                'billable_km' => $billableKm,
                'trip_days' => $tripDays,
                'vehicles' => $vehicles,
            ],
        ];
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
                $response = Http::withoutVerifying()->timeout(25)->get(
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
                    'error' => $e->getMessage(),
                    'origin' => $origin,
                    'destination' => $destination,
                ]);

                return [
                    'status' => false,
                    'message' => $e->getMessage(),
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

    private static function returnTripCategories()
    {
        return Category::query()
            ->where('is_active', 1)
            ->where('in_return', 1)
            ->where('km_charge', '>', 0)
            ->orderBy('id')
            ->get();
    }

    private static function tripDays(?string $startDate, ?string $endDate): int
    {
        if (!$startDate || !$endDate) {
            return 1;
        }

        try {
            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);

            $days = $start->diff($end)->days + 1;

            return max(1, (int) $days);
        } catch (\Throwable $e) {
            return 1;
        }
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