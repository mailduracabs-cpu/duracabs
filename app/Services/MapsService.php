<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class MapsService
{
    private const GOOGLE_BASE_URL = 'https://maps.googleapis.com/maps/api';
    private const CONNECT_TIMEOUT_SECONDS = 4;
    private const REQUEST_TIMEOUT_SECONDS = 10;
    private const AUTOCOMPLETE_CACHE_SECONDS = 300;
    private const PLACE_DETAILS_CACHE_SECONDS = 1800;
    private const GEOCODE_CACHE_SECONDS = 1800;

    private static function apiKey(): ?string
    {
        $key = config('services.google.maps_key')
            ?: env('GOOGLE_MAPS_API_KEY')
            ?: env('GOOGLE_MAP_KEY');

        return filled($key) ? trim((string) $key) : null;
    }

    private static function client(): PendingRequest
    {
        $client = Http::acceptJson()
            ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
            ->timeout(self::REQUEST_TIMEOUT_SECONDS)
            ->retry(
                2,
                150,
                static fn (Throwable $exception): bool => $exception instanceof ConnectionException,
                throw: false
            );

        // Local Windows/XAMPP installations sometimes do not have a configured CA bundle.
        // Production should use normal SSL verification.
        if (app()->environment('local')) {
            $client = $client->withoutVerifying();
        }

        return $client;
    }

    private static function cacheKey(string $type, array $parts): string
    {
        $normalized = array_map(
            static fn (mixed $value): string => Str::lower(trim((string) $value)),
            $parts
        );

        return 'google_maps:' . $type . ':' . sha1(implode('|', $normalized));
    }

    private static function missingKeyResponse(): array
    {
        return [
            'status' => false,
            'message' => 'Google Maps API key missing',
            'data' => [],
        ];
    }

    private static function request(string $endpoint, array $query, string $operation): array
    {
        try {
            /** @var Response $response */
            $response = self::client()->get(self::GOOGLE_BASE_URL . $endpoint, $query);

            if (!$response->successful()) {
                Log::warning("Google Maps {$operation} HTTP error", [
                    'http_status' => $response->status(),
                    'response' => app()->environment('local') ? $response->body() : null,
                ]);

                return [
                    'status' => false,
                    'message' => 'Google Maps service is temporarily unavailable',
                    'data' => [],
                ];
            }

            $json = $response->json();

            if (!is_array($json)) {
                Log::warning("Google Maps {$operation} returned an invalid response");

                return [
                    'status' => false,
                    'message' => 'Invalid response received from Google Maps',
                    'data' => [],
                ];
            }

            return [
                'status' => true,
                'message' => 'Google Maps response received',
                'data' => $json,
            ];
        } catch (Throwable $e) {
            Log::error("Google Maps {$operation} error", [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            return [
                'status' => false,
                'message' => app()->environment('local')
                    ? $e->getMessage()
                    : 'Google Maps service is temporarily unavailable',
                'data' => [],
            ];
        }
    }

    private static function googleError(array $json, array $allowedStatuses = ['OK']): ?array
    {
        $googleStatus = (string) ($json['status'] ?? '');

        if (in_array($googleStatus, $allowedStatuses, true)) {
            return null;
        }

        return [
            'status' => false,
            'message' => (string) ($json['error_message'] ?? ($googleStatus ?: 'Google API Error')),
            'data' => app()->environment('local') ? $json : [],
        ];
    }

    public static function placeAutocomplete(string $keyword): array
    {
        $key = self::apiKey();
        $keyword = trim(preg_replace('/\s+/', ' ', $keyword) ?? $keyword);

        if (!$key) {
            return self::missingKeyResponse();
        }

        if (mb_strlen($keyword) < 2) {
            return [
                'status' => true,
                'message' => 'Enter at least 2 characters',
                'data' => [],
            ];
        }

        $cacheKey = self::cacheKey('autocomplete', [$keyword, 'country:in', '(cities)']);

        return Cache::remember($cacheKey, self::AUTOCOMPLETE_CACHE_SECONDS, static function () use ($key, $keyword): array {
            $result = self::request('/place/autocomplete/json', [
                'input' => $keyword,
                'components' => 'country:in',
                'types' => '(cities)',
                'language' => 'en',
                'key' => $key,
            ], 'place autocomplete');

            if (!$result['status']) {
                return $result;
            }

            $json = $result['data'];
            $error = self::googleError($json, ['OK', 'ZERO_RESULTS']);

            if ($error) {
                return $error;
            }

            $predictions = collect($json['predictions'] ?? [])
                ->take(8)
                ->map(static fn (array $prediction): array => [
                    'description' => $prediction['description'] ?? '',
                    'place_id' => $prediction['place_id'] ?? '',
                    'structured_formatting' => $prediction['structured_formatting'] ?? [],
                    'terms' => $prediction['terms'] ?? [],
                    'types' => $prediction['types'] ?? [],
                ])
                ->filter(static fn (array $prediction): bool => filled($prediction['description']) && filled($prediction['place_id']))
                ->values()
                ->all();

            return [
                'status' => true,
                'message' => 'Place autocomplete fetched',
                'data' => $predictions,
            ];
        });
    }

    public static function placeDetails(string $placeId): array
    {
        $key = self::apiKey();
        $placeId = trim($placeId);

        if (!$key) {
            return self::missingKeyResponse();
        }

        if ($placeId === '') {
            return ['status' => false, 'message' => 'Place ID is required', 'data' => []];
        }

        return Cache::remember(
            self::cacheKey('place_details', [$placeId]),
            self::PLACE_DETAILS_CACHE_SECONDS,
            static function () use ($key, $placeId): array {
                $result = self::request('/place/details/json', [
                    'place_id' => $placeId,
                    'fields' => 'name,formatted_address,geometry,place_id,address_components',
                    'language' => 'en',
                    'key' => $key,
                ], 'place details');

                if (!$result['status']) {
                    return $result;
                }

                $json = $result['data'];
                $error = self::googleError($json);

                return $error ?: [
                    'status' => true,
                    'message' => 'Place details fetched',
                    'data' => $json['result'] ?? [],
                ];
            }
        );
    }

    public static function distance(string $origin, string $destination): array
    {
        $key = self::apiKey();

        if (!$key) {
            return self::missingKeyResponse();
        }

        $result = self::request('/distancematrix/json', [
            'origins' => trim($origin),
            'destinations' => trim($destination),
            'units' => 'metric',
            'key' => $key,
        ], 'distance matrix');

        if (!$result['status']) {
            return $result;
        }

        $json = $result['data'];
        $error = self::googleError($json);

        return $error ?: [
            'status' => true,
            'message' => 'Distance fetched',
            'data' => $json,
        ];
    }

    public static function directions(string $origin, string $destination): array
    {
        $key = self::apiKey();

        if (!$key) {
            return self::missingKeyResponse();
        }

        $result = self::request('/directions/json', [
            'origin' => trim($origin),
            'destination' => trim($destination),
            'key' => $key,
        ], 'directions');

        if (!$result['status']) {
            return $result;
        }

        $json = $result['data'];
        $error = self::googleError($json);

        return $error ?: [
            'status' => true,
            'message' => 'Directions fetched',
            'data' => $json,
        ];
    }

    public static function geocode(string $address): array
    {
        $key = self::apiKey();
        $address = trim($address);

        if (!$key) {
            return self::missingKeyResponse();
        }

        return Cache::remember(
            self::cacheKey('geocode', [$address]),
            self::GEOCODE_CACHE_SECONDS,
            static function () use ($key, $address): array {
                $result = self::request('/geocode/json', [
                    'address' => $address,
                    'region' => 'in',
                    'key' => $key,
                ], 'geocode');

                if (!$result['status']) {
                    return $result;
                }

                $json = $result['data'];
                $error = self::googleError($json, ['OK', 'ZERO_RESULTS']);

                return $error ?: [
                    'status' => true,
                    'message' => 'Geocode fetched',
                    'data' => $json,
                ];
            }
        );
    }

    public static function reverseGeocode(string $lat, string $lng): array
    {
        $key = self::apiKey();
        $lat = trim($lat);
        $lng = trim($lng);

        if (!$key) {
            return self::missingKeyResponse();
        }

        return Cache::remember(
            self::cacheKey('reverse_geocode', [$lat, $lng]),
            self::GEOCODE_CACHE_SECONDS,
            static function () use ($key, $lat, $lng): array {
                $result = self::request('/geocode/json', [
                    'latlng' => $lat . ',' . $lng,
                    'key' => $key,
                ], 'reverse geocode');

                if (!$result['status']) {
                    return $result;
                }

                $json = $result['data'];
                $error = self::googleError($json, ['OK', 'ZERO_RESULTS']);

                return $error ?: [
                    'status' => true,
                    'message' => 'Reverse geocode fetched',
                    'data' => $json,
                ];
            }
        );
    }
}