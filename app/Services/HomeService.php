<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HomeService
{
    public function appConfig(): array
    {
        return [
            'app_name' => 'Dura Cabs',
            'company_name' => 'Dura Cabs Services',
            'support_mobile' => '7088873331',
            'whatsapp_mobile' => '917088873331',
            'email' => 'no-reply@duracabs.com',
            'website' => 'https://www.duracabs.com',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'country_code' => '+91',
            'minimum_booking_amount' => 500,
            'app_version' => '1.0.0',
            'force_update' => false,
            'maintenance_mode' => false,
            'terms_url' => 'https://www.duracabs.com/terms-and-conditions',
            'privacy_url' => 'https://www.duracabs.com/privacy-policy',
        ];
    }

    public function home(): array
    {
        return [
            'app_config' => $this->appConfig(),
            'banners' => $this->table('banners')
                ? DB::table('banners')->select('id', 'title', 'image', 'url')->latest()->limit(10)->get()
                : [],
            'categories' => $this->table('categories')
                ? DB::table('categories')->where('is_active', 1)->limit(12)->get()
                : [],
            'self_drive_cars' => $this->table('vehicles')
                ? DB::table('vehicles')->where('is_active', 1)->latest()->limit(10)->get()
                : [],
            'popular_routes' => $this->table('products')
                ? DB::table('products')->where('is_active', 1)->latest()->limit(10)->get()
                : [],
            'offers' => $this->table('coupons')
                ? DB::table('coupons')->latest()->limit(10)->get()
                : [],
            'reviews' => $this->table('reviews')
                ? DB::table('reviews')->latest()->limit(10)->get()
                : [],
        ];
    }

    private function table(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
