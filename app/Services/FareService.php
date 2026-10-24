<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Price;
use App\Models\Product;

class FareService
{
    public function estimate(array $data): array
    {
        $route = null;

        if (! empty($data['route_id'])) {
            $route = Product::query()
                ->where('is_active', 1)
                ->find($data['route_id']);
        }

        if (! $route && ! empty($data['from']) && ! empty($data['to'])) {
            $from = trim((string) $data['from']);
            $to = trim((string) $data['to']);

            $route = Product::query()
                ->where('is_active', 1)
                ->where('name', 'LIKE', "%{$from}%")
                ->where('name', 'LIKE', "%{$to}%")
                ->orderBy('price')
                ->first();
        }

        if (! $route) {
            throw new \InvalidArgumentException(
                'Route not found. Please select a valid route.'
            );
        }

        $category = null;

        if (! empty($data['category_id'])) {
            $category = Category::query()
                ->where('is_active', 1)
                ->find($data['category_id']);

            if (! $category) {
                throw new \InvalidArgumentException(
                    'Selected vehicle category is not available.'
                );
            }
        }

        $isPublishedPackage = in_array($route->ride_type ?? '', ['one_way', 'local', 'airport'], true);
        if ($isPublishedPackage && ((! ($route->in_stock ?? false))
            || (isset($route->service_group) && $route->service_group !== 'with_driver'))) {
            throw new \InvalidArgumentException('This route is currently unavailable.');
        }

        $routePrice = null;
        $prices = Price::query()->where('product_id', $route->id);
        if ($category) {
            $prices->where('category_id', $category->id);
            if (! empty($data['price_id'])) $prices->where('id', (int) $data['price_id']);
            $routePrice = $prices->orderBy('price')->first();
        }
        if ($isPublishedPackage && ! $routePrice) {
            if (! empty($data['price_id']) || Price::query()->where('product_id', $route->id)->exists()
                || ! $category || (int) $category->id !== (int) ($route->category_id ?? 0)) {
                throw new \InvalidArgumentException('Selected category price is unavailable for this route. Search again.');
            }
        }
        $baseFare = $routePrice ? (float) $routePrice->price : (float) $route->price;
        if (! is_finite($baseFare) || $baseFare <= 0) {
            throw new \InvalidArgumentException('Route price is unavailable. Search again.');
        }

        $quantity = $isPublishedPackage ? (int) ($data['quantity'] ?? 1) : 1;
        if ($quantity < 1 || $quantity > 20) throw new \InvalidArgumentException('Select a valid car quantity.');
        $baseFare = round($baseFare * $quantity, 2);
        $maxFare = $routePrice
            ? (float) $routePrice->max_price
            : (float) $route->max_price;
        $maxFare *= $quantity;

        $hasDistance = isset($data['distance_km']);
        $hasDuration = isset($data['duration_hr']);
        foreach (['distance_km', 'duration_hr'] as $field) {
            if (isset($data[$field]) && (! is_numeric($data[$field])
                || ! is_finite((float) $data[$field]) || (float) $data[$field] < 0)) {
                throw new \InvalidArgumentException('Invalid trip distance or duration.');
            }
        }
        $localHours = null; $localKm = null;
        if (($route->ride_type ?? '') === 'local' && preg_match('/^(\d+)\s+Hour\s*\/\s*(\d+)\s+Km$/i', (string) ($route->plan ?? ''), $plan)) {
            $localHours = (float) $plan[1]; $localKm = (float) $plan[2];
        }
        $distanceKm = (float) (
            $data['distance_km']
            ?? $localKm ?? $route->km_limit
            ?? 0
        );

        $durationHr = (float) (
            $data['duration_hr']
            ?? $localHours ?? $route->hr_limit
            ?? 0
        );

        /*
         * Extra KM / Extra Hour rates are category-authoritative.
         * Route-level fields are intentionally not used here.
         */
        $extraKmCharge = max(0, (float) ($category?->extra_km_charge ?? 0));
        $extraHrCharge = max(0, (float) ($category?->extra_hr_charge ?? 0));
        $kmLimit = (float) ($localKm ?? $route->km_limit ?? 0);
        $hrLimit = (float) ($localHours ?? $route->hr_limit ?? 0);

        $extraKm = max(0, $distanceKm - $kmLimit);
        $extraHr = max(0, $durationHr - $hrLimit);

        $extraKmAmount = round($extraKm * $extraKmCharge * $quantity, 2);
        $extraHrAmount = round($extraHr * $extraHrCharge * $quantity, 2);

        /*
         * Route-level configured charges.
         */
        $configuredToll = max(0, (float) ($route->toll_tax ?? 0));
        $configuredBorderTax = max(0, (float) ($route->border_tax ?? 0));
        $configuredDriverAllowance = max(
            0,
            (float) ($route->driver_allowances ?? 0)
        );

        $tollIncluded = (bool) ($route->toll_included ?? false);
        $stateTaxIncluded = (bool) ($route->state_tax_included ?? false);
        $parkingIncluded = (bool) ($route->parking_included ?? false);
        $gstIncluded = (bool) ($route->gst_included ?? false);
        // DuraCabs published one-way fare includes these charges. Parking is always separate.
        if ($isPublishedPackage) {
            $tollIncluded = $stateTaxIncluded = $gstIncluded = true;
        }
        $parkingIncluded = false;

        /*
         * Included charges are informational only.
         * Extra charges are added to payable total.
         */
        $tollExtra = $tollIncluded ? 0.0 : $configuredToll;
        $borderTaxExtra = $stateTaxIncluded ? 0.0 : $configuredBorderTax;

        /*
         * Driver allowance is included in published one-way fares.
         * Other ride types retain the legacy configured-charge behavior.
         */
        $driverAllowanceExtra = $isPublishedPackage ? 0.0 : $configuredDriverAllowance;

        /*
         * Optional customer selections.
         * Amounts always come from admin route configuration.
         */
        $patSelected = filter_var(
            $data['pat_selected'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        $roofCarrierSelected = filter_var(
            $data['roof_carrier_selected'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        $nightChargeSelected = filter_var(
            $data['night_charge_selected'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        $extraPickupSelected = filter_var(
            $data['extra_pickup_selected'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        $extraDropSelected = filter_var(
            $data['extra_drop_selected'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        /*
         * All One Way add-on prices are category-authoritative.
         * Extra Pickup / Drop are valid only when the stop is on the main route.
         * The legacy request key pat_selected is retained for API compatibility,
         * but it represents the Pet Friendly option.
         */
        $optionKeys = [];
        foreach (['pet_friendly' => $patSelected, 'roof_carrier' => $roofCarrierSelected,
            'extra_pickup' => $extraPickupSelected, 'extra_drop' => $extraDropSelected] as $key => $selected) {
            if ($selected) $optionKeys[] = $key;
        }
        if ($isPublishedPackage) {
            $selectedOptions = CategoryBookingOptions::selected($category, $optionKeys);
            $optionPrices = array_column($selectedOptions['items'], 'price', 'key');
            $patCharge = (float) ($optionPrices['pet_friendly'] ?? 0);
            $roofCarrierCharge = (float) ($optionPrices['roof_carrier'] ?? 0);
            $extraPickupCharge = (float) ($optionPrices['extra_pickup'] ?? 0);
            $extraDropCharge = (float) ($optionPrices['extra_drop'] ?? 0);
        } else {
            $patCharge = $patSelected ? max(0, (float) ($category?->pet_friendly_charge ?? 0)) : 0.0;
            $roofCarrierCharge = $roofCarrierSelected ? max(0, (float) ($category?->roof_carrier_charge ?? 0)) : 0.0;
            $extraPickupCharge = $extraPickupSelected ? max(0, (float) ($category?->extra_pickup_charge ?? 0)) : 0.0;
            $extraDropCharge = $extraDropSelected ? max(0, (float) ($category?->extra_drop_charge ?? 0)) : 0.0;
        }

        /*
         * Prefer the admin-configured night charge when selected.
         * Keep support for the old direct night_charge request field.
         */
        if ($nightChargeSelected) {
            $nightCharge = max(0, (float) ($route->night_charge ?? 0));
        } elseif (! $isPublishedPackage && array_key_exists('night_charge', $data)) {
            $nightCharge = max(0, (float) $data['night_charge']);
        } else {
            $nightCharge = 0.0;
        }

        $subtotalBeforeGst = round(
            $baseFare
            + $extraKmAmount
            + $extraHrAmount
            + $tollExtra
            + $borderTaxExtra
            + $driverAllowanceExtra
            + $patCharge
            + $roofCarrierCharge
            + $nightCharge
            + $extraPickupCharge
            + $extraDropCharge,
            2
        );

        /*
         * GST percentage is controlled by admin.
         * Old gst_percent request is used only for other legacy ride types.
         */
        $gstPercent = (float) (
            $route->gst_percentage
            ?? ($isPublishedPackage ? 0 : ($data['gst_percent'] ?? 0))
        );

        $gstPercent = max(0, min(28, $gstPercent));

        if ($gstIncluded) {
            $gstAmount = $gstPercent > 0
                ? round($subtotalBeforeGst * $gstPercent / (100 + $gstPercent), 2)
                : 0.0;

            $taxableAmount = round($subtotalBeforeGst - $gstAmount, 2);
            $total = $subtotalBeforeGst;
        } else {
            $taxableAmount = $subtotalBeforeGst;
            $gstAmount = round($taxableAmount * $gstPercent / 100, 2);
            $total = round($taxableAmount + $gstAmount, 2);
        }

        return [
            'route' => [
                'id' => $route->id,
                'name' => $route->name,
                'slug' => $route->slug,
                'ride_type' => $route->ride_type,
            ],

            'category' => $category ? [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'model' => $category->model,
                'extra_km_charge' => (float) ($category->extra_km_charge ?? 0),
                'extra_hr_charge' => (float) ($category->extra_hr_charge ?? 0),
                'pet_friendly_charge' => (float) ($category->pet_friendly_charge ?? 0),
                'roof_carrier_charge' => (float) ($category->roof_carrier_charge ?? 0),
                'extra_pickup_charge' => (float) ($category->extra_pickup_charge ?? 0),
                'extra_drop_charge' => (float) ($category->extra_drop_charge ?? 0),
            ] : null,

            'price_id' => $routePrice?->id,
            'quantity' => $quantity,
            'distance_km' => $hasDistance ? $distanceKm : null,
            'duration_hr' => $hasDuration ? $durationHr : null,
            'category_options' => $isPublishedPackage ? $selectedOptions['items'] : [],
            'category_options_total' => $isPublishedPackage ? $selectedOptions['total'] : 0,
            'parking_policy' => 'extra_as_actual',

            'fare_breakup' => [
                'base_fare' => round($baseFare, 2),
                'quantity' => $quantity,
                'max_fare' => round($maxFare, 2),

                'km_limit' => $kmLimit,
                'hr_limit' => $hrLimit,

                'extra_km' => $extraKm,
                'extra_km_charge' => $extraKmCharge,
                'extra_km_amount' => $extraKmAmount,

                'extra_hr' => $extraHr,
                'extra_hr_charge' => $extraHrCharge,
                'extra_hr_amount' => $extraHrAmount,

                'toll_tax' => $configuredToll,
                'toll_included' => $tollIncluded,
                'toll_payable' => round($tollExtra, 2),

                'border_tax' => $configuredBorderTax,
                'state_tax_included' => $stateTaxIncluded,
                'border_tax_payable' => round($borderTaxExtra, 2),

                'driver_allowance' => round(
                    $configuredDriverAllowance,
                    2
                ),

                'driver_allowance_included' => $isPublishedPackage,
                'driver_allowance_payable' => round($driverAllowanceExtra, 2),
                'parking_included' => $parkingIncluded,
                'parking_policy' => 'extra_as_actual',

                'pat_selected' => $patSelected,
                'pat_charge' => round($patCharge, 2),

                'roof_carrier_selected' => $roofCarrierSelected,
                'roof_carrier_charge' => round(
                    $roofCarrierCharge,
                    2
                ),

                'night_charge_selected' => $nightChargeSelected,
                'night_charge' => round($nightCharge, 2),

                'extra_pickup_selected' => $extraPickupSelected,
                'extra_pickup_charge' => round($extraPickupCharge, 2),
                'extra_drop_selected' => $extraDropSelected,
                'extra_drop_charge' => round($extraDropCharge, 2),

                'gst_included' => $gstIncluded,
                'gst_percent' => $gstPercent,
                'taxable_amount' => round($taxableAmount, 2),
                'gst_amount' => round($gstAmount, 2),
            ],

            'subtotal' => $subtotalBeforeGst,
            'total_fare' => $total,

            'currency' => 'INR',
            'currency_symbol' => '₹',
        ];
    }
}
