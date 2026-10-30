<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Validation\ValidationException;

/** Adapts the existing MapsService; keys and provider calls stay on the server. */
class PartnerMarketplacePlaces
{
    public static function suggestions(string $keyword): array
    {
        $result = MapsService::placeAutocomplete($keyword);
        abort_unless($result['status'] ?? false, 503, 'Google location search is unavailable. Please retry.');
        return self::normalizeSuggestions($result['data'] ?? []);
    }

    public static function normalizeSuggestions(array $data): array
    {
        $rows = $data['predictions'] ?? $data['suggestions'] ?? $data['items'] ?? $data;
        abort_unless(is_array($rows) && array_is_list($rows), 502, 'Unexpected MapsService autocomplete response.');
        $items = [];
        foreach ($rows as $row) {
            if (!is_array($row)) continue;
            $row = $row['placePrediction'] ?? $row;
            $id = $row['place_id'] ?? $row['placeId'] ?? null;
            $label = $row['description'] ?? $row['text']['text'] ?? $row['label'] ?? null;
            if (is_string($id) && $id !== '' && is_string($label) && trim($label) !== '') {
                $items[] = ['place_id' => $id, 'label' => trim($label)];
            }
        }
        abort_unless($items !== [] || $rows === [], 502, 'MapsService did not return usable Google predictions.');
        return array_slice($items, 0, 10);
    }

    public static function resolve(string $id): string { return self::details($id)['label']; }

    public static function details(string $id): array
    {
        $result = MapsService::placeDetails($id);
        if (!($result['status'] ?? false)) {
            throw ValidationException::withMessages(['location' => 'The Google location could not be verified. Please select it again.']);
        }
        $data = $result['data'] ?? [];
        $data = $data['result'] ?? $data['place'] ?? $data['details'] ?? $data;
        $label = $data['formatted_address'] ?? $data['formattedAddress'] ?? $data['address'] ?? $data['displayName']['text'] ?? $data['name'] ?? null;
        abort_unless(is_string($label) && trim($label) !== '', 502, 'MapsService did not return a place address/name.');
        if (mb_strlen(trim($label)) > 150) {
            throw ValidationException::withMessages(['location' => 'Choose a city or shorter location name (maximum 150 characters).']);
        }
        $lat = $data['geometry']['location']['lat'] ?? $data['location']['latitude'] ?? $data['latitude'] ?? $data['lat'] ?? null;
        $lng = $data['geometry']['location']['lng'] ?? $data['location']['longitude'] ?? $data['longitude'] ?? $data['lng'] ?? null;
        return ['label' => trim($label), 'latitude' => is_numeric($lat) && abs((float)$lat)<=90 ? (float)$lat : null,
            'longitude' => is_numeric($lng) && abs((float)$lng)<=180 ? (float)$lng : null];
    }
}
