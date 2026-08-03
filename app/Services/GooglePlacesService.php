<?php

declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class GooglePlacesService
{
    private const AUTOCOMPLETE_URL = 'https://maps.googleapis.com/maps/api/place/autocomplete/json';
    private const PLACE_DETAILS_URL = 'https://maps.googleapis.com/maps/api/place/details/json';
    private const GEOCODING_URL = 'https://maps.googleapis.com/maps/api/geocode/json';

    public function __construct(
        private readonly Client $client,
    ) {
    }

    /**
     * Search Google Places suggestions restricted to India.
     *
     * @return array<int, array<string, mixed>>
     */
    public function autocomplete(?string $input): array
    {
        $input = trim((string) $input);

        if (mb_strlen($input) < 3) {
            return [];
        }

        $apiKey = $this->apiKey();

        if ($apiKey === null) {
            Log::warning('Google Places autocomplete skipped: API key is missing.');
            return [];
        }

        try {
            $response = $this->client->get(self::AUTOCOMPLETE_URL, [
                'query' => [
                    'input' => $input,
                    'components' => 'country:in',
                    'language' => 'en',
                    'key' => $apiKey,
                ],
                'timeout' => 10,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $status = $data['status'] ?? null;

            if ($status === 'ZERO_RESULTS') {
                return [];
            }

            if ($status !== 'OK') {
                Log::warning('Google Places autocomplete returned an error.', [
                    'status' => $status,
                    'error_message' => $data['error_message'] ?? null,
                    'input' => $input,
                ]);

                return [];
            }

            return is_array($data['predictions'] ?? null)
                ? $data['predictions']
                : [];
        } catch (\Throwable $exception) {
            Log::error('Google Places autocomplete request failed.', [
                'input' => $input,
                'message' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Resolve a Google place ID into address and coordinates.
     *
     * @return array{
     *     place_id: string,
     *     formatted_address: string,
     *     city: string|null,
     *     latitude: float|int|string|null,
     *     longitude: float|int|string|null
     * }|null
     */
    public function placeDetails(string $placeId): ?array
    {
        $placeId = trim($placeId);
        $apiKey = $this->apiKey();

        if ($placeId === '' || $apiKey === null) {
            return null;
        }

        try {
            $response = $this->client->get(self::PLACE_DETAILS_URL, [
                'query' => [
                    'place_id' => $placeId,
                    'fields' => 'place_id,formatted_address,address_components,geometry',
                    'language' => 'en',
                    'key' => $apiKey,
                ],
                'timeout' => 10,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $result = $data['result'] ?? null;

            if (($data['status'] ?? null) !== 'OK' || !is_array($result)) {
                Log::warning('Google Place details returned an error.', [
                    'status' => $data['status'] ?? null,
                    'error_message' => $data['error_message'] ?? null,
                    'place_id' => $placeId,
                ]);

                return null;
            }

            $address = trim((string) ($result['formatted_address'] ?? ''));
            $latitude = data_get($result, 'geometry.location.lat');
            $longitude = data_get($result, 'geometry.location.lng');

            if ($address === '' || !is_numeric($latitude) || !is_numeric($longitude)) {
                return null;
            }

            return [
                'place_id' => (string) ($result['place_id'] ?? $placeId),
                'formatted_address' => $address,
                'city' => $this->extractCity($result['address_components'] ?? []),
                'latitude' => $latitude,
                'longitude' => $longitude,
            ];
        } catch (\Throwable $exception) {
            Log::error('Google Place details request failed.', [
                'place_id' => $placeId,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Convert coordinates into a Google address.
     *
     * @return array{
     *     place_id: string|null,
     *     formatted_address: string,
     *     city: string|null,
     *     latitude: float,
     *     longitude: float
     * }|null
     */
    public function reverseGeocode(float $latitude, float $longitude): ?array
    {
        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            return null;
        }

        $apiKey = $this->apiKey();

        if ($apiKey === null) {
            Log::warning('Google reverse geocoding skipped: API key is missing.');
            return null;
        }

        try {
            $response = $this->client->get(self::GEOCODING_URL, [
                'query' => [
                    'latlng' => $latitude . ',' . $longitude,
                    'language' => 'en',
                    'key' => $apiKey,
                ],
                'timeout' => 10,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $result = data_get($data, 'results.0');

            if (($data['status'] ?? null) !== 'OK' || !is_array($result)) {
                Log::warning('Google reverse geocoding returned an error.', [
                    'status' => $data['status'] ?? null,
                    'error_message' => $data['error_message'] ?? null,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ]);

                return null;
            }

            $address = trim((string) ($result['formatted_address'] ?? ''));

            if ($address === '') {
                return null;
            }

            return [
                'place_id' => isset($result['place_id']) ? (string) $result['place_id'] : null,
                'formatted_address' => $address,
                'city' => $this->extractCity($result['address_components'] ?? []),
                'latitude' => $latitude,
                'longitude' => $longitude,
            ];
        } catch (\Throwable $exception) {
            Log::error('Google reverse geocoding request failed.', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    public function hasApiKey(): bool
    {
        return $this->apiKey() !== null;
    }

    private function apiKey(): ?string
    {
        $key = config('services.google_maps.key')
            ?: config('services.google.places_key')
            ?: env('GOOGLE_MAPS_API_KEY')
            ?: env('GOOGLE_PLACES_API_KEY');

        $key = trim((string) $key);

        return $key !== '' ? $key : null;
    }

    private function extractCity(array $components): ?string
    {
        $priority = [
            'locality',
            'postal_town',
            'administrative_area_level_3',
            'administrative_area_level_2',
        ];

        foreach ($priority as $type) {
            foreach ($components as $component) {
                if (!in_array($type, $component['types'] ?? [], true)) {
                    continue;
                }

                $city = trim((string) ($component['long_name'] ?? ''));

                if ($city !== '') {
                    return $city;
                }
            }
        }

        return null;
    }
}