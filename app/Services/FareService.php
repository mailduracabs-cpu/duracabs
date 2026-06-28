<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;

class FareService
{
    public function estimate(array $data): array
    {
        $route = null;

        if (!empty($data['route_id'])) {
            $route = Product::query()->where('is_active', 1)->find($data['route_id']);
        }

        if (!$route && !empty($data['from']) && !empty($data['to'])) {
            $from = trim($data['from']);
            $to = trim($data['to']);

            $route = Product::query()
                ->where('is_active', 1)
                ->where('name', 'LIKE', "%{$from}%")
                ->where('name', 'LIKE', "%{$to}%")
                ->orderBy('price')
                ->first();
        }

        if (!$route) {
            throw new \InvalidArgumentException('Route not found. Please select a valid route.');
        }

        $category = null;
        if (!empty($data['category_id'])) {
            $category = Category::query()->where('is_active', 1)->find($data['category_id']);
        }

        $baseFare = (float) $route->price;
        $distanceKm = (float) ($data['distance_km'] ?? $route->km_limit ?? 0);
        $durationHr = (float) ($data['duration_hr'] ?? $route->hr_limit ?? 0);
        $extraKmCharge = (float) ($route->extra_km_charge ?? ($category->km_charge ?? 0));
        $extraHrCharge = (float) ($route->extra_hr_charge ?? 0);
        $kmLimit = (float) ($route->km_limit ?? 0);
        $hrLimit = (float) ($route->hr_limit ?? 0);

        $extraKm = max(0, $distanceKm - $kmLimit);
        $extraHr = max(0, $durationHr - $hrLimit);

        $extraKmAmount = $extraKm * $extraKmCharge;
        $extraHrAmount = $extraHr * $extraHrCharge;

        $tollTax = (float) ($route->toll_tax ?? 0);
        $borderTax = (float) ($route->border_tax ?? 0);
        $driverAllowance = (float) ($route->driver_allowances ?? 0);
        $nightCharge = !empty($data['night_charge']) ? (float) $data['night_charge'] : 0;

        $subtotal = $baseFare + $extraKmAmount + $extraHrAmount + $tollTax + $borderTax + $driverAllowance + $nightCharge;
        $gstPercent = (float) ($data['gst_percent'] ?? 0);
        $gstAmount = round($subtotal * $gstPercent / 100, 2);
        $total = round($subtotal + $gstAmount, 2);

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
            'distance_km' => $distanceKm,
            'duration_hr' => $durationHr,
            'fare_breakup' => [
                'base_fare' => $baseFare,
                'km_limit' => $kmLimit,
                'hr_limit' => $hrLimit,
                'extra_km' => $extraKm,
                'extra_km_charge' => $extraKmCharge,
                'extra_km_amount' => round($extraKmAmount, 2),
                'extra_hr' => $extraHr,
                'extra_hr_charge' => $extraHrCharge,
                'extra_hr_amount' => round($extraHrAmount, 2),
                'toll_tax' => $tollTax,
                'border_tax' => $borderTax,
                'driver_allowance' => $driverAllowance,
                'night_charge' => $nightCharge,
                'gst_percent' => $gstPercent,
                'gst_amount' => $gstAmount,
            ],
            'subtotal' => round($subtotal, 2),
            'total_fare' => $total,
            'currency' => 'INR',
            'currency_symbol' => '₹',
        ];
    }
}
