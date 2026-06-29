<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Support\DuraImage;
use Illuminate\Support\Facades\Cache;
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
        return Cache::remember('dura_home_v2', 600, function () {
            return [
                'config' => $this->appConfig(),
                'app_config' => $this->appConfig(),

                'hero' => $this->hero(),
                'banners' => $this->banners(),
                'offers' => $this->offers(),

                'categories' => $this->vehicleCategories(),
                'vehicle_categories' => $this->vehicleCategories(),

                'self_drive' => $this->selfDriveCars(),
                'self_drive_cars' => $this->selfDriveCars(),

                'popular_routes' => $this->popularRoutes(),
                'recommended_trips' => $this->recommendedTrips(),
                'ai_recommended_trips' => $this->recommendedTrips(),

                'tour_packages' => $this->tourPackages(),
                'reviews' => $this->reviews(),
            ];
        });
    }

    public function hero(): array
    {
        return [
            'title' => 'Premium Travel with Dura Cabs',
            'subtitle' => 'Taxi, Self Drive, Airport Transfer & Tour Packages',
            'badge' => 'Trusted Travel Partner',
            'button_text' => 'Book Now',
            'image' => $this->firstBannerImage(),
        ];
    }

    public function banners(): array
    {
        $rows = $this->tableRows(['banners', 'app_banners', 'sliders'], 8);

        if (!empty($rows)) {
            return array_map(function ($row) {
                return [
                    'id' => $row['id'] ?? null,
                    'tag' => $row['badge'] ?? $row['ride_type'] ?? 'Dura Cabs',
                    'badge' => $row['badge'] ?? $row['ride_type'] ?? 'Dura Cabs',
                    'title' => $row['title'] ?? $row['name'] ?? 'Dura Cabs Offer',
                    'subtitle' => $row['subtitle'] ?? $row['description'] ?? 'Premium taxi and self drive service',
                    'image' => DuraImage::url($row['image'] ?? $row['banner_image'] ?? null),
                    'url' => $row['url'] ?? null,
                    'type' => $row['ride_type'] ?? $row['type'] ?? 'banner',
                ];
            }, $rows);
        }

        return [
            [
                'id' => 1,
                'tag' => '24x7',
                'title' => 'Premium Outstation Taxi',
                'subtitle' => 'Verified drivers, clean cars and best fare',
                'image' => null,
                'url' => null,
                'type' => 'taxi',
            ],
            [
                'id' => 2,
                'tag' => 'Self Drive',
                'title' => 'Self Drive Cars in Agra',
                'subtitle' => 'Hourly and 24 hour rental available',
                'image' => null,
                'url' => null,
                'type' => 'self_drive',
            ],
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
                    'subtitle' => $row['subtitle'] ?? $row['description'] ?? 'Limited time offer',
                    'description' => $row['description'] ?? null,
                    'code' => $row['code'] ?? $row['coupon_code'] ?? 'OFFER',
                    'discount' => $row['discount'] ?? $row['amount'] ?? null,
                    'image' => DuraImage::url($row['image'] ?? null),
                ];
            }, $rows);
        }

        return [
            [
                'id' => 1,
                'title' => 'Flat ₹200 OFF',
                'subtitle' => 'On outstation rides',
                'code' => 'DURA200',
                'image' => null,
            ],
            [
                'id' => 2,
                'title' => 'Airport Taxi Deal',
                'subtitle' => 'Best price for Agra to Delhi Airport',
                'code' => 'AIRPORT',
                'image' => null,
            ],
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

            return $query
                ->orderByDesc('id')
                ->take(12)
                ->get()
                ->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'image' => DuraImage::url($category->image),
                        'model' => $category->model,
                        'subtitle' => $category->model ?: 'Comfortable cab',
                        'passanger_capacity' => $category->passanger_capacity,
                        'passenger_capacity' => $category->passanger_capacity,
                        'luggage_capacity' => $category->luggage_capacity,
                        'km_charge' => $category->km_charge,
                        'driver_charge' => $category->driver_charge,
                        'security' => $category->security,
                        'pet_friendly' => $category->pet_friendly,
                        'price' => $category->km_charge ? 'From ₹' . $category->km_charge . '/km' : 'Best Price',
                    ];
                })
                ->values()
                ->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function selfDriveCars(): array
    {
        try {
            if (Schema::hasTable('products')) {
                $query = Product::query()->with('category');

                if (Schema::hasColumn('products', 'is_active')) {
                    $query->where(function ($q) {
                        $q->where('is_active', 1)->orWhereNull('is_active');
                    });
                }

                if (Schema::hasColumn('products', 'ride_type')) {
                    $query->where(function ($q) {
                        $q->where('ride_type', 'self_drive')
                            ->orWhere('ride_type', 'self-drive')
                            ->orWhere('ride_type', 'Self Drive');
                    });
                }

                $products = $query->orderByDesc('id')->take(12)->get();

                if ($products->isNotEmpty()) {
                    return $products->map(function ($product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'slug' => $product->slug ?: Str::slug($product->name),
                            'type' => optional($product->category)->name ?: 'Self Drive',
                            'model' => optional($product->category)->model,
                            'seats' => optional($product->category)->passanger_capacity ?: '5',
                            'image' => DuraImage::first($product->images) ?: DuraImage::url(optional($product->category)->image),
                            'price' => $product->price ? '₹' . $product->price : 'Best Price',
                            'raw_price' => $product->price,
                            'security' => optional($product->category)->security ?: 5000,
                            'km_limit' => $product->km_limit,
                            'hr_limit' => $product->hr_limit,
                        ];
                    })->values()->toArray();
                }
            }
        } catch (\Throwable $e) {
            //
        }

        return [
            ['id' => 1, 'name' => 'Wagon R', 'slug' => 'wagon-r', 'type' => 'Hatchback', 'seats' => '5', 'image' => null, 'price' => '₹83/hour', 'security' => 5000],
            ['id' => 2, 'name' => 'Aura CNG', 'slug' => 'aura-cng', 'type' => 'Sedan', 'seats' => '5', 'image' => null, 'price' => '₹125/hour', 'security' => 5000],
            ['id' => 3, 'name' => 'Ertiga', 'slug' => 'ertiga', 'type' => 'SUV', 'seats' => '7', 'image' => null, 'price' => '₹146/hour', 'security' => 5000],
        ];
    }

    public function popularRoutes(): array
    {
        $rows = $this->tableRows(['one_way_routes', 'routes', 'popular_routes'], 10);

        if (!empty($rows)) {
            return array_map(function ($row) {
                $from = $row['from_city'] ?? $row['from'] ?? $row['source'] ?? null;
                $to = $row['to_city'] ?? $row['to'] ?? $row['destination'] ?? null;

                return [
                    'id' => $row['id'] ?? null,
                    'title' => $row['title'] ?? $row['name'] ?? (($from && $to) ? "$from → $to" : 'Popular Route'),
                    'from' => $from,
                    'to' => $to,
                    'distance' => $row['distance'] ?? $row['km'] ?? null,
                    'duration' => $row['duration'] ?? $row['time'] ?? null,
                    'price' => $this->formatPrice($row['sale_price'] ?? $row['price'] ?? $row['regular_price'] ?? $row['amount'] ?? null),
                    'image' => DuraImage::url($row['image'] ?? null),
                ];
            }, $rows);
        }

        return [
            ['id' => 1, 'title' => 'Agra → Delhi', 'from' => 'Agra', 'to' => 'Delhi', 'distance' => '220 km', 'duration' => '4 hrs', 'price' => '₹2799', 'image' => null],
            ['id' => 2, 'title' => 'Agra → Jaipur', 'from' => 'Agra', 'to' => 'Jaipur', 'distance' => '240 km', 'duration' => '5 hrs', 'price' => '₹3000', 'image' => null],
            ['id' => 3, 'title' => 'Delhi Airport → Agra', 'from' => 'Delhi Airport', 'to' => 'Agra', 'distance' => '230 km', 'duration' => '4.5 hrs', 'price' => '₹2700', 'image' => null],
        ];
    }

    public function recommendedTrips(): array
    {
        return [
            ['id' => 1, 'title' => 'Agra to Delhi Airport', 'route' => 'Agra → Delhi Airport', 'subtitle' => 'Customers who search Delhi prefer this route', 'price' => '₹2799', 'type' => 'airport', 'image' => $this->firstBannerImage()],
            ['id' => 2, 'title' => 'Agra Mathura Vrindavan', 'route' => 'Agra → Mathura → Vrindavan', 'subtitle' => 'Best religious family tour package', 'price' => '₹5500', 'type' => 'tour', 'image' => null],
            ['id' => 3, 'title' => 'Golden Triangle Tour', 'route' => 'Delhi → Agra → Jaipur', 'subtitle' => 'Delhi Agra Jaipur premium package', 'price' => '₹21600', 'type' => 'package', 'image' => null],
        ];
    }

    public function tourPackages(): array
    {
        $rows = $this->tableRows(['tour_packages', 'packages', 'pages'], 8);

        if (!empty($rows)) {
            return array_map(function ($row) {
                return [
                    'id' => $row['id'] ?? null,
                    'title' => $row['title'] ?? $row['name'] ?? 'Tour Package',
                    'days' => $row['days'] ?? $row['duration'] ?? 'Tour',
                    'price' => $this->formatPrice($row['price'] ?? $row['amount'] ?? null),
                    'description' => $row['description'] ?? $row['short_description'] ?? null,
                    'image' => DuraImage::url($row['image'] ?? $row['thumbnail'] ?? null),
                ];
            }, $rows);
        }

        return [
            ['id' => 1, 'title' => 'Same Day Agra Tour', 'days' => '1 Day', 'price' => 'From ₹6000', 'image' => null],
            ['id' => 2, 'title' => 'Golden Triangle Tour', 'days' => '3 Days', 'price' => 'From ₹21600', 'image' => null],
            ['id' => 3, 'title' => 'Mathura Vrindavan Tour', 'days' => '1 Day', 'price' => 'From ₹3500', 'image' => null],
        ];
    }

    public function reviews(): array
    {
        $rows = $this->tableRows(['reviews', 'testimonials'], 8);

        if (!empty($rows)) {
            return array_map(function ($row) {
                return [
                    'id' => $row['id'] ?? null,
                    'name' => $row['name'] ?? $row['customer_name'] ?? 'Customer',
                    'rating' => (string)($row['rating'] ?? '5.0'),
                    'text' => $row['text'] ?? $row['review'] ?? $row['comment'] ?? '',
                    'image' => DuraImage::url($row['image'] ?? $row['photo'] ?? null),
                ];
            }, $rows);
        }

        return [
            ['id' => 1, 'name' => 'Amit Sharma', 'rating' => '4.9', 'text' => 'Very clean car and polite driver. Best cab service in Agra.', 'image' => null],
            ['id' => 2, 'name' => 'Priya Singh', 'rating' => '5.0', 'text' => 'Booked Delhi airport to Agra cab. Smooth and professional service.', 'image' => null],
            ['id' => 3, 'name' => 'Rahul Verma', 'rating' => '4.8', 'text' => 'Self drive car was clean and pickup process was easy.', 'image' => null],
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
                        $q->where('status', 1)
                            ->orWhere('status', 'active')
                            ->orWhereNull('status');
                    });
                }

                return $query
                    ->orderByDesc('id')
                    ->limit($limit)
                    ->get()
                    ->map(fn ($row) => (array) $row)
                    ->toArray();
            } catch (\Throwable $e) {
                continue;
            }
        }

        return [];
    }

    private function firstBannerImage(): ?string
    {
        $banners = $this->banners();

        foreach ($banners as $banner) {
            if (!empty($banner['image'])) {
                return $banner['image'];
            }
        }

        return null;
    }

    private function formatPrice($price): ?string
    {
        if ($price === null || $price === '') {
            return 'Best Price';
        }

        if (is_numeric($price)) {
            return '₹' . $price;
        }

        return (string)$price;
    }
}
