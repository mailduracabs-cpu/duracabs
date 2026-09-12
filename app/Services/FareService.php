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

        /*
         * Vehicle/category-wise route fare is stored in prices.
         * Product price remains the legacy fallback.
         */
        $routePrice = null;

        if ($category) {
            $routePrice = Price::query()
                ->where('product_id', $route->id)
                ->where('category_id', $category->id)
                ->first();
        }

        $baseFare = $routePrice
            ? (float) $routePrice->price
            : (float) $route->price;

        $maxFare = $routePrice
            ? (float) $routePrice->max_price
            : (float) $route->max_price;

        $distanceKm = (float) (
            $data['distance_km']
            ?? $route->km_limit
            ?? 0
        );

        $durationHr = (float) (
            $data['duration_hr']
            ?? $route->hr_limit
            ?? 0
        );

        $extraKmCharge = (float) (
            $route->extra_km_charge
            ?? ($category->km_charge ?? 0)
        );

        $extraHrCharge = (float) ($route->extra_hr_charge ?? 0);
        $kmLimit = (float) ($route->km_limit ?? 0);
        $hrLimit = (float) ($route->hr_limit ?? 0);

        $extraKm = max(0, $distanceKm - $kmLimit);
        $extraHr = max(0, $durationHr - $hrLimit);

        $extraKmAmount = round($extraKm * $extraKmCharge, 2);
        $extraHrAmount = round($extraHr * $extraHrCharge, 2);

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

        /*
         * Included charges are informational only.
         * Extra charges are added to payable total.
         */
        $tollExtra = $tollIncluded ? 0.0 : $configuredToll;
        $borderTaxExtra = $stateTaxIncluded ? 0.0 : $configuredBorderTax;

        /*
         * Driver allowance currently has no included toggle in admin,
         * so preserve legacy behaviour and add it to the payable amount.
         */
        $driverAllowanceExtra = $configuredDriverAllowance;

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

        $patCharge = $patSelected
            ? max(0, (float) ($route->pat_charge ?? 0))
            : 0.0;

        $roofCarrierCharge = $roofCarrierSelected
            ? max(0, (float) ($route->roof_carrier_charge ?? 0))
            : 0.0;

        /*
         * Prefer the admin-configured night charge when selected.
         * Keep support for the old direct night_charge request field.
         */
        if ($nightChargeSelected) {
            $nightCharge = max(0, (float) ($route->night_charge ?? 0));
        } elseif (array_key_exists('night_charge', $data)) {
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
            + $nightCharge,
            2
        );

        /*
         * GST percentage is controlled by admin.
         * Old gst_percent request is used only as a fallback for legacy routes.
         */
        $gstPercent = (float) (
            $route->gst_percentage
            ?? ($data['gst_percent'] ?? 0)
        );

        $gstPercent = max(0, min(28, $gstPercent));

        $gstAmount = $gstIncluded
            ? 0.0
            : round($subtotalBeforeGst * $gstPercent / 100, 2);

        $total = round($subtotalBeforeGst + $gstAmount, 2);

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
            ] : null,

            'price_id' => $routePrice?->id,
            'distance_km' => $distanceKm,
            'duration_hr' => $durationHr,

            'fare_breakup' => [
                'base_fare' => round($baseFare, 2),
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

                'parking_included' => $parkingIncluded,

                'pat_selected' => $patSelected,
                'pat_charge' => round($patCharge, 2),

                'roof_carrier_selected' => $roofCarrierSelected,
                'roof_carrier_charge' => round(
                    $roofCarrierCharge,
                    2
                ),

                'night_charge_selected' => $nightChargeSelected,
                'night_charge' => round($nightCharge, 2),

                'gst_included' => $gstIncluded,
                'gst_percent' => $gstPercent,
                'gst_amount' => round($gstAmount, 2),
            ],

            'subtotal' => $subtotalBeforeGst,
            'total_fare' => $total,

            'currency' => 'INR',
            'currency_symbol' => '₹',
        ];
    }
}
