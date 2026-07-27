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
            'support_mobile' => '+91 7088873331',
            'whatsapp_mobile' => '+91 7088873331',
            'email' => 'info@duracabs.com',
            'website' => 'https://www.duracabs.com',
            'currency' => 'INR',
        ];
    }

    public function home(): array
{
    return Cache::remember('dura_home_v4', 600, function () {
        return [
            'config' => $this->appConfig(),
            'app_config' => $this->appConfig(),

            'hero' => $this->hero(),
            'home_images' => $this->homeImages(),

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
public function homeImages(): array
{
    $images = DB::table('home_images')->get();

    $data = [];

    foreach ($images as $image) {
        $data[$image->image_key] = asset($image->image_path);
    }

    return $data;
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
                'subtitle' => 'Hourly, Daily, Weekly & Monthly rental available',
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

            if (Schema::hasColumn('categories', 'service_group')) {
                $query->where('service_group', 'with_driver');
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
                        'service_group' => $category->service_group ?? 'with_driver',
                        'vehicle_type' => $category->vehicle_type ?? 'car',
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
                $query = Product::query()
                    ->with([
                        'category',
                        'selfDrivePrice',
                    ]);

                if (Schema::hasColumn('products', 'service_group')) {
                    $query->where('service_group', 'without_driver');
                }

                if (Schema::hasColumn('products', 'is_active')) {
                    $query->where(function ($q) {
                        $q->where('is_active', 1)->orWhereNull('is_active');
                    });
                }

                if (Schema::hasColumn('products', 'in_stock')) {
                    $query->where(function ($q) {
                        $q->where('in_stock', 1)->orWhereNull('in_stock');
                    });
                }

                $products = $query->orderByDesc('id')->take(12)->get();

                if ($products->isNotEmpty()) {
                    return $products->map(function ($product) {
                        $price = $product->selfDrivePrice;

                        $hourlyPrice = $price->hourly_price ?? null;
                        $dailyPrice = $price->daily_price ?? null;
                        $weeklyPrice = $price->weekly_price ?? null;
                        $monthlyPrice = $price->monthly_price ?? null;

                        $displayPrice = 'Best Price';

                        if (!empty($hourlyPrice) && $hourlyPrice > 0) {
                            $displayPrice = '₹' . number_format((float) $hourlyPrice, 0) . '/Hour';
                        } elseif (!empty($dailyPrice) && $dailyPrice > 0) {
                            $displayPrice = '₹' . number_format((float) $dailyPrice, 0) . '/Day';
                        } elseif (!empty($weeklyPrice) && $weeklyPrice > 0) {
                            $displayPrice = '₹' . number_format((float) $weeklyPrice, 0) . '/Week';
                        } elseif (!empty($monthlyPrice) && $monthlyPrice > 0) {
                            $displayPrice = '₹' . number_format((float) $monthlyPrice, 0) . '/Month';
                        } elseif (!empty($product->price) && $product->price > 0) {
                            $displayPrice = '₹' . number_format((float) $product->price, 0);
                        }

                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'slug' => $product->slug ?: Str::slug($product->name),

                            'service_group' => $product->service_group ?? 'without_driver',
                            'vehicle_type' => $product->vehicle_type ?? 'car',

                            'type' => optional($product->category)->name ?: 'Self Drive',
                            'category_name' => optional($product->category)->name ?: 'Self Drive',
                            'model' => optional($product->category)->model,
                            'seats' => optional($product->category)->passanger_capacity ?: '5',

                            'image' => DuraImage::first($product->images) ?: DuraImage::url(optional($product->category)->image),
                            'images' => $product->images,

                            'price' => $displayPrice,
                            'raw_price' => $product->price,
                            'starting_price' => $displayPrice,

                            'security' => $price->security_deposit ?? optional($product->category)->security ?? 5000,
                            'security_deposit' => $price->security_deposit ?? 5000,

                            'km_limit' => $price->daily_km_limit ?? $product->km_limit,
                            'hr_limit' => $price->minimum_booking_hours ?? $product->hr_limit,

                            'self_drive_price' => [
                                'hourly_price' => $hourlyPrice,
                                'daily_price' => $dailyPrice,
                                'weekly_price' => $weeklyPrice,
                                'monthly_price' => $monthlyPrice,
                                'security_deposit' => $price->security_deposit ?? 5000,
                                'daily_km_limit' => $price->daily_km_limit ?? 300,
                                'extra_km_charge' => $price->extra_km_charge ?? 0,
                                'extra_hour_charge' => $price->extra_hour_charge ?? 0,
                                'minimum_booking_hours' => $price->minimum_booking_hours ?? 24,
                                'maximum_booking_days' => $price->maximum_booking_days ?? 30,
                                'pickup_charge' => $price->pickup_charge ?? 0,
                                'delivery_charge' => $price->delivery_charge ?? 0,
                                'fuel_policy' => $price->fuel_policy ?? 'same_to_same',
                                'transmission' => $price->transmission ?? 'manual',
                                'home_delivery' => (bool) ($price->home_delivery ?? false),
                                'doorstep_pickup' => (bool) ($price->doorstep_pickup ?? false),
                                'driver_available' => (bool) ($price->driver_available ?? false),
                            ],
                        ];
                    })->values()->toArray();
                }
            }
        } catch (\Throwable $e) {
            //
        }

        return [
            [
                'id' => 1,
                'name' => 'Wagon R',
                'slug' => 'wagon-r',
                'service_group' => 'without_driver',
                'vehicle_type' => 'car',
                'type' => 'Hatchback',
                'seats' => '5',
                'image' => null,
                'price' => '₹83/Hour',
                'security' => 5000,
            ],
            [
                'id' => 2,
                'name' => 'Aura CNG',
                'slug' => 'aura-cng',
                'service_group' => 'without_driver',
                'vehicle_type' => 'car',
                'type' => 'Sedan',
                'seats' => '5',
                'image' => null,
                'price' => '₹125/Hour',
                'security' => 5000,
            ],
            [
                'id' => 3,
                'name' => 'Ertiga',
                'slug' => 'ertiga',
                'service_group' => 'without_driver',
                'vehicle_type' => 'car',
                'type' => 'SUV',
                'seats' => '7',
                'image' => null,
                'price' => '₹146/Hour',
                'security' => 5000,
            ],
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