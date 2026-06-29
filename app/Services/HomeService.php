<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class HomeService
{
    public function appConfig(): array
    {
        return [
            'app_name' => 'Dura Cabs',
            'theme_color' => '#009FFD',
            'support_mobile' => '+91 9412301097',
            'whatsapp_mobile' => '+91 9412301097',
            'email' => 'info@duracabs.com',
            'website' => 'https://www.duracabs.com',
            'currency' => 'INR',
        ];
    }

    public function home(): array
    {
        return [
            'app_config' => $this->appConfig(),
            'banners' => $this->banners(),
            'offers' => $this->offers(),
            'categories' => $this->vehicleCategories(),
            'self_drive_cars' => $this->selfDriveCars(),
            'popular_routes' => $this->popularRoutes(),
            'ai_recommended_trips' => $this->recommendedTrips(),
        ];
    }

    public function banners(): array
    {
        $rows = $this->tableRows(['banners', 'app_banners', 'sliders'], 6);
        if (!empty($rows)) {
            return array_map(function ($row) {
                return [
                    'id' => $row['id'] ?? null,
                    'title' => $row['title'] ?? $row['name'] ?? 'Dura Cabs Offer',
                    'subtitle' => $row['subtitle'] ?? $row['description'] ?? 'Premium taxi and self drive service',
                    'image' => $row['image'] ?? $row['banner_image'] ?? null,
                    'badge' => $row['badge'] ?? 'Offer',
                    'type' => $row['type'] ?? 'banner',
                ];
            }, $rows);
        }

        return [
            ['id' => 1, 'title' => 'Premium Outstation Taxi', 'subtitle' => 'Verified drivers, clean cars and best fare', 'badge' => '24x7', 'type' => 'taxi'],
            ['id' => 2, 'title' => 'Self Drive Cars in Agra', 'subtitle' => 'Hourly and 24 hour rental available', 'badge' => 'New', 'type' => 'self_drive'],
            ['id' => 3, 'title' => 'Delhi Airport Transfers', 'subtitle' => 'Book Agra to Delhi Airport cab instantly', 'badge' => 'Popular', 'type' => 'airport'],
        ];
    }

    public function offers(): array
    {
        $rows = $this->tableRows(['offers', 'coupons'], 8);
        if (!empty($rows)) {
            return array_map(function ($row) {
                return [
                    'id' => $row['id'] ?? null,
                    'title' => $row['title'] ?? $row['name'] ?? $row['code'] ?? 'Special Offer',
                    'code' => $row['code'] ?? null,
                    'description' => $row['description'] ?? null,
                    'discount' => $row['discount'] ?? $row['amount'] ?? null,
                ];
            }, $rows);
        }

        return [
            ['id' => 1, 'title' => 'Flat ₹200 OFF on outstation rides', 'code' => 'DURA200'],
            ['id' => 2, 'title' => 'Best price for Agra to Delhi Airport', 'code' => 'AIRPORT'],
        ];
    }

    public function vehicleCategories(): array
    {
        try {
            $query = Category::query();
            if (Schema::hasColumn('categories', 'is_active')) {
                $query->where(function ($q) {
                    $q->where('is_active', 1)->orWhereNull('is_active');
                });
            }
            return $query->orderBy('id', 'desc')->take(12)->get()->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'image' => $category->image,
                    'model' => $category->model,
                    'passanger_capacity' => $category->passanger_capacity,
                    'luggage_capacity' => $category->luggage_capacity,
                    'km_charge' => $category->km_charge,
                    'driver_charge' => $category->driver_charge,
                    'security' => $category->security,
                    'pet_friendly' => $category->pet_friendly,
                ];
            })->values()->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function selfDriveCars(): array
    {
        $rows = $this->tableRows(['self_drive_cars', 'self_drives', 'vehicles', 'cars'], 12);
        if (!empty($rows)) {
            return array_map(function ($row) {
                $name = $row['car_company_name'] ?? $row['vehicle_name'] ?? $row['name'] ?? $row['title'] ?? 'Self Drive Car';
                return [
                    'id' => $row['id'] ?? null,
                    'name' => $name,
                    'slug' => $row['slug'] ?? Str::slug($name),
                    'model' => $row['model'] ?? $row['fuel_type'] ?? null,
                    'vehicle_number' => $row['vehicle_number'] ?? null,
                    'image' => $row['image'] ?? $row['thumbnail'] ?? null,
                    'price' => $row['price'] ?? $row['rent'] ?? $row['daily_price'] ?? $row['amount'] ?? null,
                    'security' => $row['security'] ?? 5000,
                ];
            }, $rows);
        }

        return [
            ['id' => 1, 'name' => 'Wagon R', 'slug' => 'wagon-r', 'model' => 'CNG/Petrol', 'price' => 1800, 'security' => 5000],
            ['id' => 2, 'name' => 'Swift', 'slug' => 'swift', 'model' => 'Petrol', 'price' => 2000, 'security' => 5000],
            ['id' => 3, 'name' => 'Ertiga', 'slug' => 'ertiga', 'model' => '7 Seater', 'price' => 3500, 'security' => 5000],
        ];
    }

    public function popularRoutes(): array
    {
        $rows = $this->tableRows(['one_way_routes', 'routes', 'popular_routes'], 10);
        if (!empty($rows)) {
            return array_map(function ($row) {
                $from = $row['from_city'] ?? $row['from'] ?? null;
                $to = $row['to_city'] ?? $row['to'] ?? null;
                return [
                    'id' => $row['id'] ?? null,
                    'title' => $row['title'] ?? $row['name'] ?? (($from && $to) ? "$from → $to" : 'Popular Route'),
                    'from' => $from,
                    'to' => $to,
                    'distance' => $row['distance'] ?? $row['km'] ?? null,
                    'duration' => $row['duration'] ?? $row['time'] ?? null,
                    'price' => $row['sale_price'] ?? $row['price'] ?? $row['regular_price'] ?? $row['amount'] ?? null,
                ];
            }, $rows);
        }

        return [
            ['id' => 1, 'title' => 'Agra → Delhi', 'distance' => '220 km', 'duration' => '4 hrs', 'price' => 2799],
            ['id' => 2, 'title' => 'Agra → Jaipur', 'distance' => '240 km', 'duration' => '5 hrs', 'price' => 3000],
            ['id' => 3, 'title' => 'Delhi Airport → Agra', 'distance' => '230 km', 'duration' => '4.5 hrs', 'price' => 2700],
        ];
    }

    public function recommendedTrips(): array
    {
        return [
            ['id' => 1, 'title' => 'Agra to Delhi Airport', 'subtitle' => 'Customers who search Delhi prefer this route', 'price' => 2799, 'type' => 'airport'],
            ['id' => 2, 'title' => 'Agra Mathura Vrindavan', 'subtitle' => 'Best religious family tour package', 'price' => 5500, 'type' => 'tour'],
            ['id' => 3, 'title' => 'Golden Triangle Tour', 'subtitle' => 'Delhi Agra Jaipur premium package', 'price' => 21600, 'type' => 'package'],
        ];
    }

    private function tableRows(array $tables, int $limit): array
    {
        foreach ($tables as $table) {
            try {
                if (!Schema::hasTable($table)) {
                    continue;
                }
                $query = DB::table($table);
                if (Schema::hasColumn($table, 'is_active')) {
                    $query->where(function ($q) {
                        $q->where('is_active', 1)->orWhereNull('is_active');
                    });
                }
                if (Schema::hasColumn($table, 'status')) {
                    $query->where(function ($q) {
                        $q->where('status', 1)->orWhere('status', 'active')->orWhereNull('status');
                    });
                }
                return $query->orderByDesc('id')->limit($limit)->get()->map(fn ($r) => (array) $r)->toArray();
            } catch (\Throwable $e) {
                continue;
            }
        }
        return [];
    }
}
