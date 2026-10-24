<?php

namespace App\Services;

use InvalidArgumentException;

/** Shared category-priced add-ons. No client price is accepted here. */
final class CategoryBookingOptions
{
    private const OPTIONS = [
        'pet_friendly' => ['Pet Friendly', 'pet_friendly_charge'],
        'roof_carrier' => ['Roof Carrier', 'roof_carrier_charge'],
        'extra_pickup' => ['Extra Pickup', 'extra_pickup_charge'],
        'extra_drop' => ['Extra Drop', 'extra_drop_charge'],
    ];

    public static function available($category): array
    {
        $options = [];
        if (! $category) return $options;
        foreach (self::OPTIONS as $key => [$label, $field]) {
            $value = is_array($category) ? ($category[$field] ?? null) : ($category->$field ?? null);
            if (! is_numeric($value) || ! is_finite((float) $value) || (float) $value < 0) continue;
            $options[] = ['key' => $key, 'name' => $label, 'price' => round((float) $value, 2),
                'main_route_only' => in_array($key, ['extra_pickup', 'extra_drop'], true)];
        }
        return $options;
    }

    public static function selected($category, array $keys): array
    {
        $available = array_column(self::available($category), null, 'key');
        $items = [];
        foreach (array_unique($keys) as $key) {
            if (! is_string($key) || ! isset($available[$key])) {
                throw new InvalidArgumentException('Selected optional service is unavailable. Refresh the category and retry.');
            }
            $items[] = $available[$key];
        }
        return ['items' => $items, 'total' => round(array_sum(array_column($items, 'price')), 2)];
    }
}
