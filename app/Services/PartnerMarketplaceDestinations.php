<?php
namespace App\Services;
final class PartnerMarketplaceDestinations
{
    public static function matches(string $destination, array $cab): bool
    {
        $cities = $cab['drop_cities'] ?? null;
        // Existing free posts remain unrestricted until republished with destinations.
        if ($cities === null) return true;
        if (is_string($cities)) $cities = json_decode($cities, true);
        if (!is_array($cities) || !$cities) return false;
        $normalize = static function (string $city): string {
            $key = preg_replace('/[^a-z0-9]/', '', strtolower(trim(explode(',', $city)[0])));
            // Common booking shorthand and Google's city label refer to the same market.
            return $key === 'newdelhi' ? 'delhi' : $key;
        };
        $target = $normalize($destination);
        foreach ($cities as $city) {
            $label = is_array($city) ? ($city['label'] ?? '') : $city;
            if (is_string($label) && $target !== '' && $target === $normalize($label)) return true;
        }
        return false;
    }
}
